<?php
/**
 * Plugin Name: Finelevate360 Directory (Partners, Advisory Board, Reports)
 * Description: CPTs + meta fields + shortcodes + template overrides + admin columns/search.
 * Version: 1.0.11-staging
 * Text Domain: finelevate360
 * Author: Finelevate360
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('FINELEVATE360_DIR_PATH', plugin_dir_path(__FILE__));
define('FINELEVATE360_DIR_URL',  plugin_dir_url(__FILE__));

require_once FINELEVATE360_DIR_PATH . 'includes/cpt.php';
// BEGIN HR/SI RESEARCH HELPERS (five-state gated; onboarding aliases across translations)
/** Five-state research is restricted to reviewed records; SR/ME additions are staging-only. */
if (!defined('ABSPATH')) exit;

/** Reversible onboarding consolidation: archived IDs retain their original data.
 * The second translation family is canonical. Never OR historical statuses into it.
 * A draft/trash first attribute activates its alias; republishing it reverses the view.
 */
/** Map source-specific research gates on the isolated staging site. */
function finelevate360_migration_rule($key, $fallback) {
  if (strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST)) !== 'stg.finelevate360.com') return $fallback;
  $rules = get_option('fe_staging_migration_research_rules', []);
  return $rules[$key] ?? [];
}

function finelevate360_archived_attribute_aliases() {
  $aliases = finelevate360_migration_rule('aliases', [762=>54,763=>603,1098=>1187,1122=>1188,1925=>1926,2034=>2035]);
  foreach ($aliases as $old_id=>$canonical_id) {
    if (get_post_status($old_id) === 'publish') unset($aliases[$old_id]);
  }
  return $aliases;
}

function finelevate360_active_attribute_values($saved) {
  return is_array($saved) ? array_diff_key($saved, finelevate360_archived_attribute_aliases()) : [];
}

function finelevate360_canonical_attribute_filters($ids) {
  $aliases = finelevate360_archived_attribute_aliases();
  return array_values(array_unique(array_map(function($id) use ($aliases) {
    return $aliases[(int)$id] ?? (int)$id;
  }, $ids)));
}

/** Reviewed destination IDs apply only to this staging origin, including localized home URLs. */
function finelevate360_regional_staging_config($market) {
  $origin = wp_parse_url(home_url('/'));
  if (!is_array($origin) || strtolower($origin['scheme'] ?? '') !== 'https'
    || strtolower($origin['host'] ?? '') !== 'stg.finelevate360.com'
    || (isset($origin['port']) && $origin['port'] !== 443)
    || isset($origin['user']) || isset($origin['pass'])) return [];
  $config = ['sr'=>['items'=>[2299,2300,2301,2302,2304,2303,2311,2305,2313,2306,2307,2315,2308,2319,2317,2321,2323,2325,2309],'attributes'=>[2366,2368,2370,2372,2374,2376,2378,2380,2382,2384,2386,2388,2390,2392,2394,2396,2398,2400,2402,2404,2406,2408,2410,2412,2414,2416],'path'=>'/sr/digitalna-mapa-2/'],'me'=>['items'=>[2489,2485,2490,2487,2491,2493,2492,2488,2494,2486,2495],'attributes'=>[2459,2460,2461,2462,2463,2464,2465,2466,2467,2468,2469,2470,2471,2472,2473,2474,2475,2476,2477,2478,2479,2480,2481,2482,2483,2484],'path'=>'/me/digitalna-mapa-banke/']];
  return $config[$market] ?? [];
}

function finelevate360_research_ids($market) {
  $regional = finelevate360_regional_staging_config($market);
  if ($regional) return $regional['items'];
  $ids = [
    'hr' => [1007,1010,1064,1067,1070,1072,1075,1079,1082,1087,1090,1093,1096,1101,1104,1107,1110,1113,1116,2164],
    'sl' => [1952,1954,1956,1958,1960,1962,1964,1965,1967,1968,1970,1972],
  ];
  $ids = finelevate360_migration_rule('items', $ids);
  return $ids[$market] ?? [];
}

function finelevate360_research_market($post_id) {
  foreach (['hr','sl','sr','me'] as $market) {
    if (in_array((int)$post_id, finelevate360_research_ids($market), true) && get_post_type($post_id) === FINELEVATE360_CPT_ITEM) return $market;
  }
  return '';
}

function finelevate360_research_page_market() {
  $id = (int)get_queried_object_id();
  foreach (['sr','me'] as $market) {
    $regional = finelevate360_regional_staging_config($market);
    if ($regional && get_post_meta($id, '_fe_public_path', true) === $regional['path']) return $market;
  }
  $pages = finelevate360_migration_rule('pages', ['hr'=>1042,'sl'=>1974]);
  return $id === ($pages['hr'] ?? 0) ? 'hr' : ($id === ($pages['sl'] ?? 0) ? 'sl' : '');
}

function finelevate360_research_attr_ids($market) {
  $regional = finelevate360_regional_staging_config($market);
  if ($regional) return $regional['attributes'];
  $ids = $market === 'sl' ? range(1925,1951) : ($market === 'hr' ? [1122,1188,1190,1192,1194,1197,1200,1204,1206,1208,1210,1212,1214,1216,1219,1221,1224,1227,1229,1231,1233,1237,1239,1241,1243,1246,1248] : []);
  $mapped = finelevate360_migration_rule('attributes', ['hr'=>$market === 'hr' ? $ids : [],'sl'=>$market === 'sl' ? $ids : []]);
  $ids = $mapped[$market] ?? [];
  return array_values(array_diff($ids, array_keys(finelevate360_archived_attribute_aliases())));
}

function finelevate360_research_status($value) {
  return is_string($value) && in_array($value, ['yes','no','partial','unknown','not_applicable'], true) ? $value : 'unknown';
}

function finelevate360_research_labels($market) {
  if (in_array($market, ['sr','me'], true) && finelevate360_regional_staging_config($market)) {
    $sr = $market === 'sr';
    return [
      'yes'=>'Dostupno','no'=>'Nije dostupno','partial'=>$sr ? 'Delimično' : 'Djelimično',
      'unknown'=>'Nije potvrđeno','not_applicable'=>$sr ? 'Nije primenljivo' : 'Nije primjenjivo',
      'confirmed'=>'Potvrđena dostupnost','coverage'=>'Pokrivenost dokazima','legend'=>'Legenda',
      'explanation'=>'Filtri prikazuju samo potvrđenu punu dostupnost. Djelimično znači ograničen obim; pročitajte pojašnjenje. Nije potvrđeno ne znači da usluga nije dostupna. Nije primjenjivo označava dokumentovanu neprimjenjivost. Pokrivenost broji statuse potkrijepljene dokazima; to nije ocjena digitalne zrelosti.',
    ];
  }
  return $market === 'sl' ? [
    'yes'=>'Na voljo','no'=>'Ni na voljo','partial'=>'Delno','unknown'=>'Ni potrjeno','not_applicable'=>'Ni relevantno',
    'confirmed'=>'Potrjena razpoložljivost','coverage'=>'Pokritost z dokazi','legend'=>'Legenda',
    'explanation'=>'Filtri prikazujejo samo potrjeno polno razpoložljivost. Delno pomeni omejen obseg; preberite pojasnilo. Ni potrjeno ne pomeni, da storitev ni na voljo. Ni relevantno je dokumentirana neuporabljivost. Pokritost šteje dokazovane statuse (tudi Delno, Ni na voljo in Ni relevantno); to ni ocena digitalne zrelosti.',
  ] : [
    'yes'=>'Dostupno','no'=>'Nije dostupno','partial'=>'Djelomično','unknown'=>'Nije potvrđeno','not_applicable'=>'Nije primjenjivo',
    'confirmed'=>'Potvrđena dostupnost','coverage'=>'Pokrivenost dokazima','legend'=>'Legenda',
    'explanation'=>'Filtri prikazuju samo potvrđenu punu dostupnost. Djelomično znači ograničen opseg; pročitajte pojašnjenje. Nije potvrđeno ne znači da usluga nije dostupna. Nije primjenjivo označava dokumentiranu neprimjenjivost. Pokrivenost broji statuse potkrijepljene dokazima (uključujući Djelomično, Nije dostupno i Nije primjenjivo); to nije ocjena digitalne zrelosti.',
  ];
}

/** Read a native research array or a bounded, lossless JSON transport envelope. */
function finelevate360_research_data($post_id) {
  if (!finelevate360_research_market($post_id)) return [];
  $research = get_post_meta($post_id, '_finelevate360_item_research', true);
  if (is_string($research)) {
    if (strlen($research) > 1048576) return [];
    $research = json_decode($research, true, 64);
  }
  if (!is_array($research)) return [];
  if (isset($research['encoding'])) {
    if ($research['encoding'] !== 'gzip-base64-json-v1' || !function_exists('gzdecode')) return [];
    $data = $research['data'] ?? null;
    $size = $research['uncompressed_bytes'] ?? null;
    $hash = $research['sha256'] ?? null;
    if (!is_string($data) || strlen($data) > 262144 || !is_int($size) || $size < 1 || $size > 1048576 || !is_string($hash) || !preg_match('/^[a-f0-9]{64}$/D', $hash)) return [];
    $compressed = base64_decode($data, true);
    if ($compressed === false) return [];
    $json = @gzdecode($compressed, 1048576);
    if (!is_string($json) || strlen($json) !== $size || !hash_equals($hash, hash('sha256', $json))) return [];
    $research = json_decode($json, true, 64);
  }
  return is_array($research) ? $research : [];
}

function finelevate360_research_rows($post_id) {
  $market = finelevate360_research_market($post_id);
  if (!$market) return [];
  $saved = get_post_meta($post_id, '_finelevate360_item_attrs', true);
  $saved = is_array($saved) ? $saved : [];
  $research = finelevate360_research_data($post_id);
  $attrs = get_posts(['post_type'=>FINELEVATE360_CPT_ATTR, 'post__in'=>finelevate360_research_attr_ids($market), 'numberposts'=>-1, 'orderby'=>'menu_order', 'order'=>'ASC', 'suppress_filters'=>true, 'post_status'=>'publish']);
  $rows = [];
  foreach ($attrs as $attr) {
    $id = (int)$attr->ID;
    $row = ['id'=>$id, 'title'=>$attr->post_title, 'evidence'=>$research['cells'][$id] ?? []];
    foreach (['mobile','desktop','biznis','privat'] as $channel) {
      // Legacy scalar and convenience fields cannot establish channel-level research.
      $row[$channel] = finelevate360_research_status(is_array($saved[$id] ?? null) ? ($saved[$id][$channel] ?? null) : null);
    }
    $rows[] = $row;
  }
  return $rows;
}

function finelevate360_research_counts($rows) {
  $counts = array_fill_keys(['yes','no','partial','unknown','not_applicable'],0);
  foreach ($rows as $row) foreach (['mobile','desktop','biznis','privat'] as $channel) $counts[finelevate360_research_status($row[$channel] ?? null)]++;
  $counts['total'] = array_sum($counts);
  $counts['covered'] = $counts['total'] - $counts['unknown'];
  return $counts;
}

function finelevate360_research_filter_map($rows) {
  $map = [];
  foreach ($rows as $row) foreach (['mobile','desktop','biznis','privat'] as $channel) {
    if ($row[$channel] === 'yes') $map[$row['id']][] = $channel;
  }
  return $map;
}

function finelevate360_research_matches($rows, $attrs, $channel) {
  $map = finelevate360_research_filter_map($rows);
  foreach ($attrs as $id) {
    if (empty($map[$id]) || ($channel && !in_array($channel,$map[$id],true))) return false;
  }
  if (!$attrs && $channel) {
    foreach ($map as $channels) if (in_array($channel,$channels,true)) return true;
    return false;
  }
  return true;
}

function finelevate360_research_summary($post_id) {
  $market = finelevate360_research_market($post_id);
  $rows = finelevate360_research_rows($post_id);
  $available = count(finelevate360_research_filter_map($rows));
  $total = count($rows);
  $percent = $total ? round(($available / $total) * 100) : 0;
  $caption = $market === 'sl' ? 'Potrjene funkcionalnosti' : 'Potvrđene funkcionalnosti';
  $note = $market === 'sl' ? 'Na voljo v vsaj enem kanalu; ni ocena zrelosti.' : 'Dostupno na barem jednom kanalu; nije ocjena zrelosti.';
  return '<div class="finelevate-filter-map-item__attr-count f360-research-summary" title="'.esc_attr($note).'"><span class="f360-attr-count__label">'.(int)$available.'/'.(int)$total.'</span><div class="f360-attr-count__bar" role="progressbar" aria-label="'.esc_attr($caption).'" aria-valuemin="0" aria-valuemax="'.(int)$total.'" aria-valuenow="'.(int)$available.'"><div class="f360-attr-count__fill" style="width:'.(int)$percent.'%"></div></div><span class="f360-research-caption">'.esc_html($caption).'</span></div>';
}

function finelevate360_research_legend($market) {
  $labels = finelevate360_research_labels($market);
  $marks = ['yes'=>'DA','partial'=>$market === 'sl' ? 'DELNO' : ($market === 'sr' ? 'DELIM.' : ($market === 'me' ? 'DJELIM.' : 'DJELOM.')),'no'=>'NE','unknown'=>'?','not_applicable'=>'—'];
  $html = '<style>
/* Research presentation only; the reference-market theme is unchanged. */
[data-research-market] .f360-research-summary{margin-top:auto;padding-top:0;font-size:13px;line-height:1.4;color:#315c96;font-weight:600}
[data-research-market] .f360-research-caption{display:block;font-size:10px;font-weight:400;color:#64748b}
[data-research-market] .finelevate-filter-map-item-desc{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.f360-research-legend{display:flex;flex-wrap:wrap;gap:6px 14px;align-items:center;margin:0 0 18px;padding:9px 12px;border:1px solid #eaeaea;border-radius:8px;font-size:11px;line-height:1.5}
.f360-research-legend span{white-space:nowrap}
.f360-research-legend .f360-state-yes,.f360-modal--research .f360-state-yes{color:#00805b;font-weight:600}
.f360-research-legend .f360-state-no,.f360-modal--research .f360-state-no{color:#8b4650}
.f360-research-legend .f360-state-partial,.f360-modal--research .f360-state-partial{color:#866000;font-weight:600}
.f360-research-legend .f360-state-unknown,.f360-modal--research .f360-state-unknown{color:#64748b}
.f360-research-legend .f360-state-not_applicable,.f360-modal--research .f360-state-not_applicable{color:#737373}
.f360-modal--research .f360-research-legend{margin:10px 0 0;padding:7px 0;border:0;border-radius:0;gap:4px 10px;font-size:10px}
.f360-modal--research .f360-modal__attrs-table{table-layout:fixed;margin-top:10px}
.f360-modal--research .f360-modal__attrs-table thead th:first-child{width:38%}
.f360-modal--research .f360-modal__attrs-table tbody th{font-weight:400;text-align:left;padding:8px 10px;font-size:12px;line-height:18px;border-bottom:1px solid #eaeaea}
.f360-modal--research .f360-modal__attrs-table tbody td{padding:8px 7px;line-height:18px;font-size:11px;white-space:nowrap}
.f360-modal--research .f360-modal__attrs-table thead th{overflow-wrap:anywhere}
.f360-modal--research .f360-modal__close{z-index:2}
@media(max-width:600px){
.f360-modal--research .f360-modal__drawer{padding:22px 16px 28px;max-width:100%;box-sizing:border-box}
.f360-modal--research .f360-modal__attrs-table{display:block;border:0}
.f360-modal--research .f360-modal__attrs-table tbody{display:block}
.f360-modal--research .f360-modal__attrs-table tbody th{display:block;padding:9px 12px;font-weight:600;background:#f9f8f8}
.f360-modal--research .f360-modal__attrs-table tbody td{font-size:12px;padding:8px 12px;gap:14px;white-space:normal}
.f360-modal--research .f360-modal__attrs-table tbody td:before{font-size:11px;text-align:left}
}
</style>';
  $html .= '<div class="f360-research-legend" aria-label="'.esc_attr($labels['legend']).'">';
  foreach (['yes','partial','no','unknown','not_applicable'] as $state) $html .= '<span class="f360-state-'.$state.'"><b>'.esc_html($marks[$state]).'</b> '.esc_html($labels[$state]).'</span>';
  return $html.'</div>';
}
// END HR/SI RESEARCH HELPERS
require_once FINELEVATE360_DIR_PATH . 'includes/metaboxes.php';
require_once FINELEVATE360_DIR_PATH . 'includes/regional-evidence.php';
require_once FINELEVATE360_DIR_PATH . 'includes/shortcodes.php';
require_once FINELEVATE360_DIR_PATH . 'includes/templates.php';
require_once FINELEVATE360_DIR_PATH . 'includes/admin-columns.php';
require_once FINELEVATE360_DIR_PATH . 'includes/admin-search.php';
require_once FINELEVATE360_DIR_PATH . 'includes/i18n.php';

/**
 * Show all partners on the partners archive page.
 * This prevents the default WordPress archive pagination of 10 items per page.
 */
add_action('pre_get_posts', function ($query) {
  if (is_admin() || !$query->is_main_query()) {
    return;
  }

  /**
   * Use both 'partner' and 'partners' to be safe.
   * If your CPT is only registered as 'partner', this still works.
   */
  if (is_post_type_archive(array('partner', 'partners'))) {
    $query->set('posts_per_page', -1);
    $query->set('no_found_rows', true);
    $query->set('orderby', 'menu_order');
    $query->set('order', 'ASC');
  }
});

// Enqueue frontend assets
add_action('wp_enqueue_scripts', function () {
  wp_enqueue_style(
    'finelevate360-main',
    FINELEVATE360_DIR_URL . 'assets/css/main.css',
    [],
    '1.0.12'
  );

  wp_enqueue_script(
    'finelevate360-modal',
    FINELEVATE360_DIR_URL . 'assets/js/modal.js',
    [],
    '1.0.10-staging-regional',
    true
  );

  // Локализирај i18n strings за JS
  wp_localize_script('finelevate360-modal', 'f360i18n', finelevate360_i18n());
});

register_activation_hook(__FILE__, function () {
  finelevate360_register_cpts();
  flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
  flush_rewrite_rules();
});
