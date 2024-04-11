<?php
/**
 * This file is run when WordPress uninstalls the plugin
 *
 * @package craigiswayne\wp-azure-application-insights
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

foreach(WP_Azure_Application_Insights::$settings as $setting){
    delete_option($setting['id']);
    delete_site_option($setting['id']);
}