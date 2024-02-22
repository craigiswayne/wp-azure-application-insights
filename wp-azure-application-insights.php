<?php
/*
Plugin Name: Azure Application Insights
Description: Enable Azure Application Insights for your website
Version: 3.2.4
Author: Craig Wayne
Author URI: https://github.com/craigiswayne/
License:  MIT
*/


// Create the admin page
function azure_app_insights_settings_page() {
	add_options_page(
		'Azure Application Insights',
		'Azure App Insights Plugin Settings',
		'manage_options',
		'azure-app-insights',
		'azure_app_insights_settings_page_content'
	);
}

function azure_app_insights_settings_page_content() {
	?>
	<div class="wrap">
		<h2>My Plugin Settings</h2>
		<form method="post" action="options.php">
			<?php
			// Output security fields for the registered setting "my_plugin_options"
			settings_fields('my_plugin_options');

			// Output setting sections and their fields
			do_settings_sections('my-plugin-settings');

			// Submit button
			submit_button();
			?>
		</form>
	</div>
	<?php
}

function my_plugin_init() {
	// Register a setting and its sanitization callback
	register_setting('my_plugin_options', 'my_plugin_option', 'sanitize_callback');

	// Add a section for our settings
	add_settings_section('my_plugin_section', 'My Plugin Settings Section', 'my_plugin_section_callback', 'my-plugin-settings');

	// Add a field for our setting
	add_settings_field('my_plugin_field', 'My Plugin Setting', 'my_plugin_field_callback', 'my-plugin-settings', 'my_plugin_section');
}
add_action('admin_init', 'my_plugin_init');

function my_plugin_section_callback() {
	echo '<p>This is a section description.</p>';
}

function my_plugin_field_callback() {
	$value = get_option('my_plugin_option');
	echo '<input type="text" name="my_plugin_option" value="' . esc_attr($value) . '" />';
}

function sanitize_callback($value) {
	// Sanitize the value before saving
	return sanitize_text_field($value);
}


add_action('admin_menu', 'azure_app_insights_settings_page');

register_deactivation_hook( __FILE__, function($network_deactivating){
	// remove settings
	delete_option('my_plugin_option');
	return;
} );