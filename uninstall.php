<?php

/**
 * @see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// TODO: find a better way to ref this
delete_option('wp_azure_app_insights_option_connection_string' );
delete_site_option( 'wp_azure_app_insights_option_connection_string' );