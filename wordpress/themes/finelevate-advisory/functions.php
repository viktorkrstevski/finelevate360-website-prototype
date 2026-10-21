<?php
/** Finelevate Advisory: isolated, editable homepage presentation. */
if (!defined('ABSPATH')) { exit; }

function fe_defaults() {
    static $values;
    if (!isset($values)) {
        $values = json_decode(file_get_contents(get_template_directory() . '/inc/content.json'), true);
    }
    return $values;
}

function fe_value($key) {
    $defaults = fe_defaults();
    $value = get_theme_mod('fe_' . $key, isset($defaults[$key]) ? $defaults[$key]['value'] : '');
    return function_exists('pll__') && isset($defaults[$key]) && $defaults[$key]['type'] === 'text' ? pll__($value) : $value;
}

function fe_asset($file) { return get_template_directory_uri() . '/assets/' . $file; }

function fe_image($key, $fallback) {
    $attachment = absint(get_theme_mod('fe_image_' . $key));
    return $attachment ? wp_get_attachment_image_url($attachment, 'full') : fe_asset($fallback);
}

function fe_staging() {
    return strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST)) === 'stg.finelevate360.com';
}

add_action('after_setup_theme', function () {
    load_theme_textdomain('finelevate-advisory', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    register_nav_menus(array('primary' => __('Primary navigation', 'finelevate-advisory'), 'footer' => __('Footer links', 'finelevate-advisory')));
});

add_action('wp_enqueue_scripts', function () {
    $release = get_template_directory() . '/release.json';
    $version = file_exists($release) ? json_decode(file_get_contents($release), true)['commit'] : wp_get_theme()->get('Version');
    wp_enqueue_style('finelevate-advisory', get_stylesheet_uri(), array(), $version);
    wp_enqueue_script('finelevate-advisory', fe_asset('site.js'), array(), $version, true);
});

// Staging has no analytics integrations and must neither index nor send mail.
add_filter('wp_robots', function ($robots) {
    if (fe_staging()) { $robots['noindex'] = true; $robots['nofollow'] = true; $robots['noarchive'] = true; }
    return $robots;
});
add_action('send_headers', function () {
    if (fe_staging()) { header('X-Robots-Tag: noindex, nofollow, noarchive', true); }
});
add_filter('robots_txt', function ($output) {
    return fe_staging() ? "User-agent: *\nDisallow: /\n" : $output;
});
add_filter('pre_wp_mail', function ($result) { return fe_staging() ? false : $result; });

add_action('init', function () {
    if (function_exists('pll_register_string')) {
        foreach (fe_defaults() as $key => $setting) {
            if ($setting['type'] === 'text') { pll_register_string('fe_' . $key, get_theme_mod('fe_' . $key, $setting['value']), 'Finelevate homepage', true); }
        }
    }
});

require get_template_directory() . '/inc/customizer.php';

function fe_navigation($location = 'primary', $suffix = 'desktop') {
    if (has_nav_menu($location)) {
        wp_nav_menu(array('theme_location' => $location, 'container' => false, 'menu_id' => 'fe-menu-' . $suffix, 'menu_class' => 'nav-links', 'fallback_cb' => false));
        return;
    }
    if ($location === 'footer') {
        echo '<ul class="footer-links">';
        foreach (array('privacy' => 'Privacy Policy', 'cookies' => 'Cookie Policy', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram') as $key => $label) {
            echo '<li><a href="' . esc_url(fe_value($key . '_url')) . '">' . esc_html__($label, 'finelevate-advisory') . '</a></li>';
        }
        echo '</ul>';
        return;
    }
    ?>
    <ul class="nav-links">
      <li><a href="<?php echo esc_url(fe_value('summit_url')); ?>"><?php esc_html_e('Digital Summit 2026', 'finelevate-advisory'); ?></a></li>
      <li><details><summary><?php esc_html_e('Digital Map', 'finelevate-advisory'); ?></summary><ul>
        <?php foreach (array('banking' => 'Banking Digital Map', 'insurance' => 'Insurance Digital Map', 'fintech' => 'Fintech Digital Map') as $key => $label) { ?>
        <li><a href="<?php echo esc_url(fe_value($key . '_url')); ?>"><?php echo esc_html__($label, 'finelevate-advisory'); ?></a></li>
        <?php } ?>
      </ul></details></li>
      <li><a href="<?php echo esc_url(fe_value('partners_url')); ?>"><?php esc_html_e('Partners', 'finelevate-advisory'); ?></a></li>
      <li><a href="<?php echo esc_url(fe_value('news_index_url')); ?>"><?php esc_html_e('News', 'finelevate-advisory'); ?></a></li>
      <li><details><summary><?php esc_html_e('About', 'finelevate-advisory'); ?></summary><ul>
        <li><a href="<?php echo esc_url(fe_value('about_url')); ?>"><?php esc_html_e('About Finelevate', 'finelevate-advisory'); ?></a></li>
        <li><a href="<?php echo esc_url(fe_value('advisory_url')); ?>"><?php esc_html_e('Advisory Board', 'finelevate-advisory'); ?></a></li>
      </ul></details></li>
      <li><details><summary aria-label="<?php esc_attr_e('Choose language', 'finelevate-advisory'); ?>"><?php echo esc_html(function_exists('pll_current_language') ? strtoupper(pll_current_language()) : 'EN'); ?></summary><ul>
        <?php if (function_exists('pll_the_languages')) { pll_the_languages(array('show_flags' => 0, 'show_names' => 1, 'hide_if_no_translation' => 1)); } else {
            foreach (array('en' => 'EN', 'mk' => 'MK', 'sr' => 'RS', 'hr' => 'HR', 'sl' => 'SL', 'me' => 'ME') as $key => $label) {
                echo '<li><a hreflang="' . esc_attr($key) . '" href="' . esc_url(fe_value('language_' . $key . '_url')) . '">' . esc_html($label) . '</a></li>';
            }
        } ?>
      </ul></details></li>
    </ul>
    <?php
}
