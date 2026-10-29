<?php
/**
 * Alify Fields Pro - Complete Branding Layer
 * Auto-loaded from acf.php if present
 * Keep design intact - only text rebrand, no layout breaks
 */

if (!defined('ABSPATH')) exit;

// 1. Override plugin setting name
add_filter('acf/settings/name', function($name) {
    return defined('ALIFY_BRAND_NAME') ? ALIFY_BRAND_NAME : 'Alify Fields Pro';
});
add_action('acf/init', function() {
    acf_update_setting('name', defined('ALIFY_BRAND_NAME') ? ALIFY_BRAND_NAME : 'Alify Fields Pro');
}, 20);

// 2. Text rebranding via gettext (only exact matches, safe)
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
    // Also hide license nag by making it empty (instead of CSS hide)
    if ($text === 'Activate your license to enable access to updates, support & PRO features.' ||
        $text === 'Activate your license to enable access to updates, support &amp; PRO features.') {
        return ''; // empty = no banner
    }
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

// 3. Footer rebrand
add_filter('admin_footer_text', function($text) {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'acf') !== false) {
        return '<a href="https://aapki-site.com" target="_blank">Alify Fields Pro</a> is developed by <a href="https://aapki-site.com" target="_blank">Alify</a>.';
    }
    return $text;
}, 999);

// 4. Allow editing without license (keep functionality)
add_filter('acf/pro/is_license_active', '__return_true');
add_filter('acf/pro/is_license_expired', '__return_false');

// 5. Hide license banner cleanly (no CSS break)
add_action('init', function() {
    // Remove license error notice via store filter
    add_filter('acf/admin/prevent_admin_notice', function($prevent, $notice) {
        $txt = $notice['text'] ?? '';
        if (strpos($txt, 'Activate your license') !== false || strpos($txt, 'license to enable') !== false) {
            return true; // prevent
        }
        return $prevent;
    }, 10, 2);
});

// 6. Menu rename - keep simple
add_action('admin_menu', function() {
    global $menu;
    foreach ($menu as &$item) {
        if (isset($item[0]) && $item[0] === 'ACF') $item[0] = 'Alify Fields';
    }
}, 999);

// 7. Minimal CSS - only logo size fix, no layout hide
add_action('admin_head', function() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'acf') === false) return;
    // Only fix WP Engine logo link to point to Alify, keep layout
    echo '<style>.acf-nav-wpengine-logo img { max-height: 18px; } .acf-field-group-pro-features-footer { display: flex; justify-content: space-between; }</style>';
    // Fix truncated promo title - ensure full width
    echo '<style>.acf-field-group-pro-features-title, .acf-field-group-pro-features-title-sm { white-space: normal !important; overflow: visible !important; }</style>';
});
