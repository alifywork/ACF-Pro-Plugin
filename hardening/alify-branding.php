<?php
/**
 * Alify Fields Pro - Complete Branding Layer
 * Auto-loaded from acf.php if present
 */
if (!defined('ABSPATH')) exit;
add_filter('acf/settings/name', function($name) {
    return defined('ALIFY_BRAND_NAME') ? ALIFY_BRAND_NAME : 'Alify Fields Pro';
});
add_action('acf/init', function() {
    acf_update_setting('name', defined('ALIFY_BRAND_NAME') ? ALIFY_BRAND_NAME : 'Alify Fields Pro');
}, 20);
add_filter('gettext', function($translated, $text, $domain) {
    if ($domain !== 'acf') return $translated;
    $map = [
        'Advanced Custom Fields PRO' => 'Alify Fields Pro',
        'Advanced Custom Fields' => 'Alify Fields',
        'ACF PRO' => 'Alify Pro',
        'ACF® PRO' => 'Alify Pro',
        'ACF®' => 'Alify',
        'Activate your license to enable access to updates, support & PRO features.' => 'Alify Fields Pro is active. Enjoy all PRO features!',
        'Activate your license to enable access to updates, support &amp; PRO features.' => 'Alify Fields Pro is active.',
        'Unlock Extra Features with ACF PRO' => 'Alify Pro Features Active',
        '4 Months Free' => 'Alify Pro',
        'Get 4 months free on any WP Engine plan' => 'Alify Fields Pro',
        'More Tools from WP Engine' => 'More Tools',
        'are developed and maintained by' => 'is developed by',
        'is developed and maintained by' => 'is developed by',
    ];
    if (isset($map[$text])) return $map[$text];
    if (isset($map[$translated])) return $map[$translated];
    $search = ['Advanced Custom Fields PRO', 'Advanced Custom Fields', 'ACF PRO', 'WP Engine'];
    $replace = ['Alify Fields Pro', 'Alify Fields', 'Alify Pro', 'Alify'];
    foreach ($search as $i => $s) {
        if (strpos($translated, $s) !== false) $translated = str_replace($s, $replace[$i], $translated);
    }
    return $translated;
}, 10, 3);
add_filter('gettext_with_context', function($translated, $text, $context, $domain) {
    if ($domain === 'acf' && $translated === 'ACF PRO logo') return 'Alify Pro logo';
    return $translated;
}, 10, 4);
add_filter('admin_footer_text', function($text) {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'acf') !== false) {
        return '<a href="https://aapki-site.com" target="_blank">Alify Fields Pro</a> is developed by <a href="https://aapki-site.com" target="_blank">Alify</a>.';
    }
    return $text;
}, 999);
add_action('admin_init', function() {
    add_action('current_screen', function() {
        foreach (['ACF_PRO', 'acf_pro'] as $cls) {
            $inst = acf_get_instance($cls);
            if ($inst && method_exists($inst, 'maybe_show_license_status_error')) {
                remove_action('acf/in_admin_header', [$inst, 'maybe_show_license_status_error']);
            }
        }
        add_filter('acf/admin/prevent_admin_notice', function($prevent, $notice) {
            if (strpos($notice['text'] ?? '', 'Activate your license') !== false) return true;
            return $prevent;
        }, 10, 2);
    }, 999);
});
add_filter('acf/pro/is_license_active', '__return_true');
add_filter('acf/pro/is_license_expired', '__return_false');
add_action('admin_head', function() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'acf') === false) return;
    echo '<style>.acf-wp-engine, .acf-nav-wpengine-logo, .acf-field-group-pro-features-wpengine-logo {display:none !important;} .acf-admin-notice.notice-warning {display:none !important;}</style>';
});
add_action('admin_menu', function() {
    global $menu;
    foreach ($menu as &$item) { if (isset($item[0]) && $item[0] === 'ACF') $item[0] = 'Alify Fields'; }
}, 999);
