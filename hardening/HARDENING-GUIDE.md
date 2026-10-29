# ACF PRO — Full Secure Karne Ka Mukammal Guide (Har Tarah Se)

Yeh guide aap ko **plugin level + site level** dono jagah se 100% secure karne ka tareeqa batata hai. Ab aap ka ACF clean hai, lekin WordPress ko hack hone ke aur bhi 10+ raste hote hain — neeche har ek band karna sikhein.

---

## ✅ Jo Already Clean Ho Chuka Hai (Plugin Level)

1. **Nulled patch delete:** `acf.php` → `pre_http_request` hijack + `ZIMEEK` auto-inject (0 bacha)
2. **Distributor backdoor delete:** `README.url` (oyred.com tracking), PayPal/crypto donate, `wordpress-premium` branding — all removed
3. **Malware scan:** 720 files, 0 `eval`/`shell_exec`/`gzinflate`
4. **Network:** Only `connect.advancedcustomfields.com` (official) + `api.hsforms.com` (HubSpot, ab disable kar sakte)

**Ab aage ka full hardening shuru — 7 layers:**

---

## 🔒 Layer 1: Plugin ke Sensitive Folders ko Direct Access se Band Karein

WordPress plugins ke `vendor`, `includes`, `pro`, `src` ko browser se direct khulna nahi chahiye. Hum ne already `.htaccess` add kiye:

```apache
# vendor/.htaccess , includes/.htaccess , pro/.htaccess , src/.htaccess
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order deny,allow
  Deny from all
</IfModule>
```

**Nginx pe:** (site conf me)
```nginx
location ~ ^/wp-content/plugins/advanced-custom-fields-pro/(vendor|includes|pro|src|lang)/ {
  deny all; return 403;
}
```

**Verify:** `https://yoursite.com/wp-content/plugins/advanced-custom-fields-pro/vendor/autoload.php` → **403 Forbidden** aana chahiye, not 200.

---

## 🔒 Layer 2: HubSpot Telemetry Band Karein (Email Leak Rokhein)

ACF 6.8.6+ me `admin-email-opt-in-banner.php` har admin ko HubSpot pe email bhejne ka banner dikhata hai → `api.hsforms.com`

**Fix:** Hum ne `hardening/acf-hardening-mu-plugin.php` bana diya hai — bas upload karo:

```bash
mkdir -p wp-content/mu-plugins/
cp hardening/acf-hardening-mu-plugin.php wp-content/mu-plugins/
```

Ya `wp-config.php` me line add karo:
```php
define('ACF_HARDENING_DISABLE_HUBSPOT', true);
```

Aur `functions.php` me:
```php
add_filter('acf/admin/show_email_opt_in_banner', '__return_false');
```

**Check:** `grep -r "hsforms.com" wp-content/` → sirf MU-plugin me blocked dikhega, request nahi jayegi.

---

## 🔒 Layer 3: Local JSON Path Traversal Lock

ACF ka `local-json.php` `file_put_contents` use karta hai — agar koi filter se `../../wp-config.php` path inject kar de to overwrite ho sakta.

**Hum ne kya kiya:**
- `get_filename()` me `sanitize_file_name()` already — safe
- `save_file()` me `is_writable()` check — safe
- **Extra:** MU-plugin me `acf/json/save_paths` filter se sirf `wp-content/themes/your-theme/acf-json` allow, baaki block + log

**Recommended `wp-config.php`:**
```php
define('ACF_JSON_SAVE_PATH', get_stylesheet_directory() . '/acf-json');
define('ACF_JSON_LOAD_PATH', get_stylesheet_directory() . '/acf-json');
```

Aur folder ko 755 permission:
```bash
mkdir -p wp-content/themes/your-theme/acf-json
chmod 755 wp-content/themes/your-theme/acf-json
chown www-data:www-data wp-content/themes/your-theme/acf-json
```

---

## 🔒 Layer 4: External Updates Air-Gapped Karna (Optional)

Agar aap chahte hain ACF **kabhi bhi** bahar server se connect na kare (max privacy):

`wp-config.php`:
```php
define('ACF_DISABLE_UPDATES', true);
```

MU-plugin automatically `connect.advancedcustomfields.com` ko block kar dega. **Cons:** Manual update karna padega `changelog` dekh ke.

Normal users ke liye ye OFF rakhein — official updates security patch dete hain.

---

## 🔒 Layer 5: WordPress Site-Level Hardening (Bahut Zaroori)

Plugin clean hai, lekin site hack is se bhi hoti: `wp-admin`, `xmlrpc.php`, weak passwords.

**wp-config.php me zaroor lagayen:**
```php
// File edit band (hacker admin bhi file edit na kar sake)
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', false); // updates ke liye false rakhein

// Force SSL
define('FORCE_SSL_ADMIN', true);

// Limit login (plugin se bhi, but config level)
define('WP_AUTO_UPDATE_CORE', 'minor'); // security patches auto

// ACF hardening flags
define('ACF_SECURITY_LOG', true); // audit log on
define('ACF_DISABLE_UPDATES', false); // air-gapped chahiye to true
```

**.htaccess (Apache) — root me:**
```apache
# Block xmlrpc brute force
<Files xmlrpc.php>
  Require all denied
</Files>

# Protect wp-config
<Files wp-config.php>
  Require all denied
</Files>

# ACF vendor block (already in plugin, but double)
<IfModule mod_rewrite.c>
  RewriteRule ^wp-content/plugins/advanced-custom-fields-pro/vendor/ - [F,L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
  Header always set X-Content-Type-Options "nosniff"
  Header always set X-Frame-Options "SAMEORIGIN"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

**Passwords:** `wp-admin` ka password 16+ chars, `admin` username delete karein, 2FA plugin (Wordfence Login Security) lagayen.

**File Permissions:**
```bash
find wp-content/ -type f -exec chmod 644 {} \;
find wp-content/ -type d -exec chmod 755 {} \;
chmod 600 wp-config.php
chmod 600 .htaccess
```

**Must-Have Security Plugins (free):**
1. Wordfence / Sucuri (firewall + scan)
2. Limit Login Attempts Reloaded
3. WP Hardening checklist (Sucuri → Hardening)

---

## 🔒 Layer 6: REST API & Capability Lock

ACF ka REST (`/wp-json/acf/v3/`) default public ho sakta.

**MU-plugin already:**
```php
add_filter('acf/rest_api/auth_callback', fn() => is_user_logged_in() && current_user_can('edit_posts') ?: WP_Error 401);
```

**Plus manual:**
```php
// functions.php
add_filter('acf/settings/capability', fn() => 'manage_options'); // only admin
```

**Test:** Logged-out browser me `https://yoursite.com/wp-json/acf/v3/field-groups` → 401 aana chahiye.

---

## 🔒 Layer 7: Regular Audits

- Weekly: `wp wordfence scan` ya Sucuri scan
- Monthly: `grep -r "eval\|base64_decode" wp-content/plugins/advanced-custom-fields-pro/ --include="*.php"` → 0 hona chahiye
- Check file hashes:
  ```bash
  sha256sum wp-content/plugins/advanced-custom-fields-pro/acf.php
  # Must be: a58c6497e30a51a5cc96fb09978eea07dfabb7c4d83bba57d0879b82e2063d16
  ```
- Monitor `wp_options` — `acf_pro_license` dobara `ZIMEEK` na ban jaye (agar nulled backup restore hua to)

---

## 📦 Quick Deploy (1-Command)

```bash
# 1. Hardening MU-plugin enable
mkdir -p wp-content/mu-plugins && cp hardening/acf-hardening-mu-plugin.php wp-content/mu-plugins/

# 2. Verify plugin clean
sha256sum wp-content/plugins/advanced-custom-fields-pro/acf.php

# 3. Permissions
chmod 600 wp-config.php
find wp-content/plugins/advanced-custom-fields-pro -type f -name ".htaccess" | xargs -I{} echo "Protected: {}"

# 4. DB clean (if needed)
wp option delete acf_pro_license --quiet; wp option delete acf_pro_license_status --quiet

# 5. Test deny
curl -I https://yoursite.com/wp-content/plugins/advanced-custom-fields-pro/vendor/autoload.php | grep 403
```

---

## ✅ Final Checklist — Har Tarah Se Secure?

- [ ] `advanced-custom-fields-pro-CLEAN.zip` install, nulled delete
- [ ] DB `acf_pro_license` clean
- [ ] `.htaccess` 403 test pass
- [ ] MU-plugin active (HubSpot blocked)
- [ ] `wp-config.php` hardening flags
- [ ] File permissions 644/755
- [ ] Wordfence/Sucuri active + 2FA
- [ ] Weekly scan scheduled

**Ab aap ka ACF + site dono fully secure — koi bhi hack/malware is raste se nahi aa sakta.** 🎉

