<?php
if (!defined('ABSPATH')) exit;

/**
 * IMPORTANT:
 * Post type keys MUST be <= 20 chars in WordPress.
 */
define('FINELEVATE360_CPT_PARTNERS', 'finelev360_partner');
define('FINELEVATE360_CPT_ADVISORY', 'finelev360_advisory');
define('FINELEVATE360_CPT_REPORTS',  'finelev360_report');
define('FINELEVATE360_CPT_ATTR',     'finelev360_attr');
define('FINELEVATE360_CPT_ITEM',     'finelev360_item');
define('FINELEVATE360_TAX_ITEMCAT',  'finelev360_cat');

add_action('init', 'finelevate360_register_cpts');

function finelevate360_register_cpts() {

  register_taxonomy(FINELEVATE360_TAX_ITEMCAT, [FINELEVATE360_CPT_ITEM, FINELEVATE360_CPT_ATTR], [
    'labels'      => ['name' => 'Item Categories', 'singular_name' => 'Item Category'],
    'public'      => true,
    'hierarchical'=> true,
    'show_in_rest'=> true,
    'show_ui'     => true,
    'rewrite'     => ['slug' => 'item-category'],
  ]);

  register_post_type(FINELEVATE360_CPT_PARTNERS, [
    'labels'      => [
      'name'         => 'Partners',
      'singular_name'=> 'Partner',
      'add_new_item' => 'Add New Partner',
      'edit_item'    => 'Edit Partner',
    ],
    'public'       => true,
    'has_archive'  => true,
    'show_in_rest' => true,
    'menu_icon'    => 'dashicons-groups',
    'supports'     => ['title', 'thumbnail', 'editor'],
    // Slug на единечен пост е 'partner', архивата се сервира преку страница
    'rewrite'      => ['slug' => 'partner', 'with_front' => false],
  ]);

  register_post_type(FINELEVATE360_CPT_ADVISORY, [
    'labels'      => [
      'name'         => 'Advisory Board',
      'singular_name'=> 'Advisory Member',
      'add_new_item' => 'Add New Advisory Member',
      'edit_item'    => 'Edit Advisory Member',
    ],
    'public'       => true,
    'has_archive'  => true,
    'show_in_rest' => true,
    'menu_icon'    => 'dashicons-businessperson',
    'supports'     => ['title', 'thumbnail', 'editor'],
    'rewrite'      => ['slug' => 'advisory-member', 'with_front' => false],
  ]);

  register_post_type(FINELEVATE360_CPT_REPORTS, [
    'labels'      => [
      'name'         => 'Reports',
      'singular_name'=> 'Report',
      'add_new_item' => 'Add New Report',
      'edit_item'    => 'Edit Report',
    ],
    'public'       => true,
    'has_archive'  => true,
    'show_in_rest' => true,
    'menu_icon'    => 'dashicons-media-document',
    'supports'     => ['title', 'thumbnail', 'editor'],
    'rewrite'      => ['slug' => 'reports', 'with_front' => false],
  ]);

  register_post_type(FINELEVATE360_CPT_ATTR, [
    'labels'       => [
      'name'         => 'Attributes',
      'singular_name'=> 'Attribute',
      'add_new_item' => 'Add New Attribute',
      'edit_item'    => 'Edit Attribute',
    ],
    'public'       => false,
    'show_ui'      => true,
    'show_in_menu' => 'edit.php?post_type=' . FINELEVATE360_CPT_ITEM,
    'show_in_rest' => true,
    'supports'     => ['title'],
  ]);

  register_post_type(FINELEVATE360_CPT_ITEM, [
    'labels'      => [
      'name'         => 'Items',
      'singular_name'=> 'Item',
      'add_new_item' => 'Add New Item',
      'edit_item'    => 'Edit Item',
    ],
    'public'       => true,
    'has_archive'  => true,
    'show_in_rest' => true,
    'menu_icon'    => 'dashicons-archive',
    'supports'     => ['title', 'thumbnail', 'editor', 'excerpt'],
    'rewrite'      => ['slug' => 'items', 'with_front' => false],
  ]);
}

// ── Филтер по категорија во листата на атрибути ──
add_action('restrict_manage_posts', function($post_type) {
  if ($post_type !== FINELEVATE360_CPT_ATTR) return;
  $terms = get_terms(['taxonomy' => FINELEVATE360_TAX_ITEMCAT, 'hide_empty' => false]);
  if (empty($terms) || is_wp_error($terms)) return;
  $selected = isset($_GET[FINELEVATE360_TAX_ITEMCAT]) ? sanitize_text_field($_GET[FINELEVATE360_TAX_ITEMCAT]) : '';
  ?>
  <select name="<?php echo esc_attr(FINELEVATE360_TAX_ITEMCAT); ?>">
    <option value=""><?php esc_html_e('All Categories', 'finelevate360'); ?></option>
    <?php foreach ($terms as $term) : ?>
      <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected, $term->slug); ?>>
        <?php echo esc_html($term->name); ?>
      </option>
    <?php endforeach; ?>
  </select>
  <?php
});

// ── Polylang: регистрирај ги сите CPT ──
add_filter('pll_get_post_types', function($post_types) {
  $cpts = [
    'FINELEVATE360_CPT_PARTNERS',
    'FINELEVATE360_CPT_ADVISORY',
    'FINELEVATE360_CPT_REPORTS',
    'FINELEVATE360_CPT_ATTR',
    'FINELEVATE360_CPT_ITEM',
  ];
  foreach ($cpts as $const) {
    if (defined($const)) {
      $post_types[constant($const)] = constant($const);
    }
  }
  return $post_types;
});

// ── Polylang: language switcher да го следи преводот на страницата ──
add_filter('pll_translation_url', function($url, $lang) {
  if (!is_page()) return $url;
  global $post;
  if (!$post || !function_exists('pll_get_post_translations')) return $url;
  $translations = pll_get_post_translations($post->ID);
  if (isset($translations[$lang])) {
    return get_permalink($translations[$lang]);
  }
  return $url;
}, 10, 2);

// ── Admin: филтер по категорија за атрибути ──
add_action('pre_get_posts', function($query) {
  if (!is_admin()) return;
  if ($query->get('post_type') !== FINELEVATE360_CPT_ATTR) return;
  if (!isset($_GET['finelev360_cat']) || $_GET['finelev360_cat'] === '') return;
  $query->set('tax_query', [[
    'taxonomy' => FINELEVATE360_TAX_ITEMCAT,
    'field'    => 'slug',
    'terms'    => sanitize_text_field($_GET['finelev360_cat']),
  ]]);
  $query->set('orderby', 'menu_order');
  $query->set('order', 'ASC');
}, 99);
