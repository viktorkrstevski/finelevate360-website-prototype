<?php
/** Exercise route decisions without a database or credentials. */
define('ABSPATH', __DIR__);
function add_filter() {}
function add_action() {}
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_unslash($value) { return $value; }
function home_url($path) { return 'https://stg.finelevate360.com' . $path; }
function sanitize_text_field($value) { return strip_tags($value); }
function map_deep($value, $callback) { return is_array($value) ? array_map(function ($v) use ($callback) { return map_deep($v, $callback); }, $value) : $callback($value); }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
require __DIR__ . '/../wordpress/themes/finelevate-advisory/inc/routes.php';
function same($actual, $expected) {
    if ($actual !== $expected) { throw new Exception('Route result mismatch: ' . $actual); }
}
$_SERVER['REQUEST_URI'] = '/about-us/';
$_GET = array();
same(fe_local_url('https://finelevate360.com/about-us/'), 'https://stg.finelevate360.com/about-us/');
same(fe_local_url('https://official-bank.example/app'), 'https://official-bank.example/app');
same(fe_local_url('https://finelevate360.com.evil.example/about-us/'), 'https://finelevate360.com.evil.example/about-us/');
same(fe_local_url('https://finelevate360.com/wp-content/uploads/logo.png'), 'https://finelevate360.com/wp-content/uploads/logo.png');
$_SERVER['REQUEST_URI'] = '/mk/home-2/';
same(fe_local_url('https://finelevate360.com/about-us/'), home_url(fe_routes()['groups']['about']['mk']));
$_SERVER['REQUEST_URI'] = '/banking-capabilities/?country=slovenia';
$_GET = array('country' => 'slovenia', 'f360_col' => 'mobile', 'password' => 'never-copy');
same(fe_language_url('hr'), home_url(fe_routes()['groups']['banking']['hr']) . '?country=slovenia&f360_col=mobile');
echo "First-party routing, translated destinations and country/filter retention passed.\n";
