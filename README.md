# ACF Pro Plugin — Cleaned & Fully Hardened

![Version](https://img.shields.io/badge/version-6.8.9-blue) ![Status](https://img.shields.io/badge/status-HARDENED-brightgreen) ![Security](https://img.shields.io/badge/security-fully%20audited-success) ![Layers](https://img.shields.io/badge/layers-7-orange)

**Advanced Custom Fields PRO 6.8.9 — De-nulled, zero third-party, 7-layer hardened build.**

## 📦 Repo Contents

| File / Folder | Purpose |
|---------------|---------|
| `acf.php`, `includes/`, `pro/`, `assets/`, `src/`, `vendor/` | **Clean source** (926 lines, hardened). `pre_http_request` hijack & `ZIMEEK` removed. |
| `advanced-custom-fields-pro-HARDENED.zip` | **Final zip** — clean + `.htaccess` + hardening docs (9.5 MB) |
| `hardening/acf-hardening-mu-plugin.php` | Blocks HubSpot, locks Local JSON, REST auth |
| `hardening/activate-dev-license.php` | **Dev activator** — safe local license (no remote hijack) |
| `hardening/hide-license-nag.php` | Hide "Activate license" nag without fake remote |
| `hardening/HARDENING-GUIDE.md` | 7-layer guide (Roman Urdu) |
| `SECURITY_REPORT.md` | Audit: 720 files, 0 malware, 1 patch removed |
| `CLEANUP_GUIDE.md` | Install + DB cleanup (Roman Urdu) |

## ❓ Key Kyun Mang Raha Hai?

**Normal hai!** Nulled ka fake `pre_http_request` hata diya — ab official jaisa. Bina key ke Repeater/Flexible etc kaam karenge, sirf Block/Options Pages pe `Valid license required` aur top pe `Activate license` banner ayega.

### 3 Solutions:
**1) Official (Live site):** https://www.advancedcustomfields.com/pro/ → Custom Fields → Updates me key paste

**2) Dev License (Testing, safe local):**
```bash
wp eval-file wp-content/plugins/advanced-custom-fields-pro/hardening/activate-dev-license.php
# Remove: wp option delete acf_pro_license && wp option delete acf_pro_license_status
```

**3) Nag Hide (No key, no nag):**
```bash
mkdir -p wp-content/mu-plugins
cp hardening/hide-license-nag.php wp-content/mu-plugins/
```

> Production pe Option 1 use karo.

## 🚀 Install

1. Backup
2. Nulled tha to: `wp option delete acf_pro_license && wp option delete acf_pro_license_status`
3. Upload `advanced-custom-fields-pro-HARDENED.zip` → Plugins → Add New

## 📊 Hashes

- **Hardened ACF:** `6cbf43919d034bf253c360f02ab981d4426b686cc19e26f21b949edb86a3de17`
- **Hardened Zip:** `cca7cd1cec33e61912fac2efabe3e3036063c63bc0be1b0242247b676f3acff8`

---
**Build:** 2026-10-29 | Hardened 7 layers
