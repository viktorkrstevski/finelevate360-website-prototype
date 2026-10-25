<?php
/** No database needed: protect native menu semantics and map content. */
define('ABSPATH', __DIR__);
function add_filter() {}
function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES); }
function fe_asset($path) { return 'https://stg.finelevate360.com/wp-content/themes/finelevate-advisory/assets/' . $path; }
function is_admin() { return false; }
function in_the_loop() { return true; }
function is_main_query() { return true; }
function fe_language() { return $GLOBALS['language']; }
function get_the_ID() { return 2074; }
function get_post_meta($id, $key, $single) { return '/me/digitalna-mapa-banke/'; }
function fe_routes() { return array('routes' => array('/me/digitalna-mapa-banke/' => array('group' => 'banking'))); }
require __DIR__ . '/../wordpress/themes/finelevate-advisory/inc/presentation.php';
function verify($ok, $message) { if (!$ok) { throw new Exception($message); } }
$menu = (object) array('theme_location' => 'primary');
$item = (object) array('classes' => array('lang-item-me', 'current-lang'));
$title = '<span class="pll-switcher-label">Me</span>';
$rendered = fe_menu_country_flag($title, $item, $menu);
verify(strpos($rendered, 'flags/me.svg') !== false && strpos($rendered, $title) !== false, 'Add Montenegro flag without replacing its label');
verify(fe_menu_country_flag($rendered, $item, $menu) === $rendered, 'Do not duplicate an existing flag');
verify(fe_menu_country_flag($title, (object) array('classes' => array('lang-item-sr')), $menu) === $title, 'Preserve other country flags');
verify(fe_menu_country_flag($title, $item, (object) array('theme_location' => 'footer')) === $title, 'Scope to primary menus');
$GLOBALS['language'] = 'me';
$empty = '<div class="finelevate-map-content"></div><section data-statuses="yes partial no unknown na">unchanged cards</section>';
$expected = str_replace('</div>', '<h2>Region: Crna Gora</h2></div>', $empty);
verify(fe_map_country_heading($empty) === $expected, 'Supply the missing region heading only in the empty wrapper');
$editorial = '<div class="finelevate-map-content"><p>Country-specific editorial copy.</p></div>';
verify(fe_map_country_heading($editorial) === $editorial, 'Preserve all existing editorial content');
foreach (array('mk', 'en', 'sr', 'hr', 'sl') as $lang) {
    $GLOBALS['language'] = $lang;
    verify(fe_map_country_heading($empty) === $empty, 'Preserve ' . $lang . ' map content');
}
echo "Country flag, existing content, status output and locale isolation passed.\n";
