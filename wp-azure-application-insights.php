<?php
/*
Plugin Name: Azure App Insights
Description: Enable Azure Application Insights for your website
Plugin URI: https://github.com/craigiswayne/wp-azure-application-insights
Version: 3.2.4
Author: Craig Wayne
Author URI: https://github.com/craigiswayne/
Requires at least: 6.4.2
Requires PHP: 8.2
License:  MIT
*/

const WP_AZURE_APPLICATION_INSIGHTS_PREFIX = 'wp_azure_app_insights_';

class AzureApplicationInsights {
	public static string $page_id = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'page';
	public static string $section_id = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'section';
	public static string $option_group = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'option_group';
	public static string $option_name = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'option_connection_string';
	public static string $regex_connection_string = 'InstrumentationKey=[a-z\d]{8}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{12};IngestionEndpoint=https:\/\/.*;LiveEndpoint=https:\/\/';


	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'create_menu_item' ] );
		register_deactivation_hook( __FILE__, [ __CLASS__, 'delete_options' ] );
		add_action( 'admin_init', [ __CLASS__, 'admin_init' ] );
		add_action( 'wp_head', [ __CLASS__, 'add_js_snippet' ] );
	}

	public static function create_menu_item(): void {
		add_plugins_page(
			get_plugin_data( __FILE__ )['Name'],
			get_plugin_data( __FILE__ )['Name'],
			'manage_options',
			'wp-azure-app-insights',
			[ __CLASS__, 'options_page_content' ]
		);
	}

	public static function options_page_content(): void {
		$plugin_data = get_plugin_data( __FILE__ );
		?>
        <div class="wrap">
            <h2><?= $plugin_data['Name']; ?></h2>
            <form method="post" action="options.php">
				<?php
				settings_errors( self::$option_group );
				settings_fields( self::$option_group );
				do_settings_sections( self::$page_id );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public static function delete_options( $network_deactivating ): void {
		delete_option( self::$option_name );
	}

	public static function admin_init(): void {
		register_setting( self::$option_group, self::$option_name, [ __CLASS__, 'sanitize_connection_string' ] );
//		add_settings_section(self::$section_id, 'Settings', [__CLASS__,'section_callback'], self::$page_id);
		add_settings_section( self::$section_id, '', '__return_false', self::$page_id );
		add_settings_field( self::$option_name, 'Connection String', [
			__CLASS__,
			'connection_string_field_callback'
		], self::$page_id, self::$section_id );
	}

	public static function sanitize_connection_string( $new_value ): string {

		if ( preg_match( '/' . self::$regex_connection_string . '/', $new_value ) ) {
			return sanitize_text_field( $new_value );
		}

		$original_value = get_option( self::$option_name );
		add_settings_error( self::$option_group, self::$option_name . '_regex_failed', 'Connection string: Incorrect format... reverting to original value', 'error' );

		return $original_value;
	}


	public static function section_callback(): void {
		echo '<p>Enter the Connection String to turn on the App Insights functionality</p>';
	}

	public static function connection_string_field_callback(): void {
		$value = get_option( self::$option_name );
		echo '<input class="widefat" type="text" required pattern="' . self::$regex_connection_string . '" name="' . self::$option_name . '" value="' . esc_attr( $value ) . '" aria-describedby="' . self::$option_name . '_validation" autocomplete="false"/>';
		echo '<p class="description" id="' . self::$option_name . '_validation">Format: <code>' . self::$regex_connection_string . '</code></p>';
		echo '<p class="description" id="' . self::$option_name . '_help"><a target="_blank" href="https://learn.microsoft.com/en-us/azure/azure-monitor/app/sdk-connection-string?tabs=dotnet5#find-your-connection-string">How to find your connection string</a></p>';
	}

	public static function add_js_snippet(): void {
		$connectionString = get_option( self::$option_name );
		if ( ! $connectionString ) {
			return;
		}
		$raw_snippet = file_get_contents( __DIR__ . '/javascript-snippet.html' );
		$replacements = [
			'/YOUR_CONNECTION_STRING/' => $connectionString
		];
		$snippet = preg_replace( array_keys( $replacements ), array_values( $replacements ), $raw_snippet );
		echo $snippet;
	}
}

AzureApplicationInsights::init();

// TODO: register shutdown hook
// TODO: dashboard widget for the app insights api