<?php
/*
Plugin Name: Azure Application Insights
Description: Enable Azure Application Insights for your website
Version: 3.2.4
Author: Craig Wayne
Author URI: https://github.com/craigiswayne/
Requires at least: 6.4.2
Requires PHP: 8.2
License:  MIT
*/

class AzureApplicationInsights {
    public static string $page_id = 'az_app_insights_my-plugin-settings';
    public static string $section_id = 'az_app_insights_my_plugin_section';
    public static string $option_group = 'az_app_insights_options';
	public static string $option_name = 'az_app_insights_options_connection_string';


	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'create_menu_item' ] );
		register_deactivation_hook( __FILE__, [ __CLASS__, 'delete_options' ] );
		add_action('admin_init', [__CLASS__, 'admin_init']);
        add_action('wp_head', [__CLASS__,'add_js_snippet']);
	}

	public static function create_menu_item(): void {
		add_plugins_page(
			'Azure Application Insights',
			'Azure App Insights',
			'manage_options',
			'azure-app-insights',
			[__CLASS__, 'options_page_content']
		);
	}

	public static function options_page_content(): void {
        $plugin_data = get_plugin_data(__FILE__);
		?>
		<div class="wrap">
			<h2><?= $plugin_data['Name']; ?></h2>
			<form method="post" action="options.php">
				<?php
                    settings_fields(self::$option_group);
                    do_settings_sections(self::$page_id);
                    submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public static function sanitize_option($value): string {
		return sanitize_text_field($value);
	}

	public static function delete_options($network_deactivating): void {
		delete_option(self::$option_name);
	}

	public static function admin_init(): void {
		register_setting(self::$option_group, self::$option_name, [__CLASS__, 'sanitize_option']);
		add_settings_section(self::$section_id, 'Settings', [__CLASS__,'section_callback'], self::$page_id);
		add_settings_field('az_app_insights_connection_string', 'Connection String', [__CLASS__, 'connection_string_field_callback'], self::$page_id, self::$section_id);
	}


	public static function section_callback(): void {
		echo '<p>Enter the Connection String to turn on the App Insights functionality</p>';
	}

	public static function connection_string_field_callback(): void {
		$value = get_option(self::$option_name);
		echo '<input class="widefat" type="text" name="'.self::$option_name.'" value="'.esc_attr($value).'" />';
	}

    public static function add_js_snippet(): void {
        $connectionString  = get_option(self::$option_name);
        if(!$connectionString){
            return;
        }
        $raw_snippet = file_get_contents(__DIR__.'/javascript-snippet.html');
        $replacements = [
            '/YOUR_CONNECTION_STRING/' => $connectionString
        ];
        $snippet = preg_replace(array_keys($replacements), array_values($replacements), $raw_snippet);
        echo $snippet;
    }
}

AzureApplicationInsights::init();

// TODO: register shutdown hook
// TODO: validate the value of the connection string