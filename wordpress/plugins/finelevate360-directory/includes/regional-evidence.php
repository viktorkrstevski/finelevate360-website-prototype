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
  foreach ($data['cells'] as $cell) {
    if (!is_array($cell)) throw new RuntimeException('Invalid evidence row.');
    $axes = array_keys($cell); sort($axes);
    if ($axes !== ['biznis','desktop','mobile','privat']) throw new RuntimeException('Evidence must contain all four channels.');
    foreach ($cell as $evidence) {
      if (!is_array($evidence) || !is_array($evidence['source_ids'] ?? null) || !is_array($evidence['source_urls'] ?? null)
        || !is_string($evidence['evidence_note'] ?? null) || trim($evidence['evidence_note']) === '') throw new RuntimeException('Incomplete per-cell evidence.');
      $urls = [];
      foreach ($evidence['source_ids'] as $id) {
        if (!is_string($id) || !isset($sources[$id])) throw new RuntimeException('Unresolved per-cell source.');
        $urls[] = $sources[$id];
      }
      if (array_values(array_unique($urls)) !== array_values(array_unique($evidence['source_urls']))) throw new RuntimeException('Per-cell source URLs do not match the source references.');
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
    echo '<label for="finelevate360_regional_evidence_json">Research evidence JSON</label>';
    echo '<textarea id="finelevate360_regional_evidence_json" name="finelevate360_regional_evidence_json" rows="12" style="width:100%">'.esc_textarea($data ? wp_json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '').'</textarea>';
  }, FINELEVATE360_CPT_ITEM, 'normal', 'default');
}, 10, 2);

// Validate before the normal matrix save. An outdated form cannot overwrite newer ratings/evidence.
add_action('save_post', function ($post_id, $post) {
  if (!is_object($post) || $post->post_type !== FINELEVATE360_CPT_ITEM
    || !finelevate360_regional_evidence_market($post_id) || wp_is_post_revision($post_id)
    || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)
    || !isset($_POST['finelevate360_regional_evidence_nonce'])) return;
  if (!wp_verify_nonce($_POST['finelevate360_regional_evidence_nonce'], 'finelevate360_regional_evidence')
    || !isset($_POST['finelevate360_meta_nonce'])
    || !wp_verify_nonce($_POST['finelevate360_meta_nonce'], 'finelevate360_save_meta')) wp_die('Invalid staging editor nonce.');
  $expected = $_POST['finelevate360_regional_expected_hash'] ?? '';
  if (!is_string($expected) || !hash_equals(finelevate360_regional_metadata_hash($post_id), $expected)) wp_die('Ratings or evidence changed since this form was opened. Reopen the record before saving.');
  $json = wp_unslash($_POST['finelevate360_regional_evidence_json'] ?? '');
  if (!is_string($json) || trim($json) === '') return;
  try {
    $data = finelevate360_regional_validate_evidence($json, $post_id);
    $columns = $_POST['finelevate360_item_attr_cols'] ?? [];
    foreach (finelevate360_research_attr_ids(finelevate360_regional_evidence_market($post_id)) as $id) {
      foreach (['mobile','desktop','biznis','privat'] as $axis) {
        $value = $columns[$id][$axis] ?? null;
        if (!is_string($value) || !in_array($value, ['yes','partial','no','unknown','not_applicable'], true)) throw new RuntimeException('All 104 reviewed channel states are required.');
        if (($data['cells'][$id][$axis]['publication_status'] ?? null) !== $value) throw new RuntimeException('Evidence status does not match the reviewed channel state.');
      }
    }
  } catch (Throwable $error) { wp_die(esc_html($error->getMessage())); }
  // Validation completed before either research metadata field is modified.
  update_post_meta($post_id, '_finelevate360_item_research', wp_slash($data));
}, 5, 2);
