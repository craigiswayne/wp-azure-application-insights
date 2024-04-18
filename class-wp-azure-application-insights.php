<?php
/**
 * Plugin Name: Azure App Insights
 * Description: Azure Application Insights in WordPress
 * Plugin URI: https://github.com/craigiswayne/wp-azure-application-insights
 * Version: 3.3.24
 * Author: Craig Wayne
 * Author URI: https://github.com/craigiswayne/
 * Requires at least: 6.4.2
 * Requires PHP: 8.2
 * License:  MIT
 **/

use ApplicationInsights\Telemetry_Client;

const WP_AZURE_APPLICATION_INSIGHTS_PREFIX = 'wp_azure_app_insights_';
define('WP_AZURE_APPLICATION_INSIGHTS_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * The controller class for App Insights for both Client and Server side
 */
class WP_Azure_Application_Insights
{

    public static array $settings = [
        [
            'id' => 'wpaai_instrumentation_key',
            'label' => 'Instrumentation Key',
            'pattern' => '[a-z\d]{8}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{12}'
        ],
        [
            'id' => 'wpaai_ingestion_endpoint',
            'label' => 'Ingestion Endpoint',
            'pattern' => 'https:\/\/(.*)\.in\.applicationinsights\.azure\.com\/$'
        ],
        [
            'id' => 'wpaai_live_endpoint',
            'label' => 'Live Endpoint',
            'pattern' => 'https:\/\/[\w.]+\.livediagnostics\.monitor\.azure\.com\/$'
        ],
        [
            'id' => 'wpaai_application_id',
            'label' => 'Application ID',
            'pattern' => '[a-z\d]{8}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{12}'
        ]
    ];

    public static array $events_tracked = [
        [
            'hook_name' => 'wp_login',
            'callback' => 'on_login_success',
            'num_of_args' => 2
        ],
        [
            'hook_name' => 'wp_logout',
            'callback' => 'on_logout',
            'num_of_args' => 1
        ],
        [
            'hook_name' => 'wp_login_failed',
            'callback' => 'on_login_failed',
            'num_of_args' => 2
        ],
        [
            'hook_name' => 'activate_plugin',
            'callback' => 'on_plugin_activate',
            'num_of_args' => 2
        ],
        [
            'hook_name' => 'deactivated_plugin',
            'callback' => 'on_plugin_deactivate',
            'num_of_args' => 2
        ],
        [
            'hook_name' => 'deleted_plugin',
            'callback' => 'on_plugin_delete',
            'num_of_args' => 2
        ],
        [
            'hook_name' => 'upgrader_process_complete',
            'callback' => 'on_upgrade_complete',
            'num_of_args' => 2
        ],
        [
            'hook_name' => 'register_new_user',
            'callback' => 'on_user_register',
            'num_of_args' => 1
        ],
        [
            'hook_name' => 'deleted_theme',
            'callback' => 'on_theme_delete',
            'num_of_args' => 1
        ],
        [
            'hook_name' => 'after_switch_theme',
            'callback' => 'on_theme_switch',
            'num_of_args' => 1
        ],
        [
            'hook_name' => 'shutdown',
            'callback' => 'on_shutdown',
            'num_of_args' => 0
        ],
        [
            'hook_name' => 'loop_end',
            'callback' => 'on_search',
            'num_of_args' => 1
        ]
    ];

    public static array $file_data;

    public static string $page_id = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'page';

    public static string $section_id = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'section';

    public static string $option_group = WP_AZURE_APPLICATION_INSIGHTS_PREFIX . 'option_group';

    public static Telemetry_Client $_telemetry_client;

    public static function init(): void
    {
        self::$_telemetry_client = self::$_telemetry_client ?? new Telemetry_Client();
        $context = self::$_telemetry_client->getContext();
        $instrumentation_key = get_option('wpaai_instrumentation_key');
        $context->setInstrumentationKey($instrumentation_key);
        $context->getLocationContext()->setIp($_SERVER['REMOTE_ADDR']);

        self::listen_for_events();

        add_action('admin_menu', array(__CLASS__, 'create_menu_item'));
        add_action('admin_init', array(__CLASS__, 'admin_init'));
        add_action('wp_head', array(__CLASS__, 'inject_js_snippet'));
    }

    public static function file_data(): array {
        return self::$file_data ?? self::$file_data = get_plugin_data(__FILE__);
    }

    public static function listen_for_events(): void
    {
        foreach (self::$events_tracked as $event_data) {
            add_action($event_data['hook_name'], [__CLASS__, $event_data['callback']], 15, $event_data['num_of_args']);
        }
    }

    public static function on_login_success($user_login, $user): void
    {
        self::$_telemetry_client
            ->getContext()
            ->getUserContext()
            ->setAuthUserId($user->data->ID);

        self::$_telemetry_client
            ->trackEvent('login', [
                'ID' => $user->data->ID,
                'user_login' => $user_login,
                'user_email' => $user->data->user_email,
                'display_name' => $user->data->display_name
            ]);
    }

    public static function on_logout($user_id): void
    {
        self::track_event('login', ['user_id' => $user_id]);
    }

    public static function on_login_failed($username, WP_Error $error): void
    {
        self::track_event('login_failed', [
            'username' => $username,
            'error' => wp_kses($error->get_error_message(), [])
        ]);
    }

    public static function on_plugin_activate($plugin, $network_wide): void
    {
        self::track_event('plugin_activated', [
            'plugin' => $plugin,
            'network_wide' => $network_wide
        ]);
    }

    public static function on_plugin_deactivate($plugin, $network_deactivating): void
    {
        self::track_event('plugin_deactivated', [
            'plugin' => $plugin,
            'network_deactivating' => $network_deactivating
        ]);
    }

    public static function on_plugin_delete($plugin_file, $deleted): void
    {
        self::track_event('plugin_deleted', [
            'plugin_file' => $plugin_file,
            'deleted' => $deleted
        ]);
    }

    public static function on_upgrade_complete($upgrade_class, array $hook_extra): void
    {
        $event_name = 'upgrader_process_complete_' . $hook_extra["type"];
        self::track_event($event_name, $hook_extra);
    }

    public static function on_user_register($user_id): void
    {
        self::track_event('new_user_registration', [
            'user_id' => $user_id
        ]);
    }

    public static function on_theme_delete($stylesheet, $deleted): void
    {
        self::track_event('theme_deleted', [
            'stylesheet' => $stylesheet,
            'deleted' => $deleted
        ]);
    }

    public static function on_theme_switch($stylesheet, $old_theme): void
    {
        self::track_event('theme_switched', [
            'stylesheet' => $stylesheet,
            'old_theme' => $old_theme
        ]);
    }

    public static function on_shutdown(): void
    {
        self::$_telemetry_client->flush();
    }

    public static function on_search(WP_Query $query)
    {
        if (!$query->is_search()) {
            return;
        }

        self::track_event('search', [
            's' => $query->get('s'),
            'found_posts' => $query->found_posts
        ]);
    }

    public static function create_menu_item(): void
    {
        add_plugins_page(
            self::file_data()['Name'],
            self::file_data()['Name'],
            'manage_options',
            'wp-azure-app-insights',
            array(__CLASS__, 'options_page_content')
        );
    }

    /**
     * Set up the callbacks for the settings, sections and fields
     *
     * @return void
     */
    public static function admin_init(): void
    {
        add_settings_section(self::$section_id, '', '__return_false', self::$page_id);

        foreach (self::$settings as $setting) {
            register_setting(self::$option_group, $setting['id'], array('sanitize_callback' => [__CLASS__, 'sanitize_option_value_callback_' . $setting['id']]));
            add_settings_field($setting['id'], $setting['label'], [__CLASS__, 'field_html_' . $setting['id']], self::$page_id, self::$section_id);
        }
    }

    public static function inject_js_snippet(): void
    {
        $snippet_path = WP_AZURE_APPLICATION_INSIGHTS_PLUGIN_PATH . 'javascript-snippet.html';
        if (!file_exists($snippet_path)) {
            self::track_exception(new Error('javascript snippet cannot be found'), [
                'path' => $snippet_path
            ]);
            return;
        }
        $raw_snippet = file_get_contents($snippet_path);
        echo $raw_snippet;
    }

    public static function options_page_content(): void
    {
        $plugin_data = get_plugin_data(__FILE__);
        require_once WP_AZURE_APPLICATION_INSIGHTS_PLUGIN_PATH . '/page-admin.php';
    }

    public static function __callStatic(string $method, $arguments)
    {
        $field_html_prefix = 'field_html_';
        if (str_starts_with($method, $field_html_prefix)) {
            return self::field_html_generic(substr($method, strlen($field_html_prefix)));
        }

        $sanitize_option_value_callback_prefix = 'sanitize_option_value_callback_';
        if (str_starts_with($method, $sanitize_option_value_callback_prefix)) {
            return self::sanitize_option_value_callback(substr($method, strlen($sanitize_option_value_callback_prefix)), $arguments[0]);
        }
    }

    public static function get_setting_data(string $setting_id): array
    {
        $setting_index = array_search($setting_id, array_column(self::$settings, 'id'));
        $setting_data = self::$settings[$setting_index];
        $setting_data['value'] = get_option($setting_id);
        return $setting_data;
    }

    public static function field_html_generic(string $setting_id): string
    {
        $setting = self::get_setting_data($setting_id);

        $pattern_html = '';
        $validation_help_html = '';

        if ($setting['pattern']) {
            $pattern_html = "required pattern='{$setting['pattern']}'";
            $validation_help_html = "<p class='description' id='{$setting_id}_validation'>Format: <code>{$setting['pattern']}</code></p>";
        }

        $value = esc_attr($setting['value']);

        $all_html = "<input class='widefat' type='text' autocomplete='false' aria-autocomplete='none' name='$setting_id' value='$value' $pattern_html /> ";
        $all_html .= $validation_help_html;
        echo $all_html;
        return $all_html;
    }

    public static function sanitize_option_value_callback(string $setting_id, ?string $new_value): string
    {
        $setting = self::get_setting_data($setting_id);

        if (!$new_value || $new_value === $setting['value']) {
            return $setting['value'];
        }

        if (!$setting['pattern']) {
            return sanitize_text_field($new_value);
        }

        if (!preg_match('/' . $setting['pattern'] . '/', $new_value)) {
            add_settings_error(self::$option_group, $setting_id . '_regex_failed', "Validation failed: {$setting['label']} <small>(reverting to initial value)</small>");
            return $setting['value'];
        }

        add_settings_error(self::$option_group, $setting_id . '_regex_passed', "Setting updated: {$setting['label']}", 'success');
        self::generate_javascript_snippet([$setting_id => $new_value]);
        return sanitize_text_field($new_value);
    }

    public static function generate_javascript_snippet($new_vals = []): void
    {
        $connection_string = self::get_connection_string($new_vals);
        if (!$connection_string) {
            return;
        }
        $snippet_path = WP_AZURE_APPLICATION_INSIGHTS_PLUGIN_PATH . 'javascript-snippet-sample.html';
        $raw_snippet = file_get_contents($snippet_path);
        $replacements = array(
            '/YOUR_CONNECTION_STRING/' => $connection_string,
        );
        $snippet = preg_replace(array_keys($replacements), array_values($replacements), $raw_snippet);
        try {
            file_put_contents(WP_AZURE_APPLICATION_INSIGHTS_PLUGIN_PATH . 'javascript-snippet.html', $snippet);
            add_settings_error(self::$option_group, 'snippet_update', "Javascript snippet updated", 'success');
        } catch (Error $error) {
            add_settings_error(self::$option_group, 'snippet_update_failed', 'Failed to generate javascript snippet');
            self::track_exception($error, [
                'snippet_path' => $snippet_path,
                'replacements' => $replacements
            ]);
        }
    }

    public static function get_connection_string(array $new_vals = []): ?string
    {
        $options = [];
        foreach (self::$settings as $setting) {
            $options[$setting['id']] = $new_vals[$setting['id']] ?? get_option($setting['id']);
        }

        $options_with_values = array_filter($options, function ($a) {
            return !!$a;
        });

        if (count($options_with_values) !== count($options)) {
            return null;
        }

        [$instrumentation_key, $ingestion_endpoint, $live_endpoint, $application_id] = array_values($options_with_values);

        return "InstrumentationKey=$instrumentation_key;IngestionEndpoint=$ingestion_endpoint;LiveEndpoint=$live_endpoint;ApplicationId=$application_id";
    }

    public static function get_core_tracking_data(): array {
        return [
            'app_env' => $_SERVER['APP_ENV'] ?? 'unknown',
            'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
            'document_uri' => $_SERVER['DOCUMENT_URI'] ?? 'unknown',
            'request_args' => $_SERVER['argv'] ?? 'unknown',
        ];
    }

    public static function track_event(string $event_name, array $args = []): void
    {
        $all_args = array_merge($args, self::get_core_tracking_data());
        self::$_telemetry_client->trackEvent($event_name, $all_args);
    }

    public static function track_exception(Error|Throwable $error, array $args = []): void {
        $all_args = array_merge($args, self::get_core_tracking_data());
        self::$_telemetry_client->trackException($error, $all_args);
    }
}

WP_Azure_Application_Insights::init();