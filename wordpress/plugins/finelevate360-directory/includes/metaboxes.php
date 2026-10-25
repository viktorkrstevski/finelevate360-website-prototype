<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'finelevate360_add_meta_boxes');
add_action('save_post', 'finelevate360_save_meta', 10, 2);

function finelevate360_add_meta_boxes() {
  add_meta_box(
    'finelevate360_partner_fields',
    'Partner Fields',
    'finelevate360_render_partner_metabox',
    FINELEVATE360_CPT_PARTNERS,
    'normal',
    'default'
  );

  add_meta_box(
    'finelevate360_advisory_fields',
    'Advisory Fields',
    'finelevate360_render_advisory_metabox',
    FINELEVATE360_CPT_ADVISORY,
    'normal',
    'default'
  );

  add_meta_box(
    'finelevate360_attr_description',
    'Attribute Description',
    'finelevate360_render_attr_description_metabox',
    FINELEVATE360_CPT_ATTR,
    'normal',
    'default'
  );

  add_meta_box(
    'finelevate360_item_attributes_yesno',
    'Attributes',
    'finelevate360_render_item_attributes_metabox',
    FINELEVATE360_CPT_ITEM,
    'normal',
    'default'
  );
}

function finelevate360_get_meta($post_id, $key, $default = '') {
  $v = get_post_meta($post_id, $key, true);
  return $v !== '' ? $v : $default;
}

function finelevate360_admin_field_styles() {
  ?>
  <style>
    .finelevate360-field { margin: 12px 0; }
    .finelevate360-field label { display:block; font-weight:600; margin-bottom:6px; }
    .finelevate360-field input, .finelevate360-field textarea { width:100%; max-width: 900px; }
    .finelevate360-help { color:#666; font-size:12px; margin-top:4px; }
  </style>
  <?php
}

function finelevate360_render_partner_metabox($post) {
  wp_nonce_field('finelevate360_save_meta', 'finelevate360_meta_nonce');
  finelevate360_admin_field_styles();

  $badge_text  = finelevate360_get_meta($post->ID, '_finelevate360_partner_badge_text');
  $badge_class = finelevate360_get_meta($post->ID, '_finelevate360_partner_badge_class');
  $contact     = finelevate360_get_meta($post->ID, '_finelevate360_partner_contact');
  $website     = finelevate360_get_meta($post->ID, '_finelevate360_partner_website');
  $linkedin    = finelevate360_get_meta($post->ID, '_finelevate360_partner_linkedin');
  ?>
  <div class="finelevate360-field">
    <label for="finelevate360_partner_badge_text">Badge Text</label>
    <input type="text" id="finelevate360_partner_badge_text" name="finelevate360_partner_badge_text" value="<?php echo esc_attr($badge_text); ?>" />
    <div class="finelevate360-help">Text shown as a badge on the frontend.</div>
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_partner_badge_class">Badge Type</label>
    <select id="finelevate360_partner_badge_class" name="finelevate360_partner_badge_class">
      <option value="">— None —</option>
      <option value="gold" <?php selected($badge_class, 'gold'); ?>>Gold</option>
      <option value="honorary" <?php selected($badge_class, 'honorary'); ?>>Honorary</option>
      <option value="silver" <?php selected($badge_class, 'silver'); ?>>Silver</option>
    </select>
    <div class="finelevate360-help">Predefined badge styles.</div>
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_partner_contact">Contact</label>
    <textarea id="finelevate360_partner_contact" name="finelevate360_partner_contact" rows="4"><?php echo esc_textarea($contact); ?></textarea>
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_partner_website">Website Link</label>
    <input type="url" id="finelevate360_partner_website" name="finelevate360_partner_website" value="<?php echo esc_attr($website); ?>" placeholder="https://example.com" />
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_partner_linkedin">LinkedIn Link</label>
    <input type="url" id="finelevate360_partner_linkedin" name="finelevate360_partner_linkedin" value="<?php echo esc_attr($linkedin); ?>" placeholder="https://linkedin.com/company/..." />
  </div>
  <?php
}

function finelevate360_render_advisory_metabox($post) {
  wp_nonce_field('finelevate360_save_meta', 'finelevate360_meta_nonce');
  finelevate360_admin_field_styles();

  $job_title = finelevate360_get_meta($post->ID, '_finelevate360_advisory_job_title');
  $company   = finelevate360_get_meta($post->ID, '_finelevate360_advisory_company');
  $contact   = finelevate360_get_meta($post->ID, '_finelevate360_advisory_contact');
  $website   = finelevate360_get_meta($post->ID, '_finelevate360_advisory_website');
  $linkedin  = finelevate360_get_meta($post->ID, '_finelevate360_advisory_linkedin');
  ?>
  <div class="finelevate360-field">
    <label for="finelevate360_advisory_job_title">Job Title / Role</label>
    <input type="text" id="finelevate360_advisory_job_title" name="finelevate360_advisory_job_title" value="<?php echo esc_attr($job_title); ?>" placeholder="e.g. Senior Advisor, CFO..." />
    <div class="finelevate360-help">Displayed below the name on the card and in the modal.</div>
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_advisory_company">Company</label>
    <input type="text" id="finelevate360_advisory_company" name="finelevate360_advisory_company" value="<?php echo esc_attr($company); ?>" placeholder="e.g. company name" />
    <div class="finelevate360-help">Displayed below the name on the card and in the modal.</div>
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_advisory_contact">Contact</label>
    <textarea id="finelevate360_advisory_contact" name="finelevate360_advisory_contact" rows="4"><?php echo esc_textarea($contact); ?></textarea>
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_advisory_website">Website Link</label>
    <input type="url" id="finelevate360_advisory_website" name="finelevate360_advisory_website" value="<?php echo esc_attr($website); ?>" placeholder="https://example.com" />
  </div>

  <div class="finelevate360-field">
    <label for="finelevate360_advisory_linkedin">LinkedIn Link</label>
    <input type="url" id="finelevate360_advisory_linkedin" name="finelevate360_advisory_linkedin" value="<?php echo esc_attr($linkedin); ?>" placeholder="https://linkedin.com/in/..." />
  </div>
  <?php
}

// ── Attribute description metabox ─────────────────────────────────────────────
function finelevate360_render_attr_description_metabox( $post ) {
  wp_nonce_field( 'finelevate360_save_meta', 'finelevate360_meta_nonce' );
  finelevate360_admin_field_styles();
  $desc = finelevate360_get_meta( $post->ID, '_finelevate360_attr_description' );
  ?>
  <div class="finelevate360-field">
    <label for="finelevate360_attr_description">Description</label>
    <textarea id="finelevate360_attr_description" name="finelevate360_attr_description" rows="4"><?php echo esc_textarea( $desc ); ?></textarea>
    <div class="finelevate360-help">Shown as a tooltip/popup in the frontend filter next to the attribute checkbox.</div>
  </div>
  <?php
}

// ── Columns for the item attributes metabox ──────────────────────────────────
// Add more entries here to introduce additional columns.
function finelevate360_attr_columns() {
  return [
    'mobile'  => 'Mobile Private',
    'desktop' => 'WEB Private',
    'biznis'  => 'Mobile Business',
    'privat'  => 'WEB Business',
  ];
}

function finelevate360_render_item_attributes_metabox($post) {
  wp_nonce_field('finelevate360_save_meta', 'finelevate360_meta_nonce');
  finelevate360_admin_field_styles();

  $research_market = finelevate360_research_market($post->ID);
  $term_ids = wp_get_post_terms($post->ID, FINELEVATE360_TAX_ITEMCAT, ['fields' => 'ids']);
  $term_id  = !empty($term_ids) ? (int) $term_ids[0] : 0;

  if (!$term_id && !$research_market) {
    echo '<p><em>Please select an Item Category (right sidebar) to load relevant attributes.</em></p>';
    return;
  }

$attrs = get_posts([
    'post_type'   => FINELEVATE360_CPT_ATTR,
    'numberposts' => -1,
    'orderby'     => 'menu_order',
    'order'       => 'ASC',
    'suppress_filters' => true,
    'tax_query'   => [[
        'taxonomy' => FINELEVATE360_TAX_ITEMCAT,
        'field'    => 'term_id',
        'terms'    => $term_id,
    ]],
]);

  if ($research_market) $attrs = get_posts(['post_type'=>FINELEVATE360_CPT_ATTR,'numberposts'=>-1,'post__in'=>finelevate360_research_attr_ids($research_market),'orderby'=>'menu_order','order'=>'ASC','suppress_filters'=>true]);
  if (empty($attrs)) {
    echo '<p><em>No attributes found in this category.</em></p>';
    return;
  }

  // Saved data: { attr_id: { mobile: 'yes'/'no', desktop: 'yes'/'no' } }
  $saved = get_post_meta($post->ID, '_finelevate360_item_attrs', true);
  if (!is_array($saved)) $saved = [];

  $attr_map = [];
  foreach ($attrs as $a) {
    $attr_map[(int)$a->ID] = $a->post_title;
  }

  // $saved = array_intersect_key($saved, $attr_map);
  $merged = [];
  foreach ($attr_map as $attr_id => $attr_title) {
      if (isset($saved[$attr_id])) {
          // Ако веќе постои — задржи ја вредноста
          $merged[$attr_id] = $saved[$attr_id];
      } else {
          // Ако е нов атрибут — додај го со default 'no'
          $merged[$attr_id] = $research_market ? 'unknown' : 'no';
      }
  }
  $saved = $merged;
  $columns = finelevate360_attr_columns();
  $research_options = $research_market ? finelevate360_research_labels($research_market) : [];
  ?>
  <style>
    #finelevate360_attr_table th,
    #finelevate360_attr_table td { vertical-align: middle; padding: 8px 10px; }
    #finelevate360_attr_table select { min-width: 80px; }
    .f360-col-remove { width: 80px; text-align: center; }
  </style>

  <div class="finelevate360-field">
    <label>Add attribute</label>
    <input type="text" id="finelevate360_attr_search" placeholder="Type to search…" style="max-width:300px;" />
    <select id="finelevate360_attr_pick" style="max-width:300px;">
      <option value="">— Select attribute —</option>
      <?php foreach ($attrs as $a): ?>
        <option value="<?php echo (int)$a->ID; ?>"><?php echo esc_html($a->post_title); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" class="button" id="finelevate360_attr_add" style="margin-left:8px;">Add</button>
    <div class="finelevate360-help">Attributes are filtered by the selected Item Category.</div>
  </div>

  <table class="widefat striped" id="finelevate360_attr_table">
    <thead>
      <tr>
        <th>Attribute</th>
        <?php foreach ($columns as $col_key => $col_label): ?>
          <th><?php echo esc_html($col_label); ?></th>
        <?php endforeach; ?>
        <th class="f360-col-remove">Remove</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($saved as $attr_id => $col_vals):
        $attr_id  = (int) $attr_id;
        $col_vals = is_array($col_vals) ? $col_vals : [];
        // Back-compat: old format was scalar 'yes'/'no'
        if (!is_array($col_vals)) $col_vals = [];
      ?>
        <tr data-attr-id="<?php echo $attr_id; ?>">
          <td>
            <strong><?php echo esc_html($attr_map[$attr_id]); ?></strong>
            <input type="hidden" name="finelevate360_item_attr_ids[]" value="<?php echo $attr_id; ?>" />
          </td>
          <?php foreach ($columns as $col_key => $col_label): 
            $val = $research_market ? finelevate360_research_status($col_vals[$col_key] ?? null) : ($col_vals[$col_key] ?? 'no');
          ?>
            <td>
              <select name="finelevate360_item_attr_cols[<?php echo $attr_id; ?>][<?php echo esc_attr($col_key); ?>]">
                <?php if ($research_market): foreach (["unknown","yes","partial","no","not_applicable"] as $state): ?>
                <option value="<?php echo esc_attr($state); ?>" <?php selected($val,$state); ?>><?php echo esc_html($research_options[$state]); ?></option>
                <?php endforeach; else: ?>
                <option value="no"  <?php selected($val, 'no');  ?>>NO</option>
                <option value="yes" <?php selected($val, 'yes'); ?>>YES</option>
                <?php endif; ?>
              </select>
            </td>
          <?php endforeach; ?>
          <td class="f360-col-remove">
            <button type="button" class="button link-delete finelevate360_attr_remove">Remove</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <script>
  (function(){
    const researchOptions = <?php echo wp_json_encode($research_market ? array_intersect_key($research_options,array_flip(['unknown','yes','partial','no','not_applicable'])) : null); ?>;
    const attrMap = <?php echo wp_json_encode($attr_map); ?>;
    const columns = <?php echo wp_json_encode(array_keys($columns)); ?>;
    const search  = document.getElementById('finelevate360_attr_search');
    const pick    = document.getElementById('finelevate360_attr_pick');
    const addBtn  = document.getElementById('finelevate360_attr_add');
    const tbody   = document.getElementById('finelevate360_attr_table').querySelector('tbody');

    function rowExists(id) {
      return !!tbody.querySelector('tr[data-attr-id="' + id + '"]');
    }

    function makeSelect(attrId, colKey) {
      if (researchOptions) return `<select name="finelevate360_item_attr_cols[${attrId}][${colKey}]">` + Object.keys(researchOptions).map(state => `<option value="${state}">${researchOptions[state]}</option>`).join('') + '</select>';
      return `<select name="finelevate360_item_attr_cols[${attrId}][${colKey}]">
        <option value="no">NO</option>
        <option value="yes">YES</option>
      </select>`;
    }

    function addRow(id) {
      id = parseInt(id, 10);
      if (!id || !attrMap[id] || rowExists(id)) return;

      let colCells = '';
      columns.forEach(function(col) {
        colCells += `<td>${makeSelect(id, col)}</td>`;
      });

      const tr = document.createElement('tr');
      tr.setAttribute('data-attr-id', String(id));
      tr.innerHTML = `
        <td>
          <strong>${attrMap[id]}</strong>
          <input type="hidden" name="finelevate360_item_attr_ids[]" value="${id}" />
        </td>
        ${colCells}
        <td class="f360-col-remove">
          <button type="button" class="button link-delete finelevate360_attr_remove">Remove</button>
        </td>`;
      tbody.appendChild(tr);
    }

    search.addEventListener('input', function() {
      const q = this.value.toLowerCase();
      Array.from(pick.options).forEach(function(opt) {
        if (!opt.value) return;
        opt.hidden = q && !opt.text.toLowerCase().includes(q);
      });
    });

    addBtn.addEventListener('click', function() {
      addRow(pick.value);
      pick.value = '';
      search.value = '';
      Array.from(pick.options).forEach(function(o) { o.hidden = false; });
    });

    tbody.addEventListener('click', function(e) {
      const btn = e.target.closest('.finelevate360_attr_remove');
      if (btn) btn.closest('tr').remove();
    });
  })();
  </script>
  <?php
}

function finelevate360_save_meta($post_id, $post) {
  if (!is_object($post)) return;

  $allowed = [FINELEVATE360_CPT_PARTNERS, FINELEVATE360_CPT_ADVISORY, FINELEVATE360_CPT_ITEM, FINELEVATE360_CPT_ATTR];
  if (!in_array($post->post_type, $allowed, true)) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (wp_is_post_revision($post_id)) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (!isset($_POST['finelevate360_meta_nonce']) || !wp_verify_nonce($_POST['finelevate360_meta_nonce'], 'finelevate360_save_meta')) return;

  if ($post->post_type === FINELEVATE360_CPT_PARTNERS) {
    update_post_meta($post_id, '_finelevate360_partner_badge_text',  sanitize_text_field($_POST['finelevate360_partner_badge_text']  ?? ''));
    update_post_meta($post_id, '_finelevate360_partner_badge_class', sanitize_html_class($_POST['finelevate360_partner_badge_class'] ?? ''));
    update_post_meta($post_id, '_finelevate360_partner_contact',     sanitize_textarea_field($_POST['finelevate360_partner_contact'] ?? ''));
    update_post_meta($post_id, '_finelevate360_partner_website',     esc_url_raw($_POST['finelevate360_partner_website']  ?? ''));
    update_post_meta($post_id, '_finelevate360_partner_linkedin',    esc_url_raw($_POST['finelevate360_partner_linkedin'] ?? ''));
  }

  if ($post->post_type === FINELEVATE360_CPT_ADVISORY) {
    update_post_meta($post_id, '_finelevate360_advisory_job_title', sanitize_text_field($_POST['finelevate360_advisory_job_title'] ?? ''));
    update_post_meta($post_id, '_finelevate360_advisory_company',   sanitize_text_field($_POST['finelevate360_advisory_company']   ?? ''));
    update_post_meta($post_id, '_finelevate360_advisory_contact',   sanitize_textarea_field($_POST['finelevate360_advisory_contact'] ?? ''));
    update_post_meta($post_id, '_finelevate360_advisory_website',   esc_url_raw($_POST['finelevate360_advisory_website']  ?? ''));
    update_post_meta($post_id, '_finelevate360_advisory_linkedin',  esc_url_raw($_POST['finelevate360_advisory_linkedin'] ?? ''));
  }

  if ($post->post_type === FINELEVATE360_CPT_ATTR) {
    $desc = sanitize_textarea_field( $_POST['finelevate360_attr_description'] ?? '' );
    update_post_meta( $post_id, '_finelevate360_attr_description', $desc );
  }

  if ($post->post_type === FINELEVATE360_CPT_ITEM) {
    $ids     = array_values(array_filter(array_map('intval', (array)($_POST['finelevate360_item_attr_ids'] ?? []))));
    $cols_in = is_array($_POST['finelevate360_item_attr_cols'] ?? null) ? $_POST['finelevate360_item_attr_cols'] : [];
    $columns = array_keys(finelevate360_attr_columns());

    $research_market = finelevate360_research_market($post_id);
    $archived = finelevate360_archived_attribute_aliases();
    $existing = get_post_meta($post_id, '_finelevate360_item_attrs', true);
    // Historical cells remain private and unchanged on later normal editor saves.
    $out = is_array($existing) ? array_intersect_key($existing, $archived) : [];
    $ids = array_values(array_diff($ids, array_keys($archived)));
    if ($research_market) {
      $ids = finelevate360_research_attr_ids($research_market);
      $existing = get_post_meta($post_id, '_finelevate360_item_attrs', true);
      $out = is_array($existing) ? $existing : [];
    }
    foreach ($ids as $attr_id) {
      $col_vals = [];
      foreach ($columns as $col_key) {
        $raw = $cols_in[$attr_id][$col_key] ?? ($research_market ? 'unknown' : 'no');
        $col_vals[$col_key] = $research_market ? finelevate360_research_status($raw) : (($raw === 'yes') ? 'yes' : 'no');
      }
      $out[$attr_id] = $col_vals;

      // Per-attr convenience meta: 'yes' if ANY column is yes
      $any_yes = in_array('yes', $col_vals, true) ? 'yes' : 'no';
      if ($research_market && $any_yes !== 'yes') {
        $any_yes = in_array('partial',$col_vals,true) ? 'partial' : (in_array('unknown',$col_vals,true) ? 'unknown' : (in_array('no',$col_vals,true) ? 'no' : 'not_applicable'));
      }
      update_post_meta($post_id, '_finelevate360_attr_' . $attr_id, $any_yes);
    }

    update_post_meta($post_id, '_finelevate360_item_attrs', $out);
  }
}
?>