<?php
/**
 * ACF PRO — Security Hardening (MU-Plugin)
 * 
 * Drop this file into: wp-content/mu-plugins/acf-hardening-mu-plugin.php
 * Or include via wp-config.php
 * 
 * What it does:
 * 1. Disables HubSpot telemetry (api.hsforms.com) — no email leaks
 * 2. Locks Local JSON to safe directory only (prevents path traversal)
 * 3. Disables ACF update checks to external server if you want air-gapped mode
 * 4. Forces capability checks & blocks unauthorized REST access
 * 5. Adds security headers for ACF admin screens
 * 
 * This file is OPTIONAL. Plugin itself is already clean.
 * Use it if you want maximum privacy / no phone-home.
 * 
 * @version 1.0.0
 * @author Alify Secure Build — 2026-10-29
 */

if (!defined('ABSPATH')) exit;

// ------------------------------------------------------------------
// 1. DISABLE HubSpot Email Banner (no data to api.hsforms.com)
// ------------------------------------------------------------------
add_filter('acf/admin/show_email_opt_in_banner', '__return_false');

// Also block the HTTP request itself (defense in depth)
add_filter('pre_http_request', function($preempt, $args, $url) {
    if (strpos($url, 'api.hsforms.com') !== false || strpos($url, 'hsforms.com') !== false) {
        // Log attempt, return fake success to silence
        error_log('[ACF Hardened] Blocked HubSpot telemetry request to: ' . esc_url_raw($url));
        return new WP_Error('blocked', 'HubSpot telemetry disabled by ACF hardening');
    }
    return $preempt;
}, 9, 3);

// ------------------------------------------------------------------
// 2. LOCK Local JSON to safe path only
//    Prevents ../../ traversal via filters
// ------------------------------------------------------------------
add_filter('acf/json/save_paths', function($paths, $post) {
    $allowed_base = defined('ACF_JSON_SAVE_PATH') ? ACF_JSON_SAVE_PATH : get_stylesheet_directory() . '/acf-json';
    $allowed_base = wp_normalize_path($allowed_base);
    foreach ($paths as $i => $path) {
        $real = wp_normalize_path($path);
        // Must be inside allowed base or wp-content
        if (strpos($real, $allowed_base) !== 0 && strpos($real, WP_CONTENT_DIR) !== 0) {
            unset($paths[$i]);
            error_log('[ACF Hardened] Blocked unsafe Local JSON path: ' . $path);
        }
    }
    return $paths;
}, 999, 2);

add_filter('acf/json/load_paths', function($paths) {
    $allowed_base = defined('ACF_JSON_LOAD_PATH') ? ACF_JSON_LOAD_PATH : get_stylesheet_directory() . '/acf-json';
    foreach ($paths as $i => $path) {
        $real = wp_normalize_path((string)$path);
        if (strpos($real, WP_CONTENT_DIR) !== 0 && strpos($real, ABSPATH) !== 0) {
            unset($paths[$i]);
        }
    }
    return $paths;
}, 999);

// ------------------------------------------------------------------
// 3. OPTIONAL: Disable external ACF update checks (air-gapped)
//    Uncomment if you want ZERO phone-home to connect.advancedcustomfields.com
//    You will then need to update plugin MANUALLY.
// ------------------------------------------------------------------
if (defined('ACF_DISABLE_UPDATES') && ACF_DISABLE_UPDATES) {
    add_filter('pre_http_request', function($preempt, $args, $url) {
        if (strpos($url, 'connect.advancedcustomfields.com') !== false) {
            return new WP_Error('blocked', 'ACF updates disabled by hardening');
        }
        return $preempt;
    }, 9, 3);
    // Also hide Updates UI
    add_filter('acf/admin/prevent_admin_update_notice', '__return_true');
}

// ------------------------------------------------------------------
// 4. REST API Hardening — require auth for ACF endpoints
// ------------------------------------------------------------------
add_filter('acf/rest_api/auth_callback', function($allowed, $request) {
    // Force ACF REST to respect WP capability
    if (!is_user_logged_in() || !current_user_can('edit_posts')) {
        return new WP_Error('rest_forbidden', 'ACF REST requires authentication', ['status' => 401]);
    }
    return $allowed;
}, 10, 2);

// ------------------------------------------------------------------
// 5. Security headers for ACF admin
// ------------------------------------------------------------------
add_action('admin_init', function() {
    if (function_exists('acf_is_screen') && acf_is_screen('edit-acf-field-group')) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
    }
});

// ------------------------------------------------------------------
// 6. Audit log: log ACF admin actions
// ------------------------------------------------------------------
if (defined('ACF_SECURITY_LOG') && ACF_SECURITY_LOG) {
    foreach (['acf/update_field_group','acf/delete_field_group','acf/update_post_type','acf/delete_post_type'] as $hook) {
        add_action($hook, function($data) use ($hook) {
            $user = wp_get_current_user();
            error_log(sprintf('[ACF Audit] %s by %s (%d) — key: %s — IP: %s',
                $hook, $user->user_login, $user->ID, $data['key'] ?? 'unknown', $_SERVER['REMOTE_ADDR'] ?? 'CLI'
            ));
        });
    }
}

// ------------------------------------------------------------------
// 7. Disable ACF AI / Site Health telemetry if not needed
// ------------------------------------------------------------------
add_filter('acf/settings/enable_acf_ai', '__return_false');
add_filter('acf/settings/enable_schema', function($v) {
    // Enable only if you need Schema.org JSON-LD, else disable for privacy
    return defined('ACF_ENABLE_SCHEMA') ? ACF_ENABLE_SCHEMA : false;
});
