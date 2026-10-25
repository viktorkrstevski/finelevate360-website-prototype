<?php
if (!defined('ABSPATH')) exit;

add_shortcode('finelevate360_partners', 'finelevate360_shortcode_partners');
add_shortcode('finelevate360_advisory', 'finelevate360_shortcode_advisory');
add_shortcode('finelevate360_items',    'finelevate360_shortcode_items');
add_shortcode('finelevate360_count_items', 'finelevate360_shortcode_count_items');
add_shortcode('finelevate360_count_attrs', 'finelevate360_shortcode_count_attrs');

// Register modal HTML in footer + localize modal.js strings
add_action('wp_footer', 'finelevate360_modal_html');
add_action('wp_enqueue_scripts', 'finelevate360_modal_enqueue');

function finelevate360_modal_enqueue() {
  wp_register_script(
    'finelevate360-modal',
    plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/modal.js',
    [],
    finelevate360_research_page_market() ? '1.0.10-hr-si-20261020-visual3' : (defined('FINELEVATE360_VERSION') ? FINELEVATE360_VERSION : '1.0.0'),
    true
  );
  wp_localize_script( 'finelevate360-modal', 'f360i18n', finelevate360_i18n() );
  wp_enqueue_script( 'finelevate360-modal' );
}

function finelevate360_modal_html() {
  static $printed = false;
  if ( $printed ) return;
  $printed = true;
  ?>
  <div id="f360-modal" class="f360-modal" onclick="f360BgClose(event,this)" role="dialog" aria-modal="true">
    <div class="f360-modal__drawer"></div>
  </div>
  <?php
}

// Back-compat alias
if (!function_exists('finelevate360_assets_once')) {
  function finelevate360_assets_once() {
    if (function_exists('finelevate360_cards_css_once')) {
      return finelevate360_cards_css_once();
    }
    return '';
  }
}

/**
 * Returns structured array for item attributes:
 * [ [ 'title' => 'Attr name', 'mobile' => 'yes', 'desktop' => 'no' ], ... ]
 * Order follows menu_order of the attribute posts.
 */
function finelevate360_get_item_attributes( $post_id ) {
  if (finelevate360_research_market($post_id)) return finelevate360_research_rows($post_id);
  $saved = get_post_meta( $post_id, '_finelevate360_item_attrs', true );

  if ( is_array( $saved ) && ! empty( $saved ) ) {
    $attr_ids = array_keys( $saved );

    $attr_posts = get_posts([
      'post_type'        => FINELEVATE360_CPT_ATTR,
      'include'          => $attr_ids,
      'numberposts'      => -1,
      'orderby'          => 'menu_order',
      'order'            => 'ASC',
      'suppress_filters' => true,
    ]);

    // Итерирај по menu_order редослед на attr_posts
    $result = [];
    foreach ( $attr_posts as $a ) {
      $attr_id  = $a->ID;
      if ( ! isset( $saved[ $attr_id ] ) ) continue;
      $col_vals = $saved[ $attr_id ];

      if ( ! is_array( $col_vals ) ) {
        // Back-compat: old scalar format
        $result[] = [
          'title'   => $a->post_title,
          'mobile'  => $col_vals,
          'desktop' => $col_vals,
          'biznis'  => $col_vals,
          'privat'  => $col_vals,
        ];
        continue;
      }

      $row = [ 'title' => $a->post_title ];
      foreach ( ['mobile', 'desktop', 'biznis', 'privat'] as $col ) {
        $row[ $col ] = isset( $col_vals[ $col ] ) ? $col_vals[ $col ] : 'no';
      }
      $result[] = $row;
    }
    return $result;
  }

  // Fallback: legacy per-attr meta keys
  $attr_posts = get_posts([
    'post_type'        => FINELEVATE360_CPT_ATTR,
    'numberposts'      => -1,
    'orderby'          => 'menu_order',
    'order'            => 'ASC',
    'suppress_filters' => true,
  ]);

  $result = [];
  foreach ( $attr_posts as $a ) {
    $yn = get_post_meta( $post_id, '_finelevate360_attr_' . $a->ID, true );
    if ( $yn !== '' ) {
      $result[] = [
        'title'   => $a->post_title,
        'mobile'  => $yn,
        'desktop' => $yn,
        'biznis'  => $yn,
        'privat'  => $yn,
      ];
    }
  }
  return $result;
}

/**
 * Build data-* attributes for the modal trigger.
 */
function finelevate360_build_modal_data( $post_id, $type ) {
  $thumb = '';
  if ( has_post_thumbnail( $post_id ) ) {
    $src   = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large' );
    $thumb = $src ? $src[0] : '';
  }

  $title   = get_the_title( $post_id );
  $content = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );

  $data = [
    'thumb' => $thumb,
    'title' => $title,
    'text'  => $content,
  ];

  if ( $type === FINELEVATE360_CPT_PARTNERS ) {
    $data['badge-text']  = get_post_meta( $post_id, '_finelevate360_partner_badge_text',  true );
    $data['badge-class'] = get_post_meta( $post_id, '_finelevate360_partner_badge_class', true );
    $data['website']     = get_post_meta( $post_id, '_finelevate360_partner_website',     true );
    $data['linkedin']    = get_post_meta( $post_id, '_finelevate360_partner_linkedin',    true );
    $data['contact']     = get_post_meta( $post_id, '_finelevate360_partner_contact',     true );
    $data['modal-class'] = 'f360-modal--default';

  } elseif ( $type === FINELEVATE360_CPT_ADVISORY ) {
    $data['job-title']   = get_post_meta( $post_id, '_finelevate360_advisory_job_title', true );
    $data['company']     = get_post_meta( $post_id, '_finelevate360_advisory_company',   true );
    $data['website']     = get_post_meta( $post_id, '_finelevate360_advisory_website',   true );
    $data['linkedin']    = get_post_meta( $post_id, '_finelevate360_advisory_linkedin',  true );
    $data['contact']     = get_post_meta( $post_id, '_finelevate360_advisory_contact',   true );
    $data['modal-class'] = 'f360-modal--advisory';

  } elseif ( $type === FINELEVATE360_CPT_ITEM ) {
    $data['website']     = get_post_meta( $post_id, '_finelevate360_item_website',  true );
    $data['linkedin']    = get_post_meta( $post_id, '_finelevate360_item_linkedin', true );
    $data['contact']     = get_post_meta( $post_id, '_finelevate360_item_contact',  true );
    $data['modal-class'] = 'f360-modal--default';
    $market = finelevate360_research_market($post_id);
    if ($market) {
      $data['research-market'] = $market;
      // Research evidence remains in protected metadata, not in public HTML.
    }
    $public_rows = finelevate360_get_item_attributes( $post_id );
    if ($market) {
      $public_rows = array_map(function($row) {
        return array_intersect_key($row, array_flip(['id','title','mobile','desktop','biznis','privat']));
      }, $public_rows);
    }
    $data['attrs'] = wp_json_encode($public_rows, JSON_UNESCAPED_UNICODE);
  }

  $out = ' data-f360-open="1"';
  foreach ( $data as $key => $val ) {
    if ( $val !== '' && $val !== null && $val !== false ) {
      $out .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $val ) . '"';
    }
  }
  return $out;
}

/**
 * Render a single card <article>.
 */
function finelevate360_render_card( $post_id, $type ) {
  $title = get_the_title( $post_id );
  $thumb = get_the_post_thumbnail( $post_id, 'medium', ['loading' => 'lazy'] );
  $desc  = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 26 );

  if ( $type === FINELEVATE360_CPT_PARTNERS ) {
    $badge_text  = get_post_meta( $post_id, '_finelevate360_partner_badge_text',  true );
    $badge_class = get_post_meta( $post_id, '_finelevate360_partner_badge_class', true );
  } else {
    $badge_text = $badge_class = '';
  }

  $modal_data = finelevate360_build_modal_data( $post_id, $type );


 // Data for client-side filtering (items only)
$filter_data = '';
if ( $type === FINELEVATE360_CPT_ITEM ) {
  $saved = get_post_meta( $post_id, '_finelevate360_item_attrs', true );
  if ( ! is_array( $saved ) ) {
    $saved = [];
  }

  $saved = finelevate360_active_attribute_values($saved);
  $filter_map = [];

  foreach ( $saved as $attr_id => $col_vals ) {
    $attr_id = (int) $attr_id;

    if ( ! $attr_id ) {
      continue;
    }

    if ( is_array( $col_vals ) ) {
      foreach ( [ 'mobile', 'desktop', 'biznis', 'privat' ] as $col_key ) {
        if ( isset( $col_vals[ $col_key ] ) && $col_vals[ $col_key ] === 'yes' ) {
          if ( ! isset( $filter_map[ $attr_id ] ) ) {
            $filter_map[ $attr_id ] = [];
          }

          $filter_map[ $attr_id ][] = $col_key;
        }
      }
    } elseif ( $col_vals === 'yes' ) {
      // Backward compatibility for old scalar data.
      $filter_map[ $attr_id ] = [ 'mobile', 'desktop', 'biznis', 'privat' ];
    }
  }

  if (finelevate360_research_market($post_id)) $filter_map = finelevate360_research_filter_map(finelevate360_research_rows($post_id));
  $filter_data = ' data-filter-map="' . esc_attr( wp_json_encode( $filter_map ) ) . '"';
}

  // Attr counter only for items
  $attr_counter = '';
  $attr_pct     = 0;
  if ( $type === FINELEVATE360_CPT_ITEM ) {
    $item_terms = wp_get_post_terms( $post_id, FINELEVATE360_TAX_ITEMCAT, [ 'fields' => 'ids' ] );
    $item_cat   = ! empty( $item_terms ) ? (int) $item_terms[0] : 0;

    $cat_attr_args = [
      'post_type'        => FINELEVATE360_CPT_ATTR,
      'numberposts'      => -1,
      'fields'           => 'ids',
      'suppress_filters' => true,
    ];
    if ( $item_cat ) {
      $cat_attr_args['tax_query'] = [[
        'taxonomy' => FINELEVATE360_TAX_ITEMCAT,
        'field'    => 'term_id',
        'terms'    => $item_cat,
      ]];
    }
    $total_all = count( get_posts( $cat_attr_args ) );

    $saved     = finelevate360_active_attribute_values(get_post_meta( $post_id, '_finelevate360_item_attrs', true ));
    $yes_count = 0;
    if ( is_array( $saved ) ) {
      foreach ( $saved as $col_vals ) {
        if ( is_array( $col_vals ) ) {
          foreach ( $col_vals as $yn ) {
            if ( $yn === 'yes' ) { $yes_count++; break; }
          }
        } elseif ( $col_vals === 'yes' ) {
          $yes_count++;
        }
      }
    }

    if ( $total_all > 0 ) {
      $attr_counter = $yes_count . '/' . $total_all;
      $attr_pct     = round( ( $yes_count / $total_all ) * 100 );
    }
  }

  $research_market = $type === FINELEVATE360_CPT_ITEM ? finelevate360_research_market($post_id) : '';
  if ($research_market) $attr_counter = '';
  ob_start();
  ?>
  <article class="finelevate-filter-map-item"<?php if ($research_market) echo ' tabindex="0" role="button" aria-haspopup="dialog"'; echo $modal_data; echo $filter_data; ?>>
    <div class="finelevate-filter-map-item-media">
      <?php echo $thumb ?: ''; ?>
    </div>
    <div class="finelevate-filter-map-item-content">
      <h3 class="finelevate-filter-map-item-title"><?php echo esc_html( $title ); ?></h3>

      <?php if ( ! empty( $badge_text ) ) : ?>
        <div class="finelevate-filter-map-item__badge <?php echo esc_attr( $badge_class ); ?>">
          <?php echo esc_html( $badge_text ); ?>
        </div>
      <?php endif; ?>

      <?php if ( ! empty( $desc ) ) : ?>
        <p class="finelevate-filter-map-item-desc"><?php echo esc_html( $desc ); ?></p>
      <?php endif; ?>

      <?php if ($research_market) echo finelevate360_research_summary($post_id); ?>
      <?php if ( $attr_counter !== '' ) : ?>
        <div class="finelevate-filter-map-item__attr-count">
          <span class="f360-attr-count__label"><?php echo esc_html( $attr_counter ); ?></span>
          <div class="f360-attr-count__bar">
            <div class="f360-attr-count__fill" style="width:<?php echo esc_attr( $attr_pct ); ?>%"></div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </article>
  <?php
  return ob_get_clean();
}

function finelevate360_cards_css_once() {
  static $done = false;
  if ( $done ) return '';
  $done = true;

  ob_start();
  ?>

<style>
/* ── Live filtering ──────────────────────────────────────────────── */
.finelevate-filter-map-item {
  transition: opacity .3s ease, transform .3s ease;
}

.finelevate-filter-map-item.f360-dim {
  opacity: .25;
  transform: scale(.97);
  pointer-events: none;
}

.finelevate-filter-map-container {
  display: flex;
  flex-wrap: wrap;
}

.finelevate-filter-map-item.f360-match {
  order: -1;
}

/* ── Filter count badges ─────────────────────────────────────────── */
.f360-filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  margin-left: 6px;
  padding: 0 6px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: #f1f5f9;
  color: #475569;
}

/* ── Disabled filter options ─────────────────────────────────────── */
.checkbox-custom.f360-filter-disabled {
  opacity: .45;
  cursor: not-allowed;
}

.checkbox-custom.f360-filter-disabled input,
.checkbox-custom.f360-filter-disabled .check-indicator,
.checkbox-custom.f360-filter-disabled .f360-filter-label-text {
  cursor: not-allowed;
}
	/* ── Filter panel visual improvement ─────────────────────────────── */
.finelevate-filters {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 18px;
  padding: 20px 18px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

.finelevate-filter-title {
  font-size: 18px;
  font-weight: 700;
  line-height: 1.3;
  color: #1f2937;
  margin: 18px 0 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eceff3;
}

.finelevate-filter-items {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.finelevate-data {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 10px;
}

/* each clickable filter option */
.checkbox-custom {
  display: grid;
  grid-template-columns: 24px minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 52px;
  padding: 10px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #ffffff;
  transition: all 0.2s ease;
  cursor: pointer;
}

.checkbox-custom:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.checkbox-custom input {
  margin: 0;
}

.check-indicator {
  width: 22px;
  height: 22px;
  border: 1.5px solid #cbd5e1;
  border-radius: 6px;
  background: #fff;
}

/* radio/checkbox checked style */
.checkbox-custom input:checked + .check-indicator {
  border-color: #4f46e5;
  background: #eef2ff;
}

.f360-filter-label-text {
  display: block;
  min-width: 0;
  font-size: 16px;
  line-height: 1.3;
  font-weight: 500;
  color: #374151;
}

/* count pill */
.f360-filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 42px;
  height: 30px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef2f7;
  color: #334155;
  font-size: 14px;
  font-weight: 700;
  line-height: 1;
}

/* info button */
.f360-attr-info {
  width: 32px;
  height: 32px;
  border: 1px solid #c7d2fe;
  border-radius: 999px;
  background: #ffffff;
  color: #4f46e5;
  font-size: 15px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: all 0.2s ease;
}

.f360-attr-info:hover {
  background: #eef2ff;
  border-color: #818cf8;
}

/* disabled options */
.checkbox-custom.f360-filter-disabled {
  opacity: 0.45;
  background: #f8fafc;
  cursor: not-allowed;
}

.checkbox-custom.f360-filter-disabled:hover {
  background: #f8fafc;
  border-color: #e5e7eb;
}

.checkbox-custom.f360-filter-disabled input,
.checkbox-custom.f360-filter-disabled .check-indicator,
.checkbox-custom.f360-filter-disabled .f360-filter-label-text {
  cursor: not-allowed;
}

/* selected row gets stronger emphasis */
.checkbox-custom:has(input:checked) {
  border-color: #6366f1;
  background: #f5f7ff;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
}

/* action area */
.finelevate-filter-action {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-top: 18px;
}

.finelevate-filter-btn {
  border: 0;
  background: #111827;
  color: #ffffff;
  border-radius: 12px;
  padding: 10px 16px;
  font-weight: 600;
  cursor: pointer;
}

.finelevate360-filters__reset {
  display: inline-flex;
  align-items: center;
  color: #475569;
  text-decoration: none;
  font-weight: 500;
}

/* mobile refinement */
@media (max-width: 640px) {
  .checkbox-custom {
    grid-template-columns: 22px minmax(0, 1fr) 24px;
    gap: 8px;
  }

  .f360-filter-count {
    width: 24px;
    min-width: 24px;
    height: 21px;
    font-size: 11px;
  }
}
.checkbox-custom {
  display: grid;
  grid-template-columns: 24px minmax(0, 1fr) 26px;
  align-items: center;
  gap: 10px;
  width: 100%;
  min-height: 52px;
  padding: 10px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #ffffff;
  transition: all 0.2s ease;
  cursor: pointer;
}

  .f360-filter-label-text {
    font-size: 15px;
  }

.f360-filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  min-width: 26px;
  height: 22px;
  padding: 0;
  border-radius: 999px;
  background: #eef2f7;
  color: #334155;
  font-size: 12px;
  font-weight: 700;
  line-height: 1;
  flex-shrink: 0;
}

  .f360-attr-info {
    width: 30px;
    height: 30px;
  }
}
</style>

  <script>
(function () {
  function getChecked(name) {
    return Array.from(document.querySelectorAll('input[name="' + name + '"]:checked'))
      .map(function (el) { return Number(el.value); })
      .filter(function (v) { return !!v; });
  }

  function getRadio(name) {
    var el = document.querySelector('input[name="' + name + '"]:checked');
    return el ? el.value : '';
  }

  function parseFilterMap(item) {
    try {
      return JSON.parse(item.dataset.filterMap || '{}');
    } catch (e) {
      return {};
    }
  }

  function attrMatchesChannel(filterMap, attrId, selectedCol) {
    var cols = filterMap[String(attrId)] || [];

    if (!cols.length) {
      return false;
    }

    if (!selectedCol) {
      return true;
    }

    return cols.indexOf(selectedCol) !== -1;
  }

  function itemHasChannel(filterMap, selectedCol) {
    if (!selectedCol) {
      return Object.keys(filterMap).length > 0;
    }

    return Object.keys(filterMap).some(function (attrId) {
      var cols = filterMap[attrId] || [];
      return cols.indexOf(selectedCol) !== -1;
    });
  }

  function itemMatches(filterMap, selectedAttrs, selectedCol) {
    if (selectedAttrs.length > 0) {
      return selectedAttrs.every(function (attrId) {
        return attrMatchesChannel(filterMap, attrId, selectedCol);
      });
    }

    if (selectedCol) {
      return itemHasChannel(filterMap, selectedCol);
    }

    return true;
  }

  function countMatches(items, selectedAttrs, selectedCol) {
    return items.filter(function (item) {
      return itemMatches(parseFilterMap(item), selectedAttrs, selectedCol);
    }).length;
  }

  function updateFilterCountsAndAvailability(items, selectedAttrs, selectedCol) {
    var attrInputs = Array.from(document.querySelectorAll('input[name="f360_attr[]"]'));
    var colInputs = Array.from(document.querySelectorAll('input[name="f360_col"]'));

    attrInputs.forEach(function (input) {
      var attrId = Number(input.value);
      var candidateAttrs = selectedAttrs.slice();

      if (candidateAttrs.indexOf(attrId) === -1) {
        candidateAttrs.push(attrId);
      }

      var count = countMatches(items, candidateAttrs, selectedCol);
      var badge = document.querySelector('[data-f360-attr-count="' + attrId + '"]');
      var label = document.querySelector('[data-f360-attr-label="' + attrId + '"]');

      if (badge) {
        badge.textContent = count;
      }

      var shouldDisable = count === 0 && !input.checked;

      input.disabled = shouldDisable;

      if (label) {
        label.classList.toggle('f360-filter-disabled', shouldDisable);
      }
    });

    colInputs.forEach(function (input) {
      var candidateCol = input.value;
      var count = countMatches(items, selectedAttrs, candidateCol);
      var badge = document.querySelector('[data-f360-col-count="' + candidateCol + '"]');
      var label = document.querySelector('[data-f360-col-label="' + candidateCol + '"]');

      if (badge) {
        badge.textContent = count;
      }

      var shouldDisable = count === 0 && !input.checked && candidateCol !== '';

      input.disabled = shouldDisable;

      if (label) {
        label.classList.toggle('f360-filter-disabled', shouldDisable);
      }
    });
  }

  function runFilter() {
    var selectedAttrs = getChecked('f360_attr[]');
    var selectedCol = getRadio('f360_col');
    var noFilter = selectedAttrs.length === 0 && !selectedCol;

    var items = Array.from(
      document.querySelectorAll('.finelevate-filter-map-item[data-filter-map]')
    );

    items.forEach(function (item) {
      var filterMap = parseFilterMap(item);

      if (noFilter) {
        item.classList.remove('f360-dim', 'f360-match');
        item.style.display = '';
        return;
      }

      var matches = itemMatches(filterMap, selectedAttrs, selectedCol);

      item.classList.toggle('f360-match', matches);
      item.classList.toggle('f360-dim', !matches);
      item.style.display = matches ? '' : 'none';
    });

    updateFilterCountsAndAvailability(items, selectedAttrs, selectedCol);
  }

  document.addEventListener('change', function (e) {
    var el = e.target;

    if (!el.matches('input[name="f360_attr[]"], input[name="f360_col"]')) {
      return;
    }

    runFilter();
  });

  var form = document.querySelector('.finelevate360-filters');

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      runFilter();
    });
  }

  var reset = document.querySelector('.finelevate360-filters__reset');

  if (reset && form) {
    reset.addEventListener('click', function (e) {
      e.preventDefault();

      Array.from(form.querySelectorAll('input[name="f360_attr[]"]')).forEach(function (input) {
        input.checked = false;
        input.disabled = false;
      });

      Array.from(form.querySelectorAll('input[name="f360_col"]')).forEach(function (input) {
        input.checked = input.value === '';
        input.disabled = false;
      });

      Array.from(form.querySelectorAll('.checkbox-custom')).forEach(function (label) {
        label.classList.remove('f360-filter-disabled');
      });

      runFilter();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runFilter);
  } else {
    runFilter();
  }
})();
</script>

  <?php
  return ob_get_clean();
}

// ─── Shortcodes ───────────────────────────────────────────────────────────────

function finelevate360_shortcode_partners( $atts ) {
  $atts = shortcode_atts([
    'count'   => 12,
    'filters' => 1,
    'orderby' => 'date',
    'order'   => 'DESC',
  ], $atts, 'finelevate360_partners');

  $q = new WP_Query([
    'post_type'      => FINELEVATE360_CPT_PARTNERS,
    'posts_per_page' => (int) $atts['count'],
    'orderby'        => sanitize_key( $atts['orderby'] ),
    'order'          => $atts['order'] === 'ASC' ? 'ASC' : 'DESC',
  ]);

  ob_start();
  echo finelevate360_cards_css_once();
  echo '<div class="finelevate-filter-map-container">';
  if ( $q->have_posts() ) {
    while ( $q->have_posts() ) { $q->the_post();
      echo finelevate360_render_card( get_the_ID(), FINELEVATE360_CPT_PARTNERS );
    }
    wp_reset_postdata();
  }
  echo '</div>';
  return ob_get_clean();
}

function finelevate360_shortcode_advisory( $atts ) {
  $atts = shortcode_atts([
    'count'   => 12,
    'orderby' => 'date',
    'order'   => 'DESC',
  ], $atts, 'finelevate360_advisory');

  $q = new WP_Query([
    'post_type'      => FINELEVATE360_CPT_ADVISORY,
    'posts_per_page' => (int) $atts['count'],
    'orderby'        => sanitize_key( $atts['orderby'] ),
    'order'          => $atts['order'] === 'ASC' ? 'ASC' : 'DESC',
  ]);

  ob_start();
  echo finelevate360_cards_css_once();
  echo '<div class="finelevate-filter-map-container">';
  if ( $q->have_posts() ) {
    while ( $q->have_posts() ) { $q->the_post();
      echo finelevate360_render_card( get_the_ID(), FINELEVATE360_CPT_ADVISORY );
    }
    wp_reset_postdata();
  }
  echo '</div>';
  return ob_get_clean();
}

/** Parse the page's finelevate360_bank_order CSV without changing country membership.
 * An invalid or unusable field returns [] so the existing query order is retained.
 */
function finelevate360_bank_order_ids($csv, $allowed_ids) {
  if (!is_string($csv) || trim($csv) === '') return [];
  $ordered_ids = [];
  foreach (explode(',', $csv) as $token) {
    $token = trim($token, " \t\n\r\v\f");
    if (!preg_match('/^[1-9][0-9]*$/D', $token) || (string)(int)$token !== $token) return [];
    $id = (int)$token;
    if (in_array($id, $allowed_ids, true) && !in_array($id, $ordered_ids, true)) $ordered_ids[] = $id;
  }
  if (!$ordered_ids) return [];
  foreach ($allowed_ids as $id) {
    if (!in_array($id, $ordered_ids, true)) $ordered_ids[] = $id;
  }
  return $ordered_ids;
}

function finelevate360_shortcode_items( $atts ) {
  $atts = shortcode_atts([
    'per_page'    => 20,
    'filters'     => 1,
    'filters_cat' => 0,
    'cat'         => '',
  ], $atts, 'finelevate360_items');

  $page_param = 'f360_ip';
  $paged      = isset( $_GET[$page_param] ) ? max( 1, (int) $_GET[$page_param] ) : 1;

  // Platform filter: mobile / desktop
  $col_filter = isset( $_GET['f360_col'] ) ? sanitize_key( $_GET['f360_col'] ) : '';
  if ( ! in_array( $col_filter, [ 'mobile', 'desktop', 'biznis', 'privat' ], true ) ) $col_filter = '';

  // Attribute checkbox filters
  $selected = isset( $_GET['f360_attr'] ) ? (array) $_GET['f360_attr'] : [];
  $selected = finelevate360_canonical_attribute_filters(array_values( array_filter( array_map( 'intval', $selected ) ) ));

  // Category
  if ( ! empty( $atts['cat'] ) ) {
    $term = get_term_by( is_numeric( $atts['cat'] ) ? 'id' : 'slug', $atts['cat'], FINELEVATE360_TAX_ITEMCAT );
    $cat  = $term ? (int) $term->term_id : 0;
  } else {
    $cat = isset( $_GET['f360_cat'] ) ? (int) $_GET['f360_cat'] : 0;
  }

  // Build meta_query
  $attr_clauses = [];
  foreach ( $selected as $attr_id ) {
    $attr_clauses[] = [
      'key'     => '_finelevate360_attr_' . $attr_id,
      'value'   => 'yes',
      'compare' => '=',
    ];
  }

  $col_clause = [];
  if ( $col_filter ) {
    $col_clause = [
      'key'     => '_finelevate360_item_attrs',
      'value'   => '"' . $col_filter . '";s:3:"yes"',
      'compare' => 'LIKE',
    ];
  }

  $meta_query = [];
  if ( ! empty( $attr_clauses ) ) {
    if ( count( $attr_clauses ) === 1 ) {
      $meta_query[] = $attr_clauses[0];
    } else {
      $attr_group = $attr_clauses;
      $attr_group['relation'] = 'AND';
      $meta_query[] = $attr_group;
    }
  }
  if ( ! empty( $col_clause ) ) {
    $meta_query[] = $col_clause;
  }

  if ( count( $meta_query ) > 1 ) {
    $meta_query['relation'] = 'AND';
  }

  $tax_query = [];
  if ( $cat ) {
    $tax_query[] = [
      'taxonomy' => FINELEVATE360_TAX_ITEMCAT,
      'field'    => 'term_id',
      'terms'    => $cat,
    ];
  }

  $research_market = finelevate360_research_page_market();
  $research_ids = [];
  if ($research_market) {
    foreach (finelevate360_research_ids($research_market) as $id) {
      $research_ids[] = $id;
    }
    $meta_query = [];
    $tax_query = [];
  }
  $query_args = [
    'post_type'      => FINELEVATE360_CPT_ITEM,
    'posts_per_page' => (int) $atts['per_page'],
    'paged'          => $paged,
    'meta_query'     => $meta_query,
    'tax_query'      => $tax_query,
  ];
  if ($research_market) {
    $query_args['post__in'] = $research_ids ?: [0];
    $query_args['posts_per_page'] = -1;
    $query_args['suppress_filters'] = true;
    $bank_order_ids = finelevate360_bank_order_ids(
      get_post_meta(get_queried_object_id(), 'finelevate360_bank_order', true),
      $research_ids
    );
    if ($bank_order_ids) {
      $query_args['post__in'] = $bank_order_ids;
      $query_args['orderby'] = 'post__in';
    }
  }
  $q = new WP_Query($query_args);

  // Attrs for checkbox filters — sorted by menu_order
  $attr_args = [
    'post_type'        => FINELEVATE360_CPT_ATTR,
    'numberposts'      => -1,
    'orderby'          => 'menu_order',
    'order'            => 'ASC',
    'suppress_filters' => true,
  ];
  if ( $cat ) {
    $attr_args['tax_query'] = [[
      'taxonomy' => FINELEVATE360_TAX_ITEMCAT,
      'field'    => 'term_id',
      'terms'    => $cat,
    ]];
  }
  if ($research_market) {
    unset($attr_args['tax_query']);
    $attr_args['post__in'] = finelevate360_research_attr_ids($research_market);
  }
  $attrs = get_posts( $attr_args );

  $scheme      = is_ssl() ? 'https' : 'http';
  $current_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  $current_url = remove_query_arg( $page_param, $current_url );

  ob_start();
  echo finelevate360_assets_once();
  echo finelevate360_cards_css_once();

  if ($research_market) echo finelevate360_research_legend($research_market);
  echo '<div class="finelevate-filter-container">';

  if ( (int) $atts['filters'] === 1 ) : ?>
    <button class="finelevate-filter-toggle">☰ Filter</button>
    <aside class="finelevate-filters">

      <form id="finelevate360-filter-form" method="get" class="finelevate360-filters" style="margin:0 0 14px;">
        <div class="finelevate-filters-wrap">

          <?php if ( (int) $atts['filters_cat'] === 1 ) : ?>
            <?php $cats = get_terms(['taxonomy' => FINELEVATE360_TAX_ITEMCAT, 'hide_empty' => false]); ?>
            <select name="f360_cat" class="finelevate360-filters__select" onchange="this.form.submit()">
              <option value="0"><?php echo esc_html( finelevate360_i18n()['all_categories'] ); ?></option>
              <?php foreach ( $cats as $t ) : ?>
                <option value="<?php echo (int) $t->term_id; ?>" <?php selected( $cat, (int) $t->term_id ); ?>>
                  <?php echo esc_html( $t->name ); ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php elseif ( $cat ) : ?>
            <input type="hidden" name="f360_cat" value="<?php echo (int) $cat; ?>" />
          <?php endif; ?>

          <!-- ── Platform + Тип combined filter ── -->
          <div style="flex-basis:100%;height:0;"></div>
          <div class="finelevate-filter-title"><?php echo esc_html( finelevate360_i18n()['platform'] ); ?></div>
          <div class="finelevate-filter-items">
            <?php
            $col_options = [
              ''        => finelevate360_i18n()['all'],
              'mobile'  => finelevate360_i18n()['mobile'],
              'desktop' => finelevate360_i18n()['desktop'],
              'biznis'  => finelevate360_i18n()['biznis'],
              'privat'  => finelevate360_i18n()['privat'],
            ];
            foreach ( $col_options as $col_val => $col_label ) : ?>
              <div class="finelevate-data">
                <label class="checkbox-custom" data-f360-col-label="<?php echo esc_attr( $col_val ); ?>">
                  <input type="radio" name="f360_col" value="<?php echo esc_attr( $col_val ); ?>"
                    <?php checked( $col_filter, $col_val ); ?> />
                  <div class="check-indicator"></div>
                  <span class="f360-filter-label-text"><?php echo esc_html( $col_label ); ?></span>
					<span class="f360-filter-count" data-f360-col-count="<?php echo esc_attr( $col_val ); ?>">0</span>
                </label>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- ── Attribute checkboxes (second) ── -->
          <?php if ( ! empty( $attrs ) ) : ?>
            <div style="flex-basis:100%;height:0;"></div>
            <div class="finelevate-filter-title"><?php echo esc_html( finelevate360_i18n()['filter_attrs'] ); ?></div>
            <div class="finelevate-filter-items">
              <?php foreach ( $attrs as $a ) : $id = (int) $a->ID;
                $attr_desc = get_post_meta( $a->ID, '_finelevate360_attr_description', true );
              ?>
                <div class="finelevate-data">
                  <label class="checkbox-custom" data-f360-attr-label="<?php echo (int) $id; ?>">
                    <input type="checkbox" name="f360_attr[]" value="<?php echo $id; ?>"
                      <?php checked( in_array( $id, $selected, true ) ); ?> />
                    <div class="check-indicator"></div>
                    <span class="f360-filter-label-text"><?php echo esc_html( $a->post_title ); ?></span>
					<span class="f360-filter-count" data-f360-attr-count="<?php echo (int) $id; ?>">0</span>
                  </label>
                  <?php if ( ! empty( $attr_desc ) ) : ?>
                    <button type="button" class="f360-attr-info"
                      data-f360-open="1"
                      data-modal-class="f360-modal--info"
                      data-title="<?php echo esc_attr( $a->post_title ); ?>"
                      data-text="<?php echo esc_attr( $attr_desc ); ?>"
                      aria-label="Info">i</button>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="finelevate-filter-action">
            <button type="submit" class="finelevate-filter-btn"><?php echo esc_html( finelevate360_i18n()['apply'] ); ?></button>
            <?php $reset = remove_query_arg( ['f360_attr', 'f360_col', $page_param], $current_url ); ?>
            <a class="finelevate360-filters__reset" href="<?php echo esc_url( $reset ); ?>"><?php echo esc_html( finelevate360_i18n()['reset'] ); ?></a>
          </div>

        </div>
      </form>
    </aside>
  <?php endif;

  echo '<div class="finelevate-filter-map-container">';
  if ( $q->have_posts() ) {
    while ( $q->have_posts() ) { $q->the_post();
      echo finelevate360_render_card( get_the_ID(), FINELEVATE360_CPT_ITEM );
    }
    wp_reset_postdata();
  } else {
    echo '<p>' . esc_html( finelevate360_i18n()['no_items'] ) . '</p>';
  }
  echo '</div>';

  if ( $q->max_num_pages > 1 ) {
    echo '<nav class="finelevate360-pagination" aria-label="' . esc_attr( finelevate360_i18n()['pagination'] ) . '">';
    echo paginate_links([
      'base'      => add_query_arg( $page_param, '%#%', $current_url ),
      'format'    => '',
      'current'   => $paged,
      'total'     => $q->max_num_pages,
      'type'      => 'list',
      'prev_text' => esc_html( finelevate360_i18n()['prev'] ),
      'next_text' => esc_html( finelevate360_i18n()['next'] ),
    ]);
    echo '</nav>';
  }

  echo '</div>';
  return ob_get_clean();
}

// ─── Count shortcodes ─────────────────────────────────────────────────────────

function finelevate360_shortcode_count_items( $atts ) {
  $atts = shortcode_atts([
    'cat' => '',
  ], $atts, 'finelevate360_count_items');

  $args = [
    'post_type'      => FINELEVATE360_CPT_ITEM,
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'post_status'    => 'publish',
  ];

  if ( ! empty( $atts['cat'] ) ) {
    $term = get_term_by( is_numeric( $atts['cat'] ) ? 'id' : 'slug', $atts['cat'], FINELEVATE360_TAX_ITEMCAT );
    if ( $term ) {
      $args['tax_query'] = [[
        'taxonomy' => FINELEVATE360_TAX_ITEMCAT,
        'field'    => 'term_id',
        'terms'    => (int) $term->term_id,
      ]];
    }
  }

  $count = count( get_posts( $args ) );
  return '<span class="f360-count f360-count--items">' . (int) $count . '</span>';
}

function finelevate360_shortcode_count_attrs( $atts ) {
  $atts = shortcode_atts([
    'cat' => '',
  ], $atts, 'finelevate360_count_attrs');

  if ( ! empty( $atts['cat'] ) ) {
    $term = get_term_by( is_numeric( $atts['cat'] ) ? 'id' : 'slug', $atts['cat'], FINELEVATE360_TAX_ITEMCAT );
    if ( $term ) {
      $args = [
        'post_type'   => FINELEVATE360_CPT_ATTR,
        'numberposts' => -1,
        'fields'      => 'ids',
        'tax_query'   => [[
          'taxonomy' => FINELEVATE360_TAX_ITEMCAT,
          'field'    => 'term_id',
          'terms'    => (int) $term->term_id,
        ]],
      ];
      $count = count( get_posts( $args ) );
      return '<span class="f360-count f360-count--attrs">' . (int) $count . '</span>';
    }
  }

  $count = count( get_posts( [ 'post_type' => FINELEVATE360_CPT_ATTR, 'numberposts' => -1, 'fields' => 'ids', 'post_status' => 'publish', 'suppress_filters' => false ] ) );
  return '<span class="f360-count f360-count--attrs">' . (int) $count . '</span>';
}
