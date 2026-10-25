<?php
if (!defined('ABSPATH')) exit;

add_filter('template_include', 'finelevate360_template_include');

function finelevate360_template_include($template) {

  // ── Singular ──
  if (is_singular(FINELEVATE360_CPT_PARTNERS)) {
    $t = locate_template(['single-finelevate360_partner.php']);
    if ($t) return $t;
    $p = FINELEVATE360_DIR_PATH . 'templates/single-finelevate360_partner.php';
    if (file_exists($p)) return $p;
  }

  if (is_singular(FINELEVATE360_CPT_ADVISORY)) {
    $t = locate_template(['single-finelevate360_advisory.php']);
    if ($t) return $t;
    $p = FINELEVATE360_DIR_PATH . 'templates/single-finelevate360_advisory.php';
    if (file_exists($p)) return $p;
  }

  // ── Archive преку страница (Polylang компатибилен начин) ──
  if (is_page()) {
    global $post;
    $page_id = (int) $post->ID;

    // Земи ги сите ID-та на Partners страниците (EN + сите преводи)
    $partners_ids = finelevate360_get_all_translation_ids('partners');
    if (in_array($page_id, $partners_ids, true)) {
      finelevate360_set_archive_query(FINELEVATE360_CPT_PARTNERS);
      $t = locate_template(['archive-finelevate360_partner.php']);
      if ($t) return $t;
      $p = FINELEVATE360_DIR_PATH . 'templates/archive-finelevate360_partner.php';
      if (file_exists($p)) return $p;
    }

    // Земи ги сите ID-та на Advisory страниците (EN + сите преводи)
    $advisory_ids = finelevate360_get_all_translation_ids('advisory-board');
    if (in_array($page_id, $advisory_ids, true)) {
      finelevate360_set_archive_query(FINELEVATE360_CPT_ADVISORY);
      $t = locate_template(['archive-finelevate360_advisory.php']);
      if ($t) return $t;
      $p = FINELEVATE360_DIR_PATH . 'templates/archive-finelevate360_advisory.php';
      if (file_exists($p)) return $p;
    }
  }

  // ── Стандардна CPT архива (fallback без Polylang страница) ──
  if (is_post_type_archive(FINELEVATE360_CPT_PARTNERS)) {
    $t = locate_template(['archive-finelevate360_partner.php']);
    if ($t) return $t;
    $p = FINELEVATE360_DIR_PATH . 'templates/archive-finelevate360_partner.php';
    if (file_exists($p)) return $p;
  }

  if (is_post_type_archive(FINELEVATE360_CPT_ADVISORY)) {
    $t = locate_template(['archive-finelevate360_advisory.php']);
    if ($t) return $t;
    $p = FINELEVATE360_DIR_PATH . 'templates/archive-finelevate360_advisory.php';
    if (file_exists($p)) return $p;
  }

  return $template;
}

/**
 * Земи ги ID-тата на страницата и сите нејзини Polylang преводи по EN slug.
 */
function finelevate360_get_all_translation_ids($en_slug) {
  $en_page = get_page_by_path($en_slug);
  if (!$en_page) return [];

  $ids = [(int) $en_page->ID];

  if (function_exists('pll_get_post_translations')) {
    $translations = pll_get_post_translations($en_page->ID);
    foreach ($translations as $id) {
      $ids[] = (int) $id;
    }
  }

  return array_unique($ids);
}

/**
 * Постави WP_Query за CPT архива (кога се сервира преку страница).
 */
function finelevate360_set_archive_query($post_type) {
  global $wp_query;
  $paged = max(1, get_query_var('paged'));

  $wp_query = new WP_Query([
    'post_type'      => $post_type,
    'posts_per_page' => (int) get_option('posts_per_page', 10),
    'paged'          => $paged,
  ]);
}

/**
 * Земи го ID-то на archive страницата за тековниот јазик.
 * Се користи во темплејтите за да го земат насловот/описот/сликата.
 */
function finelevate360_get_archive_page($en_slug) {
  $page = get_page_by_path($en_slug);
  if (!$page) return null;

  if (function_exists('pll_get_post_translations') && function_exists('pll_current_language')) {
    $translations = pll_get_post_translations($page->ID);
    $lang = pll_current_language();
    if (isset($translations[$lang])) {
      return get_post($translations[$lang]);
    }
  }

  return $page;
}
