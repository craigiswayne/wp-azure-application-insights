<?php
if(!defined('ABSPATH') || !defined('WP_AZURE_APPLICATION_INSIGHTS_PLUGIN_PATH')){
    die;
}

$legacy_option_name = 'wp_azure_app_insights_option_connection_string';
$legacy_option_value = get_option($legacy_option_name);
if(!$legacy_option_value){
    echo 'No Legacy options found';
    return;
}

delete_option($legacy_option_name);

$instrumentation_key = get_option('wpaai_instrumentation_key');
$ingestion_endpoint = get_option('wpaai_ingestion_endpoint');
$live_endpoint = get_option('wpaai_live_endpoint');

/**
 * If any of the new options have values... bounce
 */
if(!!$instrumentation_key || !!$ingestion_endpoint || !!$live_endpoint){
    echo 'You already have values for the new options';
    return;
}

$regex_matches = null;
$re = '/InstrumentationKey=(.*);IngestionEndpoint=(.*);LiveEndpoint=(.*)/m';
preg_match_all($re, $legacy_option_value, $regex_matches, PREG_SET_ORDER, 0);

if(!$regex_matches || count($regex_matches) == 0) {
    echo 'Your legacy option value is invalid';
    return;
}

update_option('wpaai_instrumentation_key', $regex_matches[0][1]);
update_option('wpaai_ingestion_endpoint', $regex_matches[0][2]);
update_option('wpaai_live_endpoint', $regex_matches[0][3]);
echo 'Migrated legacy options';