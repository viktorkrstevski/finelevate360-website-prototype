<?php
/** Execute the real enqueue callback against disposable stylesheet/release fixtures. */
define('ABSPATH', __DIR__);
$GLOBALS['template_dir'] = realpath(__DIR__ . '/../wordpress/themes/finelevate-advisory');
$GLOBALS['enqueue_actions'] = array();
function add_action($hook, $callback) { $GLOBALS['enqueue_actions'][$hook][] = $callback; }
function add_filter() {}
function get_template_directory() { return $GLOBALS['template_dir']; }
function get_stylesheet_directory() { return $GLOBALS['stylesheet_dir']; }
function get_template_directory_uri() { return 'https://stg.finelevate360.com/wp-content/themes/finelevate-advisory'; }
function get_stylesheet_uri() { return get_template_directory_uri() . '/style.css'; }
function is_singular($type) { return $GLOBALS['event']; }
function wp_get_theme() { return new class { function get($key) { return '0.1.0'; } }; }
function wp_enqueue_style($handle, $url, $dependencies, $version) { $GLOBALS['styles'][$handle] = array($url, $dependencies, $version); }
function wp_enqueue_script($handle, $url, $dependencies, $version, $footer) { $GLOBALS['scripts'][$handle] = array($url, $dependencies, $version, $footer); }
require $GLOBALS['template_dir'] . '/functions.php';
function check($condition, $message) { if (!$condition) { throw new Exception($message); } }
function enqueue() {
    $GLOBALS['styles'] = $GLOBALS['scripts'] = array();
    foreach ($GLOBALS['enqueue_actions']['wp_enqueue_scripts'] as $callback) { $callback(); }
    return $GLOBALS['styles']['finelevate-advisory'][2];
}
$fixture = sys_get_temp_dir() . '/fe-stylesheet-cache-' . bin2hex(random_bytes(8));
mkdir($fixture);
$GLOBALS['template_dir'] = $GLOBALS['stylesheet_dir'] = $fixture;
$GLOBALS['event'] = true;
$release = json_encode(array('commit' => '847e20f500e390c63e132c6c76fdeffc42b9c4e5'));
file_put_contents($fixture . '/release.json', $release);
file_put_contents($fixture . '/style.css', 'body{color:red}');
mkdir($fixture . '/assets');
file_put_contents($fixture . '/assets/site.js', 'let a=1;');
$time = filemtime($fixture . '/style.css');
$before = enqueue();
check($before === '847e20f500e390c63e132c6c76fdeffc42b9c4e5-' . substr(hash('sha256', 'body{color:red}'), 0, 12), 'CSS cache key includes the actual stylesheet content hash');
check(enqueue() === $before, 'Unchanged CSS has a stable cache key');
file_put_contents($fixture . '/style.css', 'body{color:tan}');
touch($fixture . '/style.css', $time);
$after = enqueue();
check($after !== $before, 'Changed CSS invalidates caches even with unchanged file size and mtime');
check($GLOBALS['styles']['finelevate-advisory'][0] === get_stylesheet_uri(), 'Keep the normal stylesheet URL');
check($GLOBALS['styles']['finelevate-event'][2] === '847e20f500e390c63e132c6c76fdeffc42b9c4e5', 'Event CSS retains its existing release version');
$script_before = $GLOBALS['scripts']['finelevate-advisory'][2];
check($script_before === '847e20f500e390c63e132c6c76fdeffc42b9c4e5-' . substr(hash('sha256', 'let a=1;'), 0, 12), 'JavaScript version includes its actual content hash');
$script_time = filemtime($fixture . '/assets/site.js');
file_put_contents($fixture . '/assets/site.js', 'let a=2;');
touch($fixture . '/assets/site.js', $script_time);
check(enqueue() === $after, 'A script-only change keeps the CSS cache key stable');
check($GLOBALS['scripts']['finelevate-advisory'][2] !== $script_before, 'Changed JavaScript invalidates caches despite unchanged file size and mtime');
check($GLOBALS['scripts']['finelevate-advisory'][0] === fe_asset('site.js'), 'Keep the normal JavaScript URL');
check(file_get_contents($fixture . '/release.json') === $release, 'Do not modify or relabel the release manifest');
unlink($fixture . '/release.json');
check(enqueue() === '0.1.0-' . substr(hash('sha256', 'body{color:tan}'), 0, 12), 'Unpackaged themes retain a stylesheet-specific cache key');
check($GLOBALS['scripts']['finelevate-advisory'][2] === '0.1.0-' . substr(hash('sha256', 'let a=2;'), 0, 12), 'Unpackaged themes retain a script-specific cache key');
unlink($fixture . '/assets/site.js');
rmdir($fixture . '/assets');
unlink($fixture . '/style.css');
rmdir($fixture);
echo "Stylesheet and script cache invalidation, stable versions and unchanged release metadata passed.\n";
