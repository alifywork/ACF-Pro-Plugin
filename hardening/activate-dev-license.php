<?php
/**
 * ACF PRO — Dev License Activator (SAFE local only, no remote hijack)
 * 
 * Use: php activate-dev-license.php --url=https://yoursite.com
 * Or: WP Admin → Tools → ACF Dev License (if loaded as plugin)
 * Or: WP-CLI → wp eval-file hardening/activate-dev-license.php
 * 
 * What it does: Sets acf_pro_license + acf_pro_license_status in DB to ACTIVE
 * for evaluation. Does NOT intercept connect.advancedcustomfields.com 
 * (unlike nulled hack). You can delete these options anytime to revert.
 * 
 * WARNING: For evaluation only. Buy official license for production:
 * https://www.advancedcustomfields.com/pro/
 * 
 * To deactivate: wp option delete acf_pro_license && wp option delete acf_pro_license_status
 */

if (!defined('ABSPATH') && php_sapi_name() !== 'cli') {
    die('Run via WP-CLI, wp-config include, or wp-admin. See top comment.');
}

// Allow CLI URL override: php activate-dev-license.php --url=https://example.com
$cli_url = null;
if (isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--url=') === 0) {
            $cli_url = substr($arg, 6);
        }
    }
}

function acf_dev_activate($url = null) {
    if (!function_exists('home_url')) {
        $dir = __DIR__;
        for ($i=0; $i<5; $i++) {
            if (file_exists($dir . '/wp-load.php')) { require $dir . '/wp-load.php'; break; }
            if (file_exists($dir . '/../wp-load.php')) { require $dir . '/../wp-load.php'; break; }
            $dir = dirname($dir);
            if ($dir === '/') break;
        }
        if (!function_exists('home_url')) {
            $url = $url ?: 'https://example.com';
            fwrite(STDERR, "WP not loaded. Use: wp eval-file hardening/activate-dev-license.php\n");
            return false;
        }
    }

    $home = $url ?: home_url();
    $dev_key = 'DEV-' . substr(md5($home . 'acf-dev-2026'), 0, 12) . '-EVAL';
    
    if (!function_exists('maybe_serialize')) {
        require_once ABSPATH . 'wp-includes/functions.php';
    }
    
    $license_data = array(
        'key' => $dev_key,
        'url' => $home,
    );
    
    $encoded = base64_encode(maybe_serialize($license_data));
    update_option('acf_pro_license', $encoded);
    
    $status = array(
        'status'                 => 'active',
        'created'                => time(),
        'expiry'                 => time() + 31536000,
        'name'                   => 'ACF PRO (DEV — evaluation only)',
        'lifetime'               => false,
        'refunded'               => false,
        'view_licenses_url'      => 'https://www.advancedcustomfields.com/pro/',
        'manage_subscription_url'=> 'https://www.advancedcustomfields.com/pro/',
        'error_msg'              => '',
        'next_check'             => time() + 86400,
    );
    update_option('acf_pro_license_status', $status);
    
    delete_transient('acf_activation_error');
    delete_transient('acf_plugin_updates');
    delete_transient('acf_plugin_info_pro');
    
    return array('key' => $dev_key, 'url' => $home, 'status' => $status);
}

function acf_dev_deactivate() {
    delete_option('acf_pro_license');
    delete_option('acf_pro_license_status');
    delete_transient('acf_activation_error');
    return true;
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $action = $argv[1] ?? 'activate';
    if ($action === 'deactivate' || $action === '--deactivate') {
        if (acf_dev_deactivate()) {
            echo "✅ Dev license removed. ACF will show 'Activate license' notice.\n";
        }
    } else {
        $res = acf_dev_activate($cli_url);
        if ($res) {
            echo "✅ Dev license activated (local only, no remote hijack):\n";
            echo "   Key: {$res['key']}\n";
            echo "   URL: {$res['url']}\n";
            echo "   Expiry: " . date('Y-m-d', $res['status']['expiry']) . "\n";
            echo "   Note: For evaluation only. Buy license: https://www.advancedcustomfields.com/pro/\n";
            echo "   To deactivate: php hardening/activate-dev-license.php deactivate\n";
        }
    }
    exit;
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('acf dev-activate', function($args, $assoc) {
        $url = $assoc['url'] ?? null;
        $res = acf_dev_activate($url);
        if ($res) WP_CLI::success("Dev license activated: {$res['key']} for {$res['url']}");
    });
    WP_CLI::add_command('acf dev-deactivate', function() {
        acf_dev_deactivate();
        WP_CLI::success("Dev license removed");
    });
}
