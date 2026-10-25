<?php
/**
 * Plugin Name: Finelevate Staging Migration
 * Description: Staging-only outbound isolation and selective content migration.
 * Version: 0.2.4
 * Author: Finelevate360
 */
if (!defined('ABSPATH')) exit;
function fe_migration_is_staging() {
    return strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST)) === 'stg.finelevate360.com';
}
register_activation_hook(__FILE__, function () {
    if (!fe_migration_is_staging()) wp_die('This plugin may only be activated on stg.finelevate360.com.');
});
if (!fe_migration_is_staging()) return;
// Use the original HTTPS WordPress.org endpoints; the hosting proxy changes
// their destination and is intentionally outside the staging integration allowlist.
remove_filter('pre_http_request','hostinger_use_proxy_services',10);
if (!defined('DISABLE_WP_CRON')) define('DISABLE_WP_CRON', true);
if (!defined('WP_ENVIRONMENT_TYPE')) define('WP_ENVIRONMENT_TYPE', 'staging');
add_filter('pre_wp_mail', '__return_false', PHP_INT_MAX);
add_filter('pre_get_ready_cron_jobs', function () { return []; }, PHP_INT_MAX);
add_filter('action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX);
add_filter('action_scheduler_queue_runner_concurrent_batches', function () { return 0; }, PHP_INT_MAX);
add_filter('pre_http_request', function ($pre, $args, $url) {
    $host = strtolower((string)wp_parse_url($url, PHP_URL_HOST));
    $scheme = strtolower((string)wp_parse_url($url, PHP_URL_SCHEME));
    $official = in_array($host, ['api.wordpress.org', 'downloads.wordpress.org', 's.w.org', 'ps.w.org'], true);
    if ($scheme === 'https' && $official) return $pre;
    return new WP_Error('fe_staging_outbound_blocked', 'External integrations are disabled on staging.');
}, PHP_INT_MAX, 3);
add_filter('wp_robots', function ($robots) { $robots['noindex']=true; $robots['nofollow']=true; $robots['noarchive']=true; return $robots; }, PHP_INT_MAX);
add_action('admin_menu', function () {
    add_management_page('Finelevate staging migration', 'Finelevate migration', 'manage_options', 'finelevate-staging-migration', 'fe_migration_page');
});
function fe_migration_page() {
    if (!current_user_can('manage_options')) return;
    if (function_exists('fe_migration_import_page')) { fe_migration_import_page(); return; }
    echo '<div class="wrap"><h1>Finelevate staging migration</h1><p>Outbound email, external HTTP integrations, WP-Cron execution and Action Scheduler runners are blocked on this staging site. Official WordPress.org update requests remain available.</p><p>Content import is not configured yet.</p></div>';
}

require_once __DIR__.'/adapter.php';
if (is_admin()) require_once __DIR__.'/importer.php';
