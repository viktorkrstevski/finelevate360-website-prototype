<?php
/** Keep imported public routes and language variants inside this WordPress site. */
if (!defined('ABSPATH')) { exit; }

function fe_routes() {
    static $manifest;
    if (!isset($manifest)) {
        $manifest = json_decode(file_get_contents(__DIR__ . '/routes.json'), true);
    }
    return $manifest;
}

function fe_route_path($url) {
    $path = rawurldecode((string) wp_parse_url($url, PHP_URL_PATH));
    return '/' . trim($path, '/') . (trim($path, '/') !== '' ? '/' : '');
}

function fe_language() {
    if (function_exists('pll_current_language') && pll_current_language()) {
        return pll_current_language();
    }
    $parts = explode('/', trim(fe_route_path(wp_unslash($_SERVER['REQUEST_URI'] ?? '/')), '/'));
    return in_array($parts[0], array('mk', 'sr', 'hr', 'sl', 'me'), true) ? $parts[0] : 'en';
}

add_filter('language_attributes', function ($attributes) {
    return preg_replace('/lang="[^"]+"/', 'lang="' . esc_attr(fe_language()) . '"', $attributes);
});

function fe_local_url($url, $translate = true) {
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    if (!in_array($host, array('finelevate360.com', 'www.finelevate360.com'), true)) { return $url; }
    $manifest = fe_routes();
    $path = fe_route_path($url);
    if (!isset($manifest['routes'][$path])) { return $url; }
    $group = $manifest['routes'][$path]['group'];
    if ($translate && $group && isset($manifest['groups'][$group][fe_language()])) {
        $path = $manifest['groups'][$group][fe_language()];
    }
    $query = wp_parse_url($url, PHP_URL_QUERY);
    $fragment = wp_parse_url($url, PHP_URL_FRAGMENT);
    return home_url($path) . ($query ? '?' . $query : '') . ($fragment ? '#' . $fragment : '');
}

function fe_language_url($language) {
    $manifest = fe_routes();
    $path = fe_route_path(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
    $group = isset($manifest['routes'][$path]) ? $manifest['routes'][$path]['group'] : '';
    $target = $group && isset($manifest['groups'][$group][$language])
        ? $manifest['groups'][$group][$language] : $manifest['groups']['home'][$language];
    // Language is independent of the visitor's selected country and map filters.
    $filters = array_intersect_key(wp_unslash($_GET), array_flip(array('country', 'country_id', 'country_slug', 'f360_cat', 'f360_col', 'f360_attr', 'channel', 'segment', 'sector')));
    $filters = map_deep($filters, 'sanitize_text_field');
    return $filters ? add_query_arg($filters, home_url($target)) : home_url($target);
}

// Existing/custom menus and editorial links use the same route resolver.
add_filter('nav_menu_link_attributes', function ($attributes) {
    if (isset($attributes['href'])) { $attributes['href'] = fe_local_url($attributes['href']); }
    return $attributes;
});
add_filter('the_content', function ($content) {
    return preg_replace_callback('/(<a\b[^>]*\bhref=["\'])([^"\']+)(["\'])/i', function ($match) {
        return $match[1] . esc_url(fe_local_url(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'), false)) . $match[3];
    }, $content);
}, 20);

function fe_public_record_id($path) {
    $found = get_posts(array('post_type' => array('page', 'post', 'event', 'finelev360_partner', 'finelev360_advisory'),
        'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids',
        'meta_key' => '_fe_public_path', 'meta_value' => $path));
    foreach ($found as $id) {
        // The standard importer can attach several paths to a title/date match.
        // Only its original path identifies that record's actual body/language.
        if (get_post_meta($id, '_fe_public_path', true) === $path) { return $id; }
    }
    return 0;
}

function fe_import_existing_public_record($existing, $post) {
    if (!fe_staging() || !is_admin() || !current_user_can('import')) { return $existing; }
    $meta = array();
    foreach ($post['postmeta'] ?? array() as $entry) { $meta[$entry['key']] = $entry['value']; }
    $path = $meta['_fe_public_path'] ?? '';
    $source_id = absint($meta['_fe_public_source_id'] ?? 0);
    if (!$path || !isset(fe_routes()['routes'][$path]) || fe_routes()['routes'][$path]['source_id'] !== $source_id) { return $existing; }
    // Preserve the source's normal title and date without conflating translations.
    // Never update an existing editorial body during a repeated import.
    return fe_public_record_id($path);
}
add_filter('wp_import_existing_post', 'fe_import_existing_public_record', 10, 2);

// Preserve public paths without flattening translated posts or copying production
// rewrite settings/database. The imported records remain normal editable WP posts.
add_action('parse_request', function ($request) {
    if (is_admin()) { return; }
    $path = fe_route_path('/' . $request->request);
    if ($path === '/' || !isset(fe_routes()['routes'][$path])) { return; }
    $id = fe_public_record_id($path);
    if (!$id) { return; }
    $request->query_vars = array('p' => $id, 'post_type' => get_post_type($id));
    if (get_post_type($id) === 'page') { $request->query_vars = array('page_id' => $id); }
});
add_filter('redirect_canonical', function ($redirect) {
    $path = fe_route_path(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
    return isset(fe_routes()['routes'][$path]) ? false : $redirect;
});
function fe_imported_permalink($url, $post) {
    $id = is_object($post) ? $post->ID : $post;
    $path = get_post_meta($id, '_fe_public_path', true);
    return $path ? home_url($path) : $url;
}
add_filter('page_link', 'fe_imported_permalink', 10, 2);
add_filter('post_link', 'fe_imported_permalink', 10, 2);
add_filter('post_type_link', 'fe_imported_permalink', 10, 2);
add_filter('template_include', function ($template) {
    if (is_singular() && get_post_meta(get_queried_object_id(), '_fe_public_path', true)) {
        $route = fe_routes()['routes'][get_post_meta(get_queried_object_id(), '_fe_public_path', true)];
        return get_template_directory() . ($route['group'] === 'home' ? '/front-page.php' : '/page.php');
    }
    return $template;
});

add_action('init', function () {
    foreach (array('event' => 'Events', 'finelev360_partner' => 'Partners', 'finelev360_advisory' => 'Advisory members') as $type => $label) {
        if (!post_type_exists($type)) {
            register_post_type($type, array('label' => $label, 'public' => true, 'show_in_rest' => true,
                'supports' => array('title', 'editor', 'thumbnail', 'revisions'), 'rewrite' => false));
        }
    }
});

function fe_news_archive() {
    $posts = new WP_Query(array('post_type' => 'post', 'post_status' => 'publish',
        'posts_per_page' => 50, 'meta_key' => '_fe_public_locale', 'meta_value' => fe_language()));
    echo '<div class="news-grid">';
    while ($posts->have_posts()) {
        $posts->the_post();
        echo '<article><p class="eyebrow">' . esc_html(get_the_date()) . '</p><h2><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h2><p>' . esc_html(wp_trim_words(wp_strip_all_tags(get_the_content()), 45)) . '</p><a class="text-link" href="' . esc_url(get_permalink()) . '">' . esc_html__('Read more', 'finelevate-advisory') . ' →</a></article>';
    }
    echo '</div>';
    wp_reset_postdata();
}
