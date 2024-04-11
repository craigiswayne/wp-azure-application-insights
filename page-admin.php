<?php
if(!defined('WP_AZURE_APPLICATION_INSIGHTS_PLUGIN_PATH') || !$plugin_data){
    exit;
}
$page_title = "<h2>{$plugin_data['Name']}&nbsp;<span style='font-size: 1rem'><i>({$plugin_data['Version']})</i></span></h2>";
?>
    <div class="wrap">
        <?php echo $page_title; ?>
        <p class="description">
            <a target="_blank" href="https://learn.microsoft.com/en-us/azure/azure-monitor/app/sdk-connection-string?tabs=dotnet5#find-your-connection-string">
                How to find your connection string
            </a>
        </p>
        <form method="post" action="<?php echo wp_kses( admin_url( 'options.php' ), array() ); ?>">
            <?php
            settings_errors( WP_Azure_Application_Insights::$option_group );
            settings_fields( WP_Azure_Application_Insights::$option_group );
            do_settings_sections( WP_Azure_Application_Insights::$page_id );
            ?>
            <hr/>
            <section>
                <header><h3>Events</h3></header>
                <p>The following events are automatically tracked</p>
                <ul>
                    <?php foreach (WP_Azure_Application_Insights::$events_tracked as $event): ?>
                        <li><?php echo $event['hook_name']; ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php submit_button(); ?>
        </form>
    </div>
<?php