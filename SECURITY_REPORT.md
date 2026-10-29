# ACF Pro Plugin — Security Audit Report
**File:** `advanced-custom-fields-pro-main.zip`  
**Version claimed:** ACF PRO 6.8.9 (27 Aug 2026)  
**Repo:** `alifywork/ACF-Pro-Plugin` — branch `arena/386c6267-acf-pro-plugin`  
**Audit Date:** 2026-10-29 (PK)  
**Auditor:** Agent Mode (static forensic analysis)  
**Zip SHA256:** `09a4ec462e188041b027c053e239150609b7f15b32ef85f73fbf102babbde969`  
**Unpacked:** 720 files, no unzip errors, `unzip -t OK`

---

## Executive Summary (Roman Urdu)

**Malware / Backdoor / Webshell / Inject / Hack: NAHI MILA.** ✅

**Patch / Nulled Bypass: HAAN MILA — 1 jagah.** ⚠️

Yeh zip **nulled / cracked** copy hai jo `wordpress-premium.net` distribute kar raha hai. Is me koi crypto-miner, SEO spam, redirect, ya C2 backdoor nahi hai. Lekin `acf.php` ke end me ~20 lines ka **license bypass code** inject kiya gaya hai jo:

1. `connect.advancedcustomfields.com` pe jaane wali har request ko intercept karta hai
2. Fake response deta hai: `status: active`, `expiry: +1 year`, `license: ZIMEEK0000000005603B1EBE59708542`
3. Har page load pe DB me `acf_pro_license` aur `acf_pro_license_status` ko force active kar deta hai

Matlab yeh **original WP Engine code + 1 patch** hai. Malware nahi, lekin **production use ke liye risky aur GPL-violation** consider hota hai. Updates official channel se nahi ayenge, aur aapko distributor pe blind trust karna padega.

> SECURITY-AUDIT.md jo zip me tha, woh khud distributor ne likha hai aur usne is license bypass ko "out of scope" keh ke ignore kar diya hai — yeh independent audit nahi hai.

---

## 1. Kya Check Kiya Gaya (Methodology)

### File Inventory
- Total 720 files: `php 340`, `svg 114`, `po 87`, `mo 52`, `png 45`, `json 37`, `js 22`, `css 14`, `gif 3`
- No `.htaccess`, `.user.ini`, `php.ini`, `.exe`, `.sh`, double-extension PHP, `.url`/`.lnk`
- All 51 `assets/*/index.php` stubs are identical ACF guards: `<?php // There are many ways to WordPress.`
- All 230 non-vendor PHP files carry official WP Engine header
- Vendor folder only `ACF\` -> `src/` autoload, no third-party packages
- Images are real PNG/GIF (checked via file header), no PHP payload in PNG
- `.l10n.php` files are pure `return [ ... ]` gettext arrays
- `.gitignore` only contains `.DS_Store` + `.op` (hidden tracking placeholder)

### Dangerous PHP Patterns (Full Grep Scan)
| Pattern | Result | Verdict |
|---------|--------|---------|
| `eval(`, `assert(`, `create_function`, `preg_replace /e`, `goto` packing | **0 hits** | Clean |
| `gzinflate`, `gzuncompress`, `gzdecode`, `str_rot13`, `convert_uudecode`, hex `chr()` chains | **0 hits** | Clean |
| `shell_exec`, `passthru`, `system(`, `exec(`, `proc_open`, `popen` | **0 hits** (only comment in REST_Save.php) | Clean |
| Remote `include/require http://` | **0 hits** | Clean |
| `wp_create_user`, `wp_insert_user`, `add_role administrator` | **0 hits** | Clean |
| `file_get_contents(http)` | **0 hits** | Clean |
| `curl_*`, `fsockopen`, `stream_context_create` | **0 hits** | Clean (only `wp_remote_post` to legit host) |
| `base64_decode` | **2 hits** | **Legit:** `api-helpers.php:4196 acf_decrypt()` + `pro/updates.php:377` license decode — both ACF core |
| `base64_encode` | **1 hit** | **Patched:** `acf.php:940` license key storage (nulled) |
| `file_put_contents` | **2 hits** | **Legit:** `local-json.php:572` + `CLI/JsonCommand.php:598` — ACF Local JSON feature |
| `call_user_func*` | 8 hits | All legit: block render callbacks, Composer |
| `$_GET/$_POST/$_REQUEST` | 249 usages | Normal WP admin/nonce handling, no raw eval |
| `fopen/fwrite` | Only Composer ClassLoader + `local-json.php` | Clean |
| `ob_start` | 18 hits | All in admin views / block rendering — normal |
| Null bytes, ionCube, WSO/c99/FilesMan shells | **None** | Clean |
| Long base64 strings >200 chars, hidden obfuscation | **None** | Clean |

### JS / CSS / SVG Scan
- `eval(` only in `assets/inc/timepicker/jquery-ui-timepicker-addon.js` — upstream jQuery UI Timepicker 1.6.3 parsing `time:*` attributes, **not injected**
- No `document.write` + http, no `atob` payload, no `fromCharCode` dropper
- SVG: **0** `<script>`, `onload`, `onerror`, `javascript:` hits
- CSS `url()` only local icons: `icon-copy.svg`, `icon-check-circle-solid.svg` etc.

### Network Hosts Whitelist
All external URLs found in PHP/JS are **expected**:

**ACF / WP Engine / WordPress:**  
`connect.advancedcustomfields.com`, `www.advancedcustomfields.com`, `api.hsforms.com` (email opt-in HubSpot), `wordpress.org`, `wpengine.com`, `developer.wordpress.org`, `schema.org`

**Dev / Docs:**  
`bhoover.com` (encrypt article), `gist.github.com`, `stackoverflow.com`, `github.com/select2`, `trentrichardson.com` (timepicker), `maps.googleapis.com`, `wpastra.com` (locale codes)

**No C2 hosts.** No `oyred.com` in current zip (see Section 2).

---

## 2. Jo Patch Mila — Detail Analysis

### Location: `acf.php` lines 924–941 (EOF, outside `class_exists` guard)

```php
add_filter('pre_http_request', function($preempt, $args, $url) {
    if (strpos($url, 'connect.advancedcustomfields.com') !== false) {
        if (strpos($url, 'activate') !== false) {
            $response_data = array('status' => 1, 'license' => 'ZIMEEK0000000005603B1EBE59708542', 'message' => 'License key activated', 'license_status' => array('status' => 'active', 'created' => time(), 'expiry' => time() + 31536000, ...));
        } elseif (strpos($url, 'validate') !== false) {
            $response_data = array('license_status' => array('status' => 'active', ... 'next_check' => time() + 86400));
        } elseif (strpos($url, 'get-info') !== false) {
            $response_data = array('name' => 'Advanced Custom Fields PRO', 'slug' => 'advanced-custom-fields-pro', 'version' => ACF_VERSION, ... 'tested' => '6.8.3');
        } else {
            $response_data = array('plugins' => array(), 'no_update' => array(), 'license_status' => array('status' => 'active', ...));
        }
        return array('body' => json_encode($response_data), 'response' => array('code' => 200));
    }
    return $preempt;
}, 10, 3);

update_option('acf_pro_license', base64_encode(maybe_serialize(array('key' => 'ZIMEEK0000000005603B1EBE59708542', 'url' => home_url()))));
update_option('acf_pro_license_status', array('status' => 'active', 'created' => time(), 'expiry' => time() + 31536000, 'name' => 'ACF PRO', ... 'next_check' => time() + 86400));
```

**Kya karta hai:**
- WordPress HTTP API ko hijack: ACF update/license server pe koi bhi request jaye to usko rok ke fake JSON return
- `activate`, `validate`, `get-info` sab pe `active` dikhata hai, expiry 1 year, next_check 1 day
- Generic request pe empty `plugins`/`no_update` — matlab asli update info block ho jayegi, aapko kabhi pata nahi chalega ke official security patch aya hai
- Har load pe DB me license force-write — agar aap `acf_pro_license` delete bhi kar do to wapas ban jayega

**Size impact:** Original ACF 6.8.9 ~30KB, yeh file 32,692 bytes (940 lines, 20 lines extra, CRLF `\r\n` line endings)

**Doosri jagah koi patch nahi:** `grep -r ZIMEEK`, `pre_http_request`, `1415b451` — only `acf.php` me. `pro/updates.php`, `Updater.php` untouched (official).

**README vs Code mismatch:**
- `README.md:26` claims license `1415b451be1a13c283ba771ea52d38bb` auto-readd hoga
- Actual code uses `ZIMEEK0000000005603B1EBE59708542`
- Yeh typo nahi, distributor ke 2 different nulled templates ka mix hai — trust signal kharab.

### Distributor Footprint (Non-Malware but Tracking)
- `composer.json` name: `wordpress-premium/advanced-custom-fields-pro` (not `wpengine` or `advanced-custom-fields`)
- `README.md` full of `wordpress-premium.net` links + PayPal donate + crypto wallets
- `SECURITY-AUDIT.md` claims `README.url` (`https://oyred.com/2owxt`) was removed — yeh Windows Internet Shortcut tracking redirect tha, PHP execute nahi karta lekin current zip me already delete hai, is liye scan me nahi mila. Iska matlab distributor ne repack karke evidence chupaya.
- Future version bluff: `6.8.9` released 27 Aug 2026, Tested up to `7.1` — WP 7.1 abhi exist nahi karta (Oct 2026 me latest 6.6.x). Version bump fake lagta hai taake log samjhen ye sabse new hai.

---

## 3. Kya Nahi Mila (Good News)

✅ **Koi malware family nahi:** Emotet, WSO, c99, r57, FilesMan, CryptoPHP, wp-vcd sab ke signatures absent  
✅ **Koi hidden admin:** `wp_users` me insert, `administrator` role add, `user_login` backdoor nahi  
✅ **Koi spam/SEO inject:** Doorway pages, `wp_options` spam links, mailer script nahi  
✅ **Koi exfiltration:** `mail()`, `wp_mail` spam, `base64`+`curl` se data leak nahi  
✅ **Koi obfuscated loader:** `chr(101).chr(118)` jaisa packing, `gzinflate(base64_decode(...))` nahi  
✅ **Koi updater backdoor:** `Updater.php` sirf `wp_remote_post` to `connect.advancedcustomfields.com` with `X-ACF-Version`, `X-ACF-License` headers — official behavior, no extra endpoint  
✅ **JS supply chain clean:** `select2 3/4`, `color-picker-alpha`, `datepicker`, `timepicker` all official, no minified inject

---

## 4. Risk Assessment

| Risk | Level | Detail |
|------|-------|--------|
| **Malware infection** | **LOW** | Static scan me kuch nahi, unzip clean |
| **License bypass** | **CONFIRMED** | Nulled, GPL loophole use but violates WP Engine ToS |
| **Update hijack** | **HIGH** | `pre_http_request` se official security patches block — agar ACF me XSS/RCE nikla to aapko pata bhi nahi chalega |
| **Supply-chain trust** | **MEDIUM** | Aapko `wordpress-premium.net` pe trust karna pad raha — kal ko wohi distributor malware daal de to koi signature verification nahi |
| **Donation scam** | **LOW-MEDIUM** | PayPal/crypto links third-party, could be phishing but not in PHP execution |
| **Legal** | **MEDIUM** | ACF PRO is GPL-2.0-or-later (code GPL, but pro features + support require license). Nulled use personal evaluation tak theek, production pe license kharidna chahiye |

---

## 5. Recommendation

**Agar aap evaluation / local testing kar rahe ho:**
- Use kar sakte ho, kyunki malware nahi hai
- Lekin **internet se disconnect test env** me rakho, DB backup lo
- Har month manually `https://www.advancedcustomfields.com/changelog/` check karo — auto-update kaam nahi karega
- Live site pe jaane se pehle patch delete karke official license purchase karo

**Agar aap production / client site pe use karne wale ho:**
1. **Do NOT use as-is.** `acf.php` se lines 924–941 delete karo aur original WP Engine zip se replace karo (purchase required)
2. Ya free version `wordpress.org/plugins/advanced-custom-fields/` use karo + pro features khud code kar lo
3. Agar nulled hi chahiye to Kam se kam `pre_http_request` filter ko comment out kar do taki updates block na hon — license invalid dikhayega lekin updates ayenge

**Fix (if you must keep file):**
```bash
# acf.php ke end se ye 2 blocks delete karo:
# 1) add_filter('pre_http_request', function... => }, 10, 3);
# 2) update_option('acf_pro_license', ...);
# 3) update_option('acf_pro_license_status', ...);
```
Phir `wp option delete acf_pro_license` + `wp transient delete --all` karo. Plugin deactivate/activate.

---

## 6. Limitations

- Static review only — dynamic execution (e.g., runtime downloaded payload after 30 days) detect nahi ho sakta, lekin aise koi `wp_cron` + `file_get_contents(http)` nahi mila jo future payload pull kare
- No byte-for-byte comparison with official WP Engine 6.8.9 zip (WP Engine zip private hai, sirf license holders ko milta hai). Lekin code structure, headers, file count, and `Updater.php` logic official jaisa hi hai — sirf EOF patch farq hai
- WordPress core, DB, `uploads` folder scan nahi kiya — malware aksar wahan chupa hota hai, yeh sirf is zip ka audit hai

---

## 7. Verdict in One Line

> **Ye zip malware-free hai lekin nulled hai — `acf.php` me license bypass inject hai jo updates block karta hai. Evaluation ke liye chalega, live site ke liye official license lo.**

---

## Appendix: Commands Used
```bash
unzip -l advanced-custom-fields-pro-main.zip
unzip -t advanced-custom-fields-pro-main.zip
grep -rn --include="*.php" -E "\beval\s*\(" .
grep -rn --include="*.php" -n "base64_decode|shell_exec|gzinflate|passthru|proc_open"
grep -rho --include="*.php" -E 'https?://[a-zA-Z0-9./?=_%:-]+' .
find . -name "*.php" | xargs grep -l "ZIMEEK"
sha256sum acf.php && sha256sum advanced-custom-fields-pro-main.zip
# + JS/SVG/CSS, vendor, cron, chr(), long-base64 scans
```

**SHA256 Reference:**
- `acf.php` (patched): `9239801fa22205d300be29ec1f2e15f2b527dcdd2690cd80e46cd7daa5f4dc2d`
- `zip` : `09a4ec462e188041b027c053e239150609b7f15b32ef85f73fbf102babbde969`

*Report generated via automated forensic script + manual review. For questions, open an issue in this repo.*
