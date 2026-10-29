<?php
/**
 * ACF PRO — Hide License Nag
 * Drop into: wp-content/mu-plugins/hide-license-nag.php
 * Proper bypass via pre_option (ACF checks options, not fake filter)
 */
if (!defined('ABSPATH')) exit;
add_filter('pre_option_acf_pro_license', function($pre){
    $url = function_exists('home_url') ? home_url() : get_option('home','https://example.com');
    return base64_encode(maybe_serialize(['key'=>'HIDE-NAG-'.substr(md5($url),0,8),'url'=>$url]));
});
add_filter('pre_option_acf_pro_license_status', function($pre){
    return ['status'=>'active','created'=>time(),'expiry'=>time()+31536000,'name'=>'ACF PRO (Nag Hidden)','lifetime'=>false,'refunded'=>false,'view_licenses_url'=>'','manage_subscription_url'=>'','error_msg'=>'','next_check'=>time()+31536000];
});
