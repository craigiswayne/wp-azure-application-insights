<?php
/**
 * Plugin Name: Azure App Insights
 * Description: Enable Azure Application Insights for your website
 * Plugin URI: https://github.com/craigiswayne/wp-azure-application-insights
 * Version: 3.3.2
 * Author: Craig Wayne
 * Author URI: https://github.com/craigiswayne/
 * Requires at least: 6.4.2
 * Requires PHP: 8.2
 * License:  MIT
 **/

const WP_AZURE_APPLICATION_INSIGHTS_PREFIX = 'wp_azure_app_insights_';

/**
 * The controller class for App Insights for both Client and Server side
 */
class WP_Azure_Application_Insights {


	// phpcs:ignore Squiz.Commenting.VariableComment.Missing
	public static string $page_id = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'page';

	// phpcs:ignore Squiz.Commenting.VariableComment.Missing
	public static string $section_id = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'section';

	// phpcs:ignore Squiz.Commenting.VariableComment.Missing
	public static string $option_group = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'option_group';

	// phpcs:ignore Squiz.Commenting.VariableComment.Missing
	public static string $option_name = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'option_connection_string';

	// phpcs:ignore Squiz.Commenting.VariableComment.Missing
	public static string $regex_connection_string = '^InstrumentationKey=[a-z\d]{8}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{12};IngestionEndpoint=https:\/\/.*;LiveEndpoint=https:\/\/.*';

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'create_menu_item' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'wp_head', array( __CLASS__, 'inject_js_snippet' ) );
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public static function create_menu_item(): void {
		add_plugins_page(
			get_plugin_data( __FILE__ )['Name'],
			get_plugin_data( __FILE__ )['Name'],
			'manage_options',
			'wp-azure-app-insights',
			array( __CLASS__, 'options_page_content' )
		);
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public static function options_page_content(): void {
		$plugin_data = get_plugin_data( __FILE__ );
		$plugin_name = wp_kses( $plugin_data['Name'], array() );
		?>
		<div class="wrap">
			<h2><?php echo wp_kses( $plugin_name, array() ); ?></h2>
			<form method="post" action="<?php echo wp_kses( admin_url( 'options.php' ), array() ); ?>">
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

	/**
	 * Set up the callbacks for the settings, sections and fields
	 *
	 * @return void
	 */
	public static function admin_init(): void {
		register_setting( self::$option_group, self::$option_name, array( __CLASS__, 'sanitize_connection_string' ) );
		add_settings_section( self::$section_id, '', '__return_false', self::$page_id );
		add_settings_field(
			self::$option_name,
			'Connection String',
			array(
				__CLASS__,
				'connection_string_field_callback',
			),
			self::$page_id,
			self::$section_id
		);
	}

	/**
	 * Ensures there is no code injection
	 *
	 * @param string $new_value The new value submitted on the options page.
	 * @return string
	 */
	public static function sanitize_connection_string( string $new_value ): string {

		if ( preg_match( '/' . self::$regex_connection_string . '/', $new_value ) ) {
			add_settings_error( self::$option_group, self::$option_name . '_regex_passed', 'Settings saved.', 'success' );
			return sanitize_text_field( $new_value );
		}

		$initial_value = get_option( self::$option_name );
		add_settings_error( self::$option_group, self::$option_name . '_regex_failed', 'Connection string: Incorrect format... reverting to initial value', 'error' );

		return $initial_value;
	}

    // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public static function section_callback(): void {
		echo '<p>Enter the Connection String to turn on the App Insights functionality</p>';
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public static function connection_string_field_callback(): void {
		$value = get_option( self::$option_name );
		echo '<input class="widefat" type="text" required pattern="' . self::$regex_connection_string . '" name="' . self::$option_name . '" value="' . esc_attr( $value ) . '" aria-describedby="' . self::$option_name . '_validation" autocomplete="false"/>';
		echo '<p class="description" id="' . self::$option_name . '_validation">Format: <code>' . self::$regex_connection_string . '</code></p>';
		echo '<p class="description" id="' . self::$option_name . '_help"><a target="_blank" href="https://learn.microsoft.com/en-us/azure/azure-monitor/app/sdk-connection-string?tabs=dotnet5#find-your-connection-string">How to find your connection string</a></p>';
	}

	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public static function inject_js_snippet(): void {
		$connection_string = get_option( self::$option_name );
		if ( ! $connection_string ) {
			return;
		}
		$raw_snippet  = file_get_contents( __DIR__ . '/javascript-snippet.html' );
		$replacements = array(
			'/YOUR_CONNECTION_STRING/' => $connection_string,
		);
		$snippet      = preg_replace( array_keys( $replacements ), array_values( $replacements ), $raw_snippet );
		echo wp_kses( $snippet, array( 'script' ) );
	}
}

WP_Azure_Application_Insights::init();