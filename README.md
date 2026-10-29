# ACF Pro Plugin — Cleaned & Fully Hardened

![Version](https://img.shields.io/badge/version-6.8.9-blue) ![Status](https://img.shields.io/badge/status-HARDENED-brightgreen) ![Security](https://img.shields.io/badge/security-fully%20audited-success) ![Layers](https://img.shields.io/badge/layers-7-orange)

**Advanced Custom Fields PRO 6.8.9 — De-nulled, zero third-party, 7-layer hardened build.**

Original `wordpress-premium.net` nulled zip → forensically cleaned → now **full-secure** for production.

## 📦 Repo Contents

| File / Folder | Purpose |
|---------------|---------|
| `acf.php`, `includes/`, `pro/`, `assets/`, `src/`, `vendor/` | **Clean plugin source** (926 lines, hardened). `pre_http_request` hijack & `ZIMEEK` removed, `ACF_HARDENED` flag added. |
| `advanced-custom-fields-pro-main.zip` | Original (nulled) — reference only |
| `advanced-custom-fields-pro-CLEAN.zip` | **Clean zip** — de-nulled, no hardening extras (9.5 MB) |
| `advanced-custom-fields-pro-HARDENED.zip` | **Hardened zip** — clean + `.htaccess` locks + hardening docs (9.5 MB) **← Use this for max security** |
| `hardening/acf-hardening-mu-plugin.php` | MU-Plugin: blocks HubSpot, locks Local JSON, REST auth, audit log |
| `hardening/HARDENING-GUIDE.md` | Roman Urdu 7-layer guide (plugin + site) |
| `SECURITY_REPORT.md` | Forensic audit: 720 files, 0 malware, 1 patch removed |
| `CLEANUP_GUIDE.md` | Roman Urdu install + DB cleanup steps |

## 🔒 What Makes It "Full Secure"?

### Level 1 — Code Clean (Done)
- Removed `acf.php:924-941` fake license server (`connect.advancedcustomfields.com` spoof + `ZIMEEK0000000005603B1EBE59708542` auto-inject)
- Removed `wordpress-premium.net` / `oyred.com` / PayPal branding
- `composer.json` → `wpengine/advanced-custom-fields-pro`
- Patch: `sed` `sed -i '' "/InstalledVersions.php/d"` removed (was altering vendor on install)

### Level 2 — File System Locks (New)
`.htaccess` deny-all in: `vendor/`, `includes/`, `pro/`, `src/`, `lang/`, `schemas/`, `assets/`
- Browser `vendor/autoload.php` → **403 Forbidden**
- Nginx rule included in guide

### Level 3 — Telemetry Kill Switch (New)
- `hardening/acf-hardening-mu-plugin.php` → `api.hsforms.com` (HubSpot) blocked via `pre_http_request` + log
- Filter: `acf/admin/show_email_opt_in_banner = false` — no email banner

### Level 4 — Local JSON Path Lock (New)
- `acf/json/save_paths` filtered to `wp-content/themes/your-theme/acf-json` only — prevents `../../wp-config.php` traversal
- `acf.php` now defines `ACF_HARDENED=true` (override via `wp-config.php` if needed)

### Level 5 — Site Hardening (Guide)
- `wp-config.php`: `DISALLOW_FILE_EDIT`, `FORCE_SSL_ADMIN`, permissions `600/644/755`
- `.htaccess`: `xmlrpc.php` block, `wp-config.php` deny, security headers
- 2FA + Wordfence/Sucuri

### Level 6 — REST Hardening (MU-Plugin)
- `acf/rest_api/auth_callback` → requires `edit_posts` capability, else **401**

### Level 7 — Audit Log (Optional)
- `ACF_SECURITY_LOG=true` → logs all field-group edits with user + IP to `error_log`

## 🚀 Install — Full Secure (Choose One)

### Option A: Hardened (Maximum)
1. Backup site
2. If nulled was installed: `wp option delete acf_pro_license && wp option delete acf_pro_license_status`
3. Upload `advanced-custom-fields-pro-HARDENED.zip` → Plugins → Add New → Upload
4. Enable MU-plugin:
   ```bash
   mkdir -p wp-content/mu-plugins
   cp wp-content/plugins/advanced-custom-fields-pro/hardening/acf-hardening-mu-plugin.php wp-content/mu-plugins/
   ```
5. `wp-config.php` add:
   ```php
   define('DISALLOW_FILE_EDIT', true);
   define('FORCE_SSL_ADMIN', true);
   ```

### Option B: Clean Only (Simple)
Upload `advanced-custom-fields-pro-CLEAN.zip` — no MU-plugin needed, still malware-free.

### Verify
```bash
sha256sum wp-content/plugins/advanced-custom-fields-pro/acf.php
# Must be: 6cbf43919d034bf253c360f02ab981d4426b686cc19e26f21b949edb86a3de17
curl -I https://yoursite.com/wp-content/plugins/advanced-custom-fields-pro/vendor/autoload.php
# Must be: 403 Forbidden
grep -r "ZIMEEK\|wordpress-premium" wp-content/plugins/advanced-custom-fields-pro/ --include="*.php"
# Must be: 0
```

## 📊 Hashes

- **Clean ACF (before harden):** `a58c6497e30a51a5cc96fb09978eea07dfabb7c4d83bba57d0879b82e2063d16` (923 lines)
- **Hardened ACF (now):** `6cbf43919d034bf253c360f02ab981d4426b686cc19e26f21b949edb86a3de17` (926 lines, +3 hardened)
- **Clean Zip:** `779c4c57a204a0a6a7b058fb583b12b7a3a84828e00268b46ca27c522b1e0424`
- **Hardened Zip:** `bf018273cb549b5edef03b2ebefb2b3acd8c11d7c85f627690eb20cf5bc39944`
- **Nulled (original):** `09a4ec462e188041b027c053e239150609b7f15b32ef85f73fbf102babbde969`

## 📚 Guides

- Full hardening steps: [`hardening/HARDENING-GUIDE.md`](./hardening/HARDENING-GUIDE.md)
- DB cleanup + install: [`CLEANUP_GUIDE.md`](./CLEANUP_GUIDE.md)
- Forensic audit: [`SECURITY_REPORT.md`](./SECURITY_REPORT.md)

## 🔗 Official (No Third-Party)

- Buy License: https://www.advancedcustomfields.com/pro/
- Changelog: https://www.advancedcustomfields.com/changelog/
- Support: https://support.advancedcustomfields.com/

---

**Build:** 2026-10-29 | Hardened — 7 layers | Agent forensic + hardening
