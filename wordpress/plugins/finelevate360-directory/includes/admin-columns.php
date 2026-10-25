<?php
if (!defined('ABSPATH')) exit;

add_filter('manage_' . FINELEVATE360_CPT_PARTNERS . '_posts_columns', function ($columns) {
  $new = [];
  foreach ($columns as $k => $v) {
    $new[$k] = $v;
    if ($k === 'title') {
      $new['finelevate360_badge']    = 'Badge';
      $new['finelevate360_website']  = 'Website';
      $new['finelevate360_linkedin'] = 'LinkedIn';
      $new['finelevate360_contact']  = 'Contact';
    }
  }
  return $new;
});

add_filter('manage_' . FINELEVATE360_CPT_ADVISORY . '_posts_columns', function ($columns) {
  $new = [];
  foreach ($columns as $k => $v) {
    $new[$k] = $v;
    if ($k === 'title') {
      $new['finelevate360_website']  = 'Website';
      $new['finelevate360_linkedin'] = 'LinkedIn';
      $new['finelevate360_contact']  = 'Contact';
    }
  }
  return $new;
});

add_action('manage_' . FINELEVATE360_CPT_PARTNERS . '_posts_custom_column', function ($column, $post_id) {
  if ($column === 'finelevate360_badge') {
    $t = get_post_meta($post_id, '_finelevate360_partner_badge_text', true);
    $c = get_post_meta($post_id, '_finelevate360_partner_badge_class', true);
    if ($t) {
      echo '<span style="display:inline-block;padding:3px 8px;border:1px solid #ddd;border-radius:999px;font-size:12px;">'
        . esc_html($t)
        . ($c ? ' <code style="font-size:11px;">' . esc_html($c) . '</code>' : '')
        . '</span>';
    } else {
      echo '&mdash;';
    }
    return;
  }

  if ($column === 'finelevate360_website') {
    $u = get_post_meta($post_id, '_finelevate360_partner_website', true);
    echo $u ? '<a href="' . esc_url($u) . '" target="_blank" rel="noopener noreferrer">' . esc_html(finelevate360_admin_short_url($u)) . '</a>' : '&mdash;';
    return;
  }

  if ($column === 'finelevate360_linkedin') {
    $u = get_post_meta($post_id, '_finelevate360_partner_linkedin', true);
    echo $u ? '<a href="' . esc_url($u) . '" target="_blank" rel="noopener noreferrer">' . esc_html(finelevate360_admin_short_url($u)) . '</a>' : '&mdash;';
    return;
  }

  if ($column === 'finelevate360_contact') {
    $v = get_post_meta($post_id, '_finelevate360_partner_contact', true);
    echo $v ? esc_html(wp_trim_words(wp_strip_all_tags($v), 10)) : '&mdash;';
    return;
  }
}, 10, 2);

add_action('manage_' . FINELEVATE360_CPT_ADVISORY . '_posts_custom_column', function ($column, $post_id) {
  if ($column === 'finelevate360_website') {
    $u = get_post_meta($post_id, '_finelevate360_advisory_website', true);
    echo $u ? '<a href="' . esc_url($u) . '" target="_blank" rel="noopener noreferrer">' . esc_html(finelevate360_admin_short_url($u)) . '</a>' : '&mdash;';
    return;
  }

  if ($column === 'finelevate360_linkedin') {
    $u = get_post_meta($post_id, '_finelevate360_advisory_linkedin', true);
    echo $u ? '<a href="' . esc_url($u) . '" target="_blank" rel="noopener noreferrer">' . esc_html(finelevate360_admin_short_url($u)) . '</a>' : '&mdash;';
    return;
  }

  if ($column === 'finelevate360_contact') {
    $v = get_post_meta($post_id, '_finelevate360_advisory_contact', true);
    echo $v ? esc_html(wp_trim_words(wp_strip_all_tags($v), 10)) : '&mdash;';
    return;
  }
}, 10, 2);

add_filter('manage_edit-' . FINELEVATE360_CPT_PARTNERS . '_sortable_columns', function ($columns) {
  $columns['finelevate360_badge'] = 'finelevate360_badge_text';
  return $columns;
});

add_action('pre_get_posts', function ($q) {
  if (!is_admin() || !$q->is_main_query()) return;

  $post_type = $q->get('post_type');
  if ($post_type !== FINELEVATE360_CPT_PARTNERS) return;

  $orderby = $q->get('orderby');
  if ($orderby === 'finelevate360_badge_text') {
    $q->set('meta_key', '_finelevate360_partner_badge_text');
    $q->set('orderby', 'meta_value');
  }
});

function finelevate360_admin_short_url($url) {
  $u = preg_replace('#^https?://#', '', trim((string)$url));
  $u = rtrim($u, '/');
  return mb_strlen($u) > 34 ? (mb_substr($u, 0, 31) . '…') : $u;
}
