<?php
// Exercise the actual registered modal enqueue callback with WordPress UI stubs.
$assertions = 0;
$market = '';
$registered = [];
$enqueued = [];
$localized = [];
define('FINELEVATE360_VERSION', '1.0.11-staging');
define('ABSPATH', dirname(__DIR__) . '/');
function add_action(...$args) {}
function add_shortcode(...$args) {}
function finelevate360_research_page_market() { return $GLOBALS['market']; }
function finelevate360_i18n() { return ['close' => 'Close']; }
function plugin_dir_url($path) { return 'https://stg.finelevate360.com/wp-content/plugins/finelevate360-directory/'; }
function wp_register_script($handle, $src, $deps, $version, $footer) { $GLOBALS['registered'][$handle] = compact('src', 'deps', 'version', 'footer'); }
function wp_localize_script($handle, $name, $data) { $GLOBALS['localized'] = compact('handle', 'name', 'data'); }
function wp_enqueue_script($handle) { $GLOBALS['enqueued'][] = $handle; }
function verify_asset($condition, $message) {
    $GLOBALS['assertions']++;
    if (!$condition) throw new RuntimeException($message);
}
$plugin = dirname(__DIR__) . '/wordpress/plugins/finelevate360-directory';
require $plugin . '/includes/shortcodes.php';
$expected = '1.0.10-regional-' . substr(hash_file('sha256', $plugin . '/assets/js/modal.js'), 0, 12);
foreach (['sr', 'me', 'hr', 'sl', ''] as $candidate) {
    $market = $candidate;
    $registered = $enqueued = $localized = [];
    finelevate360_modal_enqueue();
    $script = $registered['finelevate360-modal'];
    verify_asset($script['version'] === ($candidate ? $expected : FINELEVATE360_VERSION), 'Version must follow the actual script bytes for each research market.');
    verify_asset($script['src'] === 'https://stg.finelevate360.com/wp-content/plugins/finelevate360-directory/assets/js/modal.js', 'Keep the existing asset URL.');
    verify_asset($script['deps'] === [] && $script['footer'] === true, 'Keep existing loading behavior.');
    verify_asset($enqueued === ['finelevate360-modal'], 'Enqueue the registered modal handle once.');
    verify_asset($localized === ['handle' => 'finelevate360-modal', 'name' => 'f360i18n', 'data' => ['close' => 'Close']], 'Keep existing localization.');
}
verify_asset((bool) preg_match('/^1\.0\.10-regional-[a-f0-9]{12}$/', $expected), 'Use a stable content fingerprint.');
echo "Regional modal asset version: {$assertions} assertions passed.\n";
