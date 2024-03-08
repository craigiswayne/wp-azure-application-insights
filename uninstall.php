<?php
/**
 * This file is run when WordPress uninstalls the plugin
 *
 * @package craigiswayne\wp-azure-application-insights
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// TODO: find a better way to ref this!
delete_option( 'wp_azure_app_insights_option_connection_string' );
delete_site_option( 'wp_azure_app_insights_option_connection_string' );
