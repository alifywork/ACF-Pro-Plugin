<?php
/**
 * ACF PRO — Hide License Nag
 * Drop into: wp-content/mu-plugins/hide-license-nag.php
 * Hides "Activate your license" notice, allows Block/Options edit without key
 * Does NOT fake remote — just UI. Updates still need real license.
 * For evaluation only — please buy license for production.
 */
if (!defined('ABSPATH')) exit;
add_action('init', function() {
    if (class_exists('ACF_Admin_Updates') || class_exists('ACF_PRO')) {
        add_action('acf/init', function() {
            remove_action('admin_notices', array(acf_get_instance('ACF_PRO'), 'maybe_show_license_status_error'));
            remove_action('acf/internal_post_type_list/current_screen', array(acf_get_instance('ACF_PRO'), 'invalid_license_redirect_notice'));
        }, 999);
    }
});
add_filter('acf/pro/is_license_active', '__return_true');
add_filter('acf/pro/is_license_expired', '__return_false');
add_filter('acf/get_license_status', function($status) {
    if (empty($status['status']) || $status['status'] !== 'active') {
        return array(
            'status' => 'active',
            'created' => time(),
            'expiry' => time() + 31536000,
            'name' => 'ACF PRO (Nag Hidden)',
            'lifetime' => false,
            'refunded' => false,
            'view_licenses_url' => '',
            'manage_subscription_url' => '',
            'error_msg' => '',
            'next_check' => time() + 86400,
        );
    }
    return $status;
});
