<?php
if (!defined('ABSPATH')) exit;

/** Preserve the existing protected evidence schema through the native staging editor. */
function finelevate360_regional_evidence_market($post_id) {
  $market = finelevate360_research_market($post_id);
  return in_array($market, ['sr','me'], true) && finelevate360_regional_staging_config($market) ? $market : '';
}

function finelevate360_regional_metadata_hash($post_id) {
  return hash('sha256', serialize([
    get_post_meta($post_id, '_finelevate360_item_attrs', true),
    get_post_meta($post_id, '_finelevate360_item_research', true),
  ]));
}

/** Compare the exact channel values the native handler would save. */
function finelevate360_regional_matrix_unchanged($post_id) {
  $market = finelevate360_regional_evidence_market($post_id);
  if (!$market) return false;
  $saved = get_post_meta($post_id, '_finelevate360_item_attrs', true);
  $columns = is_array($_POST['finelevate360_item_attr_cols'] ?? null) ? $_POST['finelevate360_item_attr_cols'] : [];
  foreach (finelevate360_research_attr_ids($market) as $id) {
    foreach (['mobile','desktop','biznis','privat'] as $axis) {
      $next = finelevate360_research_status($columns[$id][$axis] ?? 'unknown');
      if (!is_array($saved[$id] ?? null) || ($saved[$id][$axis] ?? null) !== $next) return false;
    }
  }
  return true;
}

function finelevate360_regional_validate_evidence($json, $post_id) {
  $market = finelevate360_regional_evidence_market($post_id);
  if (!$market || !is_string($json) || strlen($json) > 1048576) throw new RuntimeException('Invalid staging evidence target or size.');
  $data = json_decode($json, true, 64);
  if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('Evidence must be valid JSON.');
  foreach (['schema_version','as_of','bank','cells','warnings','sources','publication_binding'] as $key) {
    if (!array_key_exists($key, $data)) throw new RuntimeException('Incomplete existing evidence schema.');
  }
  $binding = $data['publication_binding'];
  if (!is_array($binding) || ($binding['bank_post_id'] ?? null) !== (int)$post_id
    || ($binding['language'] ?? null) !== $market || ($data['bank']['language'] ?? null) !== $market
    || !is_string($data['bank']['bank_key'] ?? null)
    || strpos($data['bank']['bank_key'], $market.'_') !== 0) throw new RuntimeException('Evidence bank identity does not match this staging record.');
  if (!is_array($data['cells']) || !is_array($data['sources']) || !is_array($data['warnings'])) throw new RuntimeException('Invalid evidence collections.');
  $expected = finelevate360_research_attr_ids($market);
  $actual = array_keys($data['cells']); sort($expected); sort($actual);
  if ($actual !== $expected) throw new RuntimeException('Evidence must contain exactly the 26 reviewed staging attributes.');
  $sources = [];
  foreach ($data['sources'] as $source) {
    $id = $source['source_id'] ?? null; $url = $source['url'] ?? null;
    if (!is_string($id) || $id === '' || isset($sources[$id]) || !is_string($url)
      || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['https','http'], true)) throw new RuntimeException('Invalid or duplicate evidence source.');
    $sources[$id] = $url;
  }
  $pos_attribute = finelevate360_research_attr_ids($market)[11];
  foreach ($data['cells'] as $attribute_id => $cell) {
    if (!is_array($cell)) throw new RuntimeException('Invalid evidence row.');
    $axes = array_keys($cell); sort($axes);
    if ($axes !== ['biznis','desktop','mobile','privat']) throw new RuntimeException('Evidence must contain all four channels.');
    foreach ($cell as $axis => $evidence) {
      if (!is_array($evidence) || !is_array($evidence['source_ids'] ?? null) || !is_array($evidence['source_urls'] ?? null)
        || !is_string($evidence['evidence_note'] ?? null) || trim($evidence['evidence_note']) === '') throw new RuntimeException('Incomplete per-cell evidence.');
      $urls = [];
      foreach ($evidence['source_ids'] as $id) {
        if (!is_string($id) || !isset($sources[$id])) throw new RuntimeException('Unresolved per-cell source.');
        $urls[] = $sources[$id];
      }
      if (array_values(array_unique($urls)) !== array_values(array_unique($evidence['source_urls']))) throw new RuntimeException('Per-cell source URLs do not match the source references.');
      $state = $evidence['publication_status'] ?? null;
      if (!is_string($state) || !in_array($state, ['yes','partial','no','unknown','not_applicable'], true)) throw new RuntimeException('A reviewed evidence status is required.');
      if (in_array($state, ['yes','partial','no'], true) && !$urls) throw new RuntimeException('Definitive capability states require a resolved citation.');
      if ($state === 'unknown' && (($evidence['original_record']['status'] ?? null) !== 'Unverified'
        || !is_string($evidence['original_record']['evidence_summary'] ?? null)
        || trim($evidence['original_record']['evidence_summary']) === '')) throw new RuntimeException('Unverified requires its retained research-gap note.');
      if ($state === 'not_applicable') {
        $original = $evidence['original_record'] ?? [];
        if ((int)$attribute_id !== $pos_attribute || !in_array($axis, ['desktop','privat'], true)
          || ($original['applicable'] ?? null) !== false || ($original['feature_index'] ?? null) !== 12
          || ($original['axis_key'] ?? null) !== $axis || !is_string($original['evidence_summary'] ?? null)
          || trim($original['evidence_summary']) === '') throw new RuntimeException('Not applicable requires the agreed Digital POS browser rule and its rationale.');
      }
    }
  }
  return $data;
}

add_action('add_meta_boxes', function ($post_type, $post) {
  if ($post_type !== FINELEVATE360_CPT_ITEM || !is_object($post) || !finelevate360_regional_evidence_market($post->ID)) return;
  add_meta_box('finelevate360_regional_evidence', 'Reviewed capability evidence', function ($post) {
    wp_nonce_field('finelevate360_regional_evidence', 'finelevate360_regional_evidence_nonce');
    $data = finelevate360_research_data($post->ID);
    echo '<p>Protected per-cell sources and caveats, using the existing research schema. Evidence is visible to editors and is not included in the public map payload.</p>';
    echo '<input type="hidden" name="finelevate360_regional_expected_hash" value="'.esc_attr(finelevate360_regional_metadata_hash($post->ID)).'">';
    echo '<p><label><input type="checkbox" id="finelevate360_regional_preserve_scores" data-post-id="'.(int)$post->ID.'" data-nonce="'.esc_attr(wp_create_nonce('finelevate360_regional_scores_'.$post->ID)).'"> Preserve unrelated SEO scores for this reviewed capability publication</label></p>';
    echo '<label for="finelevate360_regional_evidence_json">Research evidence JSON</label>';
    echo '<textarea id="finelevate360_regional_evidence_json" name="finelevate360_regional_evidence_json" rows="12" style="width:100%">'.esc_textarea($data ? wp_json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '').'</textarea>';
  }, FINELEVATE360_CPT_ITEM, 'normal', 'default');
}, 10, 2);

add_action('admin_enqueue_scripts', function ($hook) {
  $id = (int)($_GET['post'] ?? 0);
  if ($hook !== 'post.php' || !finelevate360_regional_evidence_market($id)
    || get_post_type($id) !== FINELEVATE360_CPT_ITEM || !current_user_can('edit_post', $id)) return;
  $path = FINELEVATE360_DIR_PATH.'assets/js/regional-publication.js';
  wp_enqueue_script('finelevate360-regional-publication', FINELEVATE360_DIR_URL.'assets/js/regional-publication.js', ['wp-api-fetch'], (string)filemtime($path), true);
});

/** Rank Math's Classic Editor sends this separate REST request before editpost.
 * The opt-in nonce/hash travels with that request; native-save globals cannot.
 * Unset only the two scores: empty values would DELETE pre-existing metadata.
 */
function finelevate360_regional_preserve_rankmath_scores($meta, $request) {
  if (!is_object($request) || !method_exists($request, 'get_param')) return $meta;
  $context = $request->get_param('finelevate360_publication');
  if ($context === null) return $meta;
  $id = (int)$request->get_param('objectID');
  if (!finelevate360_regional_evidence_market($id) || get_post_type($id) !== FINELEVATE360_CPT_ITEM) return $meta;
  if ($request->get_route() !== '/rankmath/v1/updateMeta' || $request->get_method() !== 'POST'
    || $request->get_param('objectType') !== 'post' || !current_user_can('edit_post', $id)
    || !is_array($context) || ($context['post_id'] ?? null) !== $id
    || !is_string($context['nonce'] ?? null) || !wp_verify_nonce($context['nonce'], 'finelevate360_regional_scores_'.$id)
    || !is_string($context['expected_hash'] ?? null)
    || !hash_equals(finelevate360_regional_metadata_hash($id), $context['expected_hash']) || !is_array($meta)) {
    wp_die('The separate publication request is stale or unauthenticated; no SEO metadata was changed.');
  }
  unset($meta['rank_math_seo_score'], $meta['rank_math_contentai_score']);
  return $meta;
}
add_filter('rank_math/filter_metadata', 'finelevate360_regional_preserve_rankmath_scores', PHP_INT_MAX, 2);

// Pure validation is shared by early admin preflight and the native matrix save.
function finelevate360_regional_editor_payload($post_id) {
  $json = wp_unslash($_POST['finelevate360_regional_evidence_json'] ?? '');
  $has_evidence = is_string($json) && trim($json) !== '';
  if (!$has_evidence && finelevate360_regional_matrix_unchanged($post_id)) return null;
  if (!isset($_POST['finelevate360_regional_evidence_nonce'])
    || !wp_verify_nonce($_POST['finelevate360_regional_evidence_nonce'], 'finelevate360_regional_evidence')) throw new RuntimeException('Matching reviewed evidence and its editor nonce are required for a rating change.');
  $expected = $_POST['finelevate360_regional_expected_hash'] ?? '';
  if (!is_string($expected) || !hash_equals(finelevate360_regional_metadata_hash($post_id), $expected)) throw new RuntimeException('Ratings or evidence changed since this form was opened. Reopen the record before saving.');
  if (!$has_evidence) throw new RuntimeException('Matching reviewed evidence is required for a rating change.');
  $data = finelevate360_regional_validate_evidence($json, $post_id);
  $columns = $_POST['finelevate360_item_attr_cols'] ?? [];
  foreach (finelevate360_research_attr_ids(finelevate360_regional_evidence_market($post_id)) as $id) {
    foreach (['mobile','desktop','biznis','privat'] as $axis) {
      $value = $columns[$id][$axis] ?? null;
      if (!is_string($value) || !in_array($value, ['yes','partial','no','unknown','not_applicable'], true)) throw new RuntimeException('All 104 reviewed channel states are required.');
      if (($data['cells'][$id][$axis]['publication_status'] ?? null) !== $value) throw new RuntimeException('Evidence status does not match the reviewed channel state.');
    }
  }
  return $data;
}

add_action('save_post', function ($post_id, $post) {
  if (!is_object($post) || $post->post_type !== FINELEVATE360_CPT_ITEM
    || !finelevate360_regional_evidence_market($post_id) || wp_is_post_revision($post_id)
    || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)
    || !isset($_POST['finelevate360_meta_nonce'])
    || !wp_verify_nonce($_POST['finelevate360_meta_nonce'], 'finelevate360_save_meta')) return;
  try {$data=finelevate360_regional_editor_payload($post_id);} catch(Throwable $error) {wp_die(esc_html($error->getMessage()));}
  if ($data!==null) update_post_meta($post_id, '_finelevate360_item_research', wp_slash($data));
}, 5, 2);

/** Isolate one authorized research request, without changing Polylang options. */
function finelevate360_regional_begin_isolation($post_id, $repair = false) {
  global $finelevate360_regional_isolation;
  if (!finelevate360_regional_evidence_market($post_id) || !current_user_can('edit_post', $post_id)) wp_die('Invalid regional isolation target.');
  if (!function_exists('PLL') || !function_exists('pll_get_post_translations')
    || !defined('POLYLANG_VERSION') || POLYLANG_VERSION !== '3.9.1'
    || (defined('POLYLANG_PRO') && POLYLANG_PRO)) wp_die('The installed synchronization implementation requires review before this save.');
  $sync = PLL()->sync;
  if (!is_object($sync) || get_class($sync) !== 'PLL_Admin_Sync'
    || has_action('pll_save_post', [$sync, 'pll_save_post']) !== 10
    || has_filter('wp_insert_post_data', [$sync, 'can_sync_post_data']) !== 10
    || has_filter('wp_insert_post_parent', [$sync, 'can_sync_post_parent']) !== 10) wp_die('Unexpected Polylang synchronization callbacks; no regional save was started.');
  $family = array_values(array_unique(array_map('intval', pll_get_post_translations($post_id))));
  if (!in_array((int)$post_id, $family, true)) wp_die('Regional translation identity is incomplete.');
  foreach ($family as $id) if (get_post_type($id) !== FINELEVATE360_CPT_ITEM) wp_die('Unexpected translated record type.');
  if ($repair) {
    $families = [2487=>[2290,2305,2487],2488=>[2322,2323,2488]];
    $expected = $families[(int)$post_id] ?? []; $actual = $family; sort($actual);
    if (!$expected || $actual !== $expected || !current_user_can('manage_options')) wp_die('Repair is restricted to the captured translation families.');
  }
  $finelevate360_regional_isolation = ['source'=>(int)$post_id,'family'=>$family,'repair'=>(bool)$repair];

  // Register last, before post.php changes core terms or metadata. Keep active through late SEO saves.
  $copy_guard = function ($keys, $syncing, $from, $to, $language) {
    global $finelevate360_regional_isolation;
    $family = $finelevate360_regional_isolation['family'];
    return in_array((int)$from, $family, true) || in_array((int)$to, $family, true) ? [] : $keys;
  };
  add_filter('pll_copy_post_metas', $copy_guard, PHP_INT_MAX, 5);
  add_filter('pll_copy_taxonomies', $copy_guard, PHP_INT_MAX, 5);
  $metadata_guard = function ($check, $id, $key, $operation, $value) {
    global $finelevate360_regional_isolation;
    $context = $finelevate360_regional_isolation;
    if (!in_array((int)$id, $context['family'], true)) return $check;
    if (isset($context['logo_attachment'])) {
      $integer = is_int($value) || (is_string($value) && ctype_digit($value));
      return (int)$id === $context['source'] && $key === '_thumbnail_id'
        && in_array($operation, ['add','update'], true) && $integer
        && (int)$value === $context['logo_attachment']
        && !metadata_exists('post', $id, '_thumbnail_id') ? $check : false;
    }
    if ($context['repair']) {
      $deleting = $context['repair_delete'] ?? [];
      return $operation === 'delete' && ($deleting['id'] ?? null) === (int)$id && ($deleting['key'] ?? null) === $key ? $check : false;
    }
    $allowed = ['_finelevate360_item_attrs','_finelevate360_item_research'];
    foreach (finelevate360_research_attr_ids(finelevate360_regional_evidence_market($context['source'])) as $attr) $allowed[] = '_finelevate360_attr_'.$attr;
    return (int)$id === $context['source'] && in_array($key, $allowed, true) ? $check : false;
  };
  foreach (['add_post_metadata'=>'add','update_post_metadata'=>'update','delete_post_metadata'=>'delete'] as $hook=>$operation) {
    add_filter($hook, function ($check, $id, $key, $value) use ($metadata_guard, $operation) { return $metadata_guard($check, $id, $key, $operation, $value); }, PHP_INT_MAX, 4);
  }

  // The installed callback writes fields directly with $wpdb, bypassing wp_insert_post_data.
  remove_action('pll_save_post', [$sync, 'pll_save_post'], 10);
  add_action('pll_save_post', function ($id, $post, $translations) use ($sync) {
    global $finelevate360_regional_isolation;
    $family = $finelevate360_regional_isolation['family'];
    if (in_array((int)$id, $family, true)) return;
    $translations = array_diff($translations, $family);
    $sync->pll_save_post($id, $post, $translations);
  }, 10, 3);
  // Do not return false from the permission filter: that would reverse-sync into the edited record.
  remove_filter('wp_insert_post_data', [$sync, 'can_sync_post_data'], 10);
  add_filter('wp_insert_post_data', function ($data, $postarr) use ($sync) {
    global $finelevate360_regional_isolation;
    return in_array((int)($postarr['ID'] ?? 0), $finelevate360_regional_isolation['family'], true) ? $data : $sync->can_sync_post_data($data, $postarr);
  }, 10, 2);
  remove_filter('wp_insert_post_parent', [$sync, 'can_sync_post_parent'], 10);
  add_filter('wp_insert_post_parent', function ($parent, $id, $postarr) use ($sync) {
    global $finelevate360_regional_isolation;
    return in_array((int)($postarr['ID'] ?? 0), $finelevate360_regional_isolation['family'], true) ? $parent : $sync->can_sync_post_parent($parent, $id, $postarr);
  }, 10, 3);
  // Ratings change no core post fields. A repair may change only the reviewed menu order.
  add_filter('wp_insert_post_data', function ($data, $postarr) {
    global $finelevate360_regional_isolation;
    $context = $finelevate360_regional_isolation; $id = (int)($postarr['ID'] ?? 0);
    if (!in_array($id, $context['family'], true) || !($current = get_post($id))) return $data;
    foreach ($data as $key=>$value) {
      if ($context['repair'] && $key === 'menu_order') continue;
      if (property_exists($current, $key)) $data[$key] = wp_slash($current->$key);
    }
    return $data;
  }, PHP_INT_MAX, 2);
}

add_action('admin_init', function () {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_POST['action'] ?? '') !== 'editpost') return;
  $id = (int)($_POST['post_ID'] ?? 0);
  if (!finelevate360_regional_evidence_market($id)) return;
  if (!isset($_POST['finelevate360_meta_nonce']) || !wp_verify_nonce($_POST['finelevate360_meta_nonce'], 'finelevate360_save_meta')) wp_die('An authenticated research editor request is required before changing this regional bank.');
  try {finelevate360_regional_editor_payload($id);} catch(Throwable $error) {wp_die(esc_html($error->getMessage()));}
  finelevate360_regional_begin_isolation($id);
}, 0);

/** Exact, nonce-protected recovery of the recorded first-save side effects. */
function finelevate360_regional_repair($json) {
  if (!finelevate360_regional_staging_config('me') || !current_user_can('manage_options') || !is_string($json) || strlen($json)>65536) throw new RuntimeException('Invalid staging repair request.');
  $plan = json_decode($json, true, 32);
  if (is_array($plan) && ($plan['operation'] ?? '') === 'adriatic-score-additions') return finelevate360_regional_repair_adriatic_scores($plan);
  if (!is_array($plan) || !is_array($plan['records'] ?? null) || count($plan['records'])!==3) throw new RuntimeException('The captured three-record repair plan is required.');
  $ids = array_column($plan['records'], 'id');sort($ids);
  if ($ids !== [2290,2305,2487]) throw new RuntimeException('Repair targets do not match the captured family.');
  $orders = [2290=>86,2305=>87,2487=>88];$restore_terms = [2290=>[83],2305=>[138],2487=>[132]];
  $protected = [];
  foreach ($plan['records'] as $row) {
    $id = $row['id'];$post = get_post($id);
    if (!$post || get_post_type($id)!==FINELEVATE360_CPT_ITEM || !current_user_can('edit_post',$id)
      || $post->post_title!==($row['title']??null) || (int)$post->menu_order!==($row['expected_order']??null)
      || ($row['restore_order']??null)!==$orders[$id] || ($row['restore_terms']??null)!==$restore_terms[$id]) throw new RuntimeException('Repair identity or recorded field values do not match.');
    $terms = wp_get_post_terms($id, FINELEVATE360_TAX_ITEMCAT, ['fields'=>'ids']);
    if (is_wp_error($terms)) throw new RuntimeException('Could not read current bank categories.');
    $terms = array_map('intval',$terms);sort($terms);$expected_terms=$row['expected_terms']??null;
    if (!is_array($expected_terms) || $terms!==$expected_terms) throw new RuntimeException('Bank categories changed since capture.');
    foreach ($restore_terms[$id] as $term_id) {
      $term = get_term($term_id,FINELEVATE360_TAX_ITEMCAT);
      $slug = [83=>'banks-rs',138=>'banke-srbije',132=>'banks-me'][$term_id];
      if (!$term || is_wp_error($term) || $term->slug!==$slug) throw new RuntimeException('Captured category ID/slug mismatch.');
    }
    $protected[$id] = finelevate360_regional_metadata_hash($id);
    if (!is_string($row['protected_hash']??null) || !hash_equals($protected[$id],$row['protected_hash'])) throw new RuntimeException('Ratings/evidence changed since the repair capture.');
    $allowed = ['cmplz_hide_cookiebanner','cybocfi_hide_featured_image'];
    if ($id===2487) $allowed=array_merge($allowed,['rank_math_seo_score','rank_math_contentai_score','_edit_last']);
    $keys = array_keys($row['remove_metadata']??[]);sort($keys);sort($allowed);
    if ($keys!==$allowed) throw new RuntimeException('Only the captured newly created metadata may be removed.');
    foreach ($row['remove_metadata'] as $key=>$expected) {
      if (!metadata_exists('post',$id,$key) || get_post_meta($id,$key,true)!==$expected) throw new RuntimeException('Repair metadata changed since capture.');
    }
  }
  // All compare-and-swap checks pass before the first write.
  finelevate360_regional_begin_isolation(2487,true);
  global $finelevate360_regional_isolation;
  foreach ($plan['records'] as $row) {
    $id=$row['id'];
    if ($row['expected_order']!==$row['restore_order']) {
      $result=wp_update_post(['ID'=>$id,'menu_order'=>$row['restore_order']],true);
      if (is_wp_error($result)) throw new RuntimeException('Repair post update failed; inspect the retained plan before retrying.');
    }
    if ($row['expected_terms']!==$row['restore_terms']) {
      $result=wp_set_object_terms($id,$row['restore_terms'],FINELEVATE360_TAX_ITEMCAT,false);
      if (is_wp_error($result)) throw new RuntimeException('Repair taxonomy update failed; inspect before retrying.');
    }
    foreach ($row['remove_metadata'] as $key=>$expected) {
      $finelevate360_regional_isolation['repair_delete']=['id'=>$id,'key'=>$key];
      try {delete_post_meta($id,$key);} finally {unset($finelevate360_regional_isolation['repair_delete']);}
      if (metadata_exists('post',$id,$key)) throw new RuntimeException('Repair metadata removal failed; inspect before retrying.');
    }
    if (!hash_equals($protected[$id],finelevate360_regional_metadata_hash($id))) throw new RuntimeException('Protected ratings/evidence changed during repair.');
  }
}

/** Full captured state, including absence versus empty and all taxonomy IDs. */
function finelevate360_regional_bank_state_hash($id, $omit_thumbnail = false) {
  $post = get_post($id); if (!$post) throw new RuntimeException('Captured bank is missing.');
  $metadata = get_metadata('post', $id);
  if ($omit_thumbnail) unset($metadata['_thumbnail_id']);
  ksort($metadata);
  $taxonomies = [];
  foreach (get_post_taxonomies($id) as $tax) {
    $terms = wp_get_post_terms($id, $tax, ['fields'=>'ids']);
    if (is_wp_error($terms)) throw new RuntimeException('Could not read captured bank taxonomies.');
    $terms = array_map('intval', $terms); sort($terms); $taxonomies[$tax] = $terms;
  }
  ksort($taxonomies);
  return hash('sha256', serialize([(array)$post,$metadata,$taxonomies]));
}

/** Delete only the two known new score rows; no post or term update is issued. */
function finelevate360_regional_repair_adriatic_scores($plan) {
  if (!finelevate360_regional_staging_config('me') || !current_user_can('manage_options')
    || ($plan['id'] ?? null) !== 2488 || ($plan['title'] ?? null) !== 'Adriatic Bank AD Podgorica'
    || !finelevate360_regional_evidence_market(2488) || get_post(2488)->post_title !== $plan['title']) throw new RuntimeException('Invalid captured Adriatic repair target.');
  $family = $plan['family_state_hashes'] ?? [];
  $ids = array_map('intval',array_keys($family));sort($ids);
  if ($ids !== [2322,2323,2488]) throw new RuntimeException('The exact captured Adriatic family is required.');
  foreach ($ids as $id) {
    if (!current_user_can('edit_post',$id) || !is_string($family[$id])
      || !hash_equals(finelevate360_regional_bank_state_hash($id),$family[$id])) throw new RuntimeException('Captured Adriatic family changed; no repair started.');
  }
  $keys = ['rank_math_contentai_score','rank_math_seo_score'];
  $remove = $plan['remove_metadata'] ?? []; $actual = array_keys($remove);sort($actual);
  if ($actual !== $keys) throw new RuntimeException('Only the two captured score additions may be removed.');
  foreach ($remove as $key=>$entry) {
    if (!is_array($entry) || ($entry['before_absent'] ?? null) !== true || !array_key_exists('expected_value',$entry)
      || !metadata_exists('post',2488,$key) || get_post_meta(2488,$key,true) !== $entry['expected_value']) throw new RuntimeException('Captured score value/absence mismatch; no repair started.');
  }
  $protected = finelevate360_regional_metadata_hash(2488);
  finelevate360_regional_begin_isolation(2488,true);
  global $finelevate360_regional_isolation;
  foreach ($remove as $key=>$entry) {
    $finelevate360_regional_isolation['repair_delete']=['id'=>2488,'key'=>$key];
    try {delete_post_meta(2488,$key);} finally {unset($finelevate360_regional_isolation['repair_delete']);}
    if (metadata_exists('post',2488,$key)) throw new RuntimeException('Score removal failed; inspect current state before retrying.');
  }
  if (!hash_equals($protected,finelevate360_regional_metadata_hash(2488))) throw new RuntimeException('Protected Adriatic ratings/evidence changed.');
}

/** Reviewed staging bank and verified attachment identities only. */
function finelevate360_regional_logo_bindings() {
  return [
    2489 => [2982, 'Crnogorska Komercijalna Banka AD Podgorica', 'ckb.png'],
    2490 => [2983, 'Hipotekarna banka AD Podgorica', 'hipotekarna.png'],
    2491 => [2788, 'Zapad banka AD Podgorica', 'zapad-banka-logo.png'],
    2492 => [2789, 'Prva banka Crne Gore AD Podgorica', 'prva-banka-crne-gore-logo.jpg'],
    2493 => [2984, 'Lovćen banka AD Podgorica', 'lovcen.png'],
    2494 => [2986, 'Universal Capital Bank AD Podgorica', 'ucb.png'],
    2495 => [2985, 'ZIRAAT Bank Montenegro AD Podgorica', 'ziraat.jpg'],
  ];
}

function finelevate360_regional_logo_family_hash($source, $omit_source_thumbnail = false) {
  if (!function_exists('pll_get_post_translations')) throw new RuntimeException('Translation identities unavailable.');
  $family = array_values(array_unique(array_map('intval', pll_get_post_translations($source))));
  sort($family);
  if (!in_array((int)$source, $family, true)) throw new RuntimeException('Incomplete bank translation family.');
  $states = [];
  foreach ($family as $id) {
    if (get_post_type($id) !== FINELEVATE360_CPT_ITEM) throw new RuntimeException('Unexpected linked record type.');
    $states[$id] = finelevate360_regional_bank_state_hash($id, $omit_source_thumbnail && $id === (int)$source);
  }
  return hash('sha256', serialize($states));
}

/** Native tool POST: one thumbnail addition, without editpost or a Rank Math request. */
function finelevate360_regional_assign_logo($request) {
  $id = filter_var($request['finelevate360_logo_bank'] ?? null, FILTER_VALIDATE_INT);
  $attachment = filter_var($request['finelevate360_logo_attachment'] ?? null, FILTER_VALIDATE_INT);
  $binding = finelevate360_regional_logo_bindings()[$id] ?? null;
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !finelevate360_regional_staging_config('me')
    || !$binding || !$binding[0] || $attachment !== $binding[0]
    || !current_user_can('manage_options') || !current_user_can('edit_post', $id)
    || !is_string($request['finelevate360_logo_nonce'] ?? null)
    || !wp_verify_nonce($request['finelevate360_logo_nonce'], 'finelevate360_regional_logo_'.$id)
    || finelevate360_regional_evidence_market($id) !== 'me'
    || !get_post($id) || get_post($id)->post_title !== $binding[1]) throw new RuntimeException('Invalid or unauthorized staging logo assignment.');
  $image = get_post($attachment);
  if (!$image || $image->post_type !== 'attachment' || !wp_attachment_is_image($attachment)
    || basename((string)get_post_meta($attachment, '_wp_attached_file', true)) !== $binding[2]
    || !wp_get_attachment_image($attachment, 'thumbnail')) throw new RuntimeException('Mapped attachment is not the verified renderable bank image.');
  $expected = $request['finelevate360_logo_state'] ?? null;
  if (metadata_exists('post', $id, '_thumbnail_id') || !is_string($expected)
    || !hash_equals(finelevate360_regional_logo_family_hash($id), $expected)) throw new RuntimeException('Bank or linked records changed; reopen the logo tool before saving.');
  finelevate360_regional_begin_isolation($id);
  global $finelevate360_regional_isolation;
  $finelevate360_regional_isolation['logo_attachment'] = $attachment;
  // Core can delete on an invalid image: validation precedes this call, and deletion stays blocked.
  set_post_thumbnail($id, $attachment);
  if (!metadata_exists('post', $id, '_thumbnail_id') || (int)get_post_meta($id, '_thumbnail_id', true) !== $attachment)
    throw new RuntimeException('Logo did not persist; inspect current state before any retry.');
  if (!hash_equals($expected, finelevate360_regional_logo_family_hash($id, true)))
    throw new RuntimeException('Unexpected family difference; stop and inspect the authenticated export.');
  return $attachment;
}

add_action('admin_menu', function () {
  if (!finelevate360_regional_staging_config('me')) return;
  add_management_page('Regional preservation repair','Regional preservation repair','manage_options','finelevate360-regional-repair',function () {
    if (!current_user_can('manage_options')) wp_die('Administrator access required.');
    echo '<div class="wrap"><h1>Regional preservation repair</h1><p>Restore only the captured Erste side effects or the two new Adriatic score rows. Ratings, evidence, categories and unrelated fields are preserved. The full plan is checked against current values before any write.</p>';
    $adriatic_hashes=[];foreach([2322,2323,2488] as $id) $adriatic_hashes[$id]=finelevate360_regional_bank_state_hash($id);
    echo '<p>Current Adriatic family state hashes for the independently captured repair plan:</p><pre id="finelevate360-adriatic-state-hashes">'.esc_html(wp_json_encode($adriatic_hashes)).'</pre>';
    if (($_SERVER['REQUEST_METHOD']??'')==='POST' && isset($_POST['finelevate360_logo_bank'])) {
      try {finelevate360_regional_assign_logo(wp_unslash($_POST));echo '<div class="notice notice-success"><p>Mapped bank logo persisted. Verify the authenticated export and rendered map.</p></div>';}
      catch(Throwable $error) {echo '<div class="notice notice-error"><p>Stopped: '.esc_html($error->getMessage()).'</p></div>';}
    } elseif (($_SERVER['REQUEST_METHOD']??'')==='POST') {
      check_admin_referer('finelevate360_regional_repair');
      try {finelevate360_regional_repair(wp_unslash($_POST['finelevate360_repair_plan']??''));echo '<div class="notice notice-success"><p>Captured side effects restored. Verify the authenticated export before further publication.</p></div>';}
      catch(Throwable $error) {echo '<div class="notice notice-error"><p>Stopped: '.esc_html($error->getMessage()).'</p></div>';}
    }
    echo '<h2>Approved Montenegro bank logos</h2><p>Each action adds only its verified image to a bank that still has no featured-image metadata. Capability data and linked records are checked and preserved.</p>';
    foreach (finelevate360_regional_logo_bindings() as $id=>$binding) {
      echo '<form method="post"><p>'.esc_html($binding[1]).' ';
      if (!$binding[0] || metadata_exists('post',$id,'_thumbnail_id')) {echo '<span>Unavailable: upload verification pending or image already assigned.</span></p></form>';continue;}
      wp_nonce_field('finelevate360_regional_logo_'.$id,'finelevate360_logo_nonce');
      echo '<input type="hidden" name="finelevate360_logo_bank" value="'.(int)$id.'"><input type="hidden" name="finelevate360_logo_attachment" value="'.(int)$binding[0].'"><input type="hidden" name="finelevate360_logo_state" value="'.esc_attr(finelevate360_regional_logo_family_hash($id)).'"><button class="button" type="submit">Assign verified logo</button></p></form>';
    }
    echo '<form method="post">';wp_nonce_field('finelevate360_regional_repair');
    echo '<label for="finelevate360_repair_plan">Verified repair plan JSON</label><textarea id="finelevate360_repair_plan" name="finelevate360_repair_plan" rows="12" style="display:block;width:100%"></textarea><p><button class="button button-primary" type="submit">Restore captured side effects</button></p></form></div>';
  });
});
