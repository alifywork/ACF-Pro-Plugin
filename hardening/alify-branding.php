<?php
/**
 * Alify Fields Pro - Complete Branding + License Bypass
 * Auto-loaded from acf.php if present
 * Keeps design intact - only text rebrand + seamless activation
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------
   1. LICENSE BYPASS — make ACF think it's active
   This is the REAL fix for "key mang raha hai".
   Previous version used fake filters (acf/pro/is_license_active)
   which ACF never checks. ACF checks option values:
     - acf_pro_license        = base64(serialized ['key'=>..,'url'=>..])
     - acf_pro_license_status = ['status'=>'active', ...]
   We intercept get_option via pre_option_* so no DB write needed
   and no remote call to connect.advancedcustomfields.com.
------------------------------------------------- */

// Helper to generate fake license for current site URL
if (!function_exists('alify_fake_license_value')) {
    function alify_fake_license_value() {
        // Use home_url if available, fallback to example.com during early load
        $url = function_exists('home_url') ? home_url() : (function_exists('get_option') ? get_option('home','https://example.com') : 'https://example.com');
        // Deterministic key per site so reactivation checks pass
        $key = 'ALIFY-' . strtoupper(substr(md5($url . '|alify-fields-pro|2026'), 0, 16)) . '-ACTIVE';
        $data = ['key' => $key, 'url' => $url];
        // WordPress stores as base64(maybe_serialize)
        if (function_exists('maybe_serialize')) {
            return base64_encode(maybe_serialize($data));
        }
        return base64_encode(serialize($data));
    }
    function alify_fake_license_status() {
        return [
            'status'                  => 'active',
            'created'                 => time() - 86400,
            'expiry'                  => time() + 31536000, // +1 year
            'name'                    => 'Alify Fields Pro (Active)',
            'lifetime'                => false,
            'refunded'                => false,
            'view_licenses_url'       => 'https://aapki-site.com',
            'manage_subscription_url' => 'https://aapki-site.com',
            'error_msg'               => '',
            'next_check'              => time() + 31536000, // far future → no remote validate
        ];
    }
}

// Intercept get_option BEFORE it hits DB — works even if DB has old inactive value
add_filter('pre_option_acf_pro_license', function($pre) { return alify_fake_license_value(); });
add_filter('pre_option_acf_pro_license_status', function($pre) { return alify_fake_license_status(); });
// Also filter after DB fetch to be extra safe (if pre_option missed)
add_filter('option_acf_pro_license', function($val) { return $val ?: alify_fake_license_value(); });
add_filter('option_acf_pro_license_status', function($val) {
    if (empty($val) || empty($val['status']) || $val['status'] !== 'active') return alify_fake_license_status();
    // Force next_check far future so ACF never re-validates remotely
    $val['status'] = 'active';
    $val['next_check'] = time() + 31536000;
    $val['error_msg'] = '';
    return $val;
});
// Hide any activation error transient that might show banner
add_filter('pre_transient_acf_activation_error', function(){ return []; });
add_filter('pre_site_transient_acf_activation_error', function(){ return []; });

/* -------------------------------------------------
   2. Branding — plugin name
------------------------------------------------- */
add_filter('acf/settings/name', function($name) {
    return defined('ALIFY_BRAND_NAME') ? ALIFY_BRAND_NAME : 'Alify Fields Pro';
});
add_action('acf/init', function() {
    acf_update_setting('name', defined('ALIFY_BRAND_NAME') ? ALIFY_BRAND_NAME : 'Alify Fields Pro');
}, 20);

/* -------------------------------------------------
   3. Text rebranding via gettext (exact matches only)
------------------------------------------------- */
add_filter('gettext', function($translated, $text, $domain) {
    if ($domain !== 'acf') return $translated;
    $map = [
        'Advanced Custom Fields PRO' => 'Alify Fields Pro',
        'Advanced Custom Fields' => 'Alify Fields',
        'ACF PRO' => 'Alify Pro',
        'ACF® PRO' => 'Alify Pro',
        'ACF®' => 'Alify',
        'Unlock Extra Features with ACF PRO' => 'Alify Pro Features Active',
        '4 Months Free' => 'Alify Pro',
        'Get 4 months free on any WP Engine plan' => 'Alify Fields Pro',
        'More Tools from WP Engine' => 'More Tools',
        'are developed and maintained by' => 'is developed by',
        'is developed and maintained by' => 'is developed by',
    ];
    if (isset($map[$text])) return $map[$text];
    if (isset($map[$translated])) return $map[$translated];
    return $translated;
}, 10, 3);

add_filter('gettext_with_context', function($translated, $text, $context, $domain) {
    if ($domain === 'acf') {
        if ($translated === 'ACF PRO logo') return 'Alify Pro logo';
        if ($text === 'Advanced Custom Fields logo') return 'Alify Fields logo';
    }
    return $translated;
}, 10, 4);

/* -------------------------------------------------
   4. Footer rebrand
------------------------------------------------- */
add_filter('admin_footer_text', function($text) {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'acf') !== false) {
        return '<a href="https://aapki-site.com" target="_blank">Alify Fields Pro</a> is developed by <a href="https://aapki-site.com" target="_blank">Alify</a>.';
    }
    return $text;
}, 999);

/* -------------------------------------------------
   5. Hide any leftover license nag (belt & suspenders)
------------------------------------------------- */
add_action('init', function() {
    add_filter('acf/admin/prevent_admin_notice', function($prevent, $notice) {
        $txt = $notice['text'] ?? '';
        if (strpos($txt, 'Activate your license') !== false || strpos($txt, 'license to enable') !== false) return true;
        return $prevent;
    }, 10, 2);
});

/* -------------------------------------------------
   6. Menu rename - ACF → Alify Fields
------------------------------------------------- */
add_action('admin_menu', function() {
    global $menu;
    foreach ($menu as &$item) {
        if (isset($item[0]) && $item[0] === 'ACF') $item[0] = 'Alify Fields';
    }
}, 999);

/* -------------------------------------------------
   7. Minimal CSS fix for layout (not blocking)
------------------------------------------------- */
add_action('admin_head', function() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'acf') === false) return;
    echo '<style>.acf-field-group-pro-features-title,.acf-field-group-pro-features-title-sm{white-space:normal!important;overflow:visible!important}</style>';
});
