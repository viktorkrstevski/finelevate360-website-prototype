<?php
if (!defined('ABSPATH')) exit;

add_filter('posts_join', 'finelevate360_admin_search_join', 10, 2);
add_filter('posts_where', 'finelevate360_admin_search_where', 10, 2);
add_filter('posts_groupby', 'finelevate360_admin_search_groupby', 10, 2);

function finelevate360_admin_search_join($join, $q) {
  global $wpdb;
  if (!is_admin() || !$q->is_main_query() || !$q->is_search()) return $join;

  $pt = $q->get('post_type');
  if (!in_array($pt, [FINELEVATE360_CPT_PARTNERS, FINELEVATE360_CPT_ADVISORY], true)) return $join;

  if (strpos($join, $wpdb->postmeta) === false) {
    $join .= " LEFT JOIN {$wpdb->postmeta} finelevate360_pm ON ({$wpdb->posts}.ID = finelevate360_pm.post_id) ";
  }

  return $join;
}

function finelevate360_admin_search_where($where, $q) {
  global $wpdb;
  if (!is_admin() || !$q->is_main_query() || !$q->is_search()) return $where;

  $pt = $q->get('post_type');
  if (!in_array($pt, [FINELEVATE360_CPT_PARTNERS, FINELEVATE360_CPT_ADVISORY], true)) return $where;

  $s = $q->get('s');
  if (!$s) return $where;

  $keys = ($pt === FINELEVATE360_CPT_PARTNERS)
    ? ["_finelevate360_partner_badge_text","_finelevate360_partner_badge_class","_finelevate360_partner_contact","_finelevate360_partner_website","_finelevate360_partner_linkedin"]
    : ["_finelevate360_advisory_contact","_finelevate360_advisory_website","_finelevate360_advisory_linkedin"];

  $like = '%' . $wpdb->esc_like($s) . '%';
  $keys_in = "'" . implode("','", array_map('esc_sql', $keys)) . "'";

  $meta_sql = $wpdb->prepare(" (finelevate360_pm.meta_key IN ($keys_in) AND finelevate360_pm.meta_value LIKE %s) ", $like);

  $where .= " OR " . $meta_sql;

  return $where;
}

function finelevate360_admin_search_groupby($groupby, $q) {
  global $wpdb;
  if (!is_admin() || !$q->is_main_query() || !$q->is_search()) return $groupby;

  $pt = $q->get('post_type');
  if (!in_array($pt, [FINELEVATE360_CPT_PARTNERS, FINELEVATE360_CPT_ADVISORY], true)) return $groupby;

  return "{$wpdb->posts}.ID";
}
