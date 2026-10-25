<?php
/** Presentation adapters for the imported country maps and native menus. */
if (!defined('ABSPATH')) { exit; }

function fe_montenegro_flag() {
    return '<span class="pll-switcher-flag fe-country-flag"><img src="' . esc_url(fe_asset('flags/me.svg')) . '" width="18" height="14" alt=""></span>';
}

// The imported cnr language has no flag. Repair only this theme's menu output,
// including both the current-language parent and its Montenegro submenu entry.
function fe_menu_country_flag($title, $item, $args) {
    if (($args->theme_location ?? '') !== 'primary'
        || !in_array('lang-item-me', (array) $item->classes, true)
        || strpos($title, '<img') !== false) { return $title; }
    return fe_montenegro_flag() . $title;
}
add_filter('nav_menu_item_title', 'fe_menu_country_flag', 20, 3);

// Montenegro's imported body is empty. Keep its existing body and Directory
// output intact, while supplying the same country heading used by other maps.
function fe_map_country_heading($content) {
    if (is_admin() || !in_the_loop() || !is_main_query() || fe_language() !== 'me') { return $content; }
    $path = get_post_meta(get_the_ID(), '_fe_public_path', true);
    if ((fe_routes()['routes'][$path]['group'] ?? '') !== 'banking') { return $content; }
    return preg_replace('/(<div class="finelevate-map-content">)\s*(<\/div>)/', '$1<h2>Region: Crna Gora</h2>$2', $content, 1);
}
add_filter('the_content', 'fe_map_country_heading', 40);
