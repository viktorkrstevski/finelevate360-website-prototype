<?php
if (!defined('ABSPATH')) exit;

get_header();

$archive_page  = finelevate360_get_archive_page('partners');
$archive_title = $archive_page ? get_the_title($archive_page) : 'Partners & Ecosystem Enablers';
$archive_desc  = $archive_page ? wp_kses_post($archive_page->post_content) : '';
$archive_image = $archive_page ? get_the_post_thumbnail_url($archive_page->ID, 'full') : '';

/**
 * Detect the current CPT used by this archive.
 * This avoids hardcoding 'partner', which may not be the real CPT key.
 */
global $wp_query;

$current_post_type = $wp_query->get('post_type');

if (is_array($current_post_type)) {
  $current_post_type = reset($current_post_type);
}

if (!$current_post_type) {
  $queried_object = get_queried_object();

  if ($queried_object && !empty($queried_object->name)) {
    $current_post_type = $queried_object->name;
  }
}

/**
 * Fallback candidates if detection fails.
 */
if (!$current_post_type) {
  $possible_post_types = array(
    'partner',
    'partners',
    'finelevate360_partner',
    'finelevate_partner',
  );

  foreach ($possible_post_types as $possible_post_type) {
    if (post_type_exists($possible_post_type)) {
      $current_post_type = $possible_post_type;
      break;
    }
  }
}

/**
 * Final fallback.
 */
if (!$current_post_type) {
  $current_post_type = 'partner';
}

/**
 * Load all partners on one page.
 */
$partners_query = new WP_Query(array(
  'post_type'              => $current_post_type,
  'post_status'            => 'publish',
  'posts_per_page'         => -1,
  'orderby'                => 'menu_order title',
  'order'                  => 'ASC',
  'no_found_rows'          => true,
  'ignore_sticky_posts'    => true,
  'update_post_meta_cache' => true,
  'update_post_term_cache' => true,
));
?>

<!-- F360 partners template: no-pagination dynamic CPT version -->
<!-- Detected post type: <?php echo esc_html($current_post_type); ?> -->

<div class="finelevate-container">
  <div class="finelevate-row">

    <main class="finelevate360-wrap">

      <div class="finelevate360-hero-page">
        <div class="finelevate360-hero-page-intro">
          <h1><?php echo esc_html($archive_title); ?></h1>

          <?php if ($archive_desc) : ?>
            <p class="finelevate360-archive-desc"><?php echo $archive_desc; ?></p>
          <?php endif; ?>

          <a class="f360-pf-trigger" href="#">Become a partner</a>
        </div>

        <?php if ($archive_image) : ?>
          <div class="finelevate360-hero-page-image">
            <img src="<?php echo esc_url($archive_image); ?>" alt="<?php echo esc_attr($archive_title); ?>">
          </div>
        <?php endif; ?>
      </div>

      <div class="finelevate360-container">

        <?php if ($partners_query->have_posts()) : ?>
          <?php while ($partners_query->have_posts()) : $partners_query->the_post();

            $full_text   = wp_strip_all_tags(get_the_content());
            $words       = preg_split('/\s+/', trim($full_text));
            $word_count  = count($words);
            $short_text  = implode(' ', array_slice($words, 0, 20));
            $has_more    = $word_count > 20;
            $post_id     = get_the_ID();

            $badge_class = get_post_meta($post_id, '_finelevate360_partner_badge_class', true);
            $badge_text  = get_post_meta($post_id, '_finelevate360_partner_badge_text',  true);
            $website     = get_post_meta($post_id, '_finelevate360_partner_website',     true);
            $linkedin    = get_post_meta($post_id, '_finelevate360_partner_linkedin',    true);
            $contact     = trim(get_post_meta($post_id, '_finelevate360_partner_contact', true));

            $card_class  = 'finelevate360-item' . ($badge_class ? ' ' . sanitize_html_class($badge_class) : '');
          ?>

            <article class="<?php echo esc_attr($card_class); ?>">

              <div class="finelevate360-item-image">
                <?php the_post_thumbnail('full', array(
                  'loading' => 'lazy',
                  'class'   => 'finelevate360-item-img',
                )); ?>
              </div>

              <?php if ($badge_text) : ?>
                <div class="finelevate360-item-badge <?php echo esc_attr($badge_class); ?>">
                  <?php echo esc_html($badge_text); ?>
                </div>
              <?php endif; ?>

              <div class="finelevate360-item-content">
                <h3 class="finelevate360-item-title"><?php the_title(); ?></h3>

                <p class="finelevate360-item-desc">
                  <?php echo esc_html($short_text); ?>
                  <?php if ($has_more) : ?>
                    <span class="finelevate360-item-dots"> …</span>
                  <?php endif; ?>
                </p>

                <?php if ($contact) : ?>
                  <div class="finelevate360-item-info">
                    <span class="finelevate360-item-contact">
                      <?php echo esc_html(wp_trim_words($contact, 10)); ?>
                    </span>
                  </div>
                <?php endif; ?>

                <?php if ($has_more) : ?>
                  <button
                    class="finelevate360-read-more"
                    onclick="f360OpenModal(this)"
                    aria-haspopup="dialog"
                    data-title="<?php echo esc_attr(get_the_title()); ?>"
                    data-text="<?php echo esc_attr($full_text); ?>"
                    data-thumb="<?php echo esc_attr(get_the_post_thumbnail_url($post_id, 'medium') ?: ''); ?>"
                    data-badge-text="<?php echo esc_attr($badge_text); ?>"
                    data-badge-class="<?php echo esc_attr($badge_class); ?>"
                    data-contact="<?php echo esc_attr($contact); ?>"
                    data-website="<?php echo esc_attr($website); ?>"
                    data-linkedin="<?php echo esc_attr($linkedin); ?>"
                  >Read more</button>
                <?php endif; ?>
              </div>

            </article>

          <?php endwhile; ?>
          <?php wp_reset_postdata(); ?>

        <?php else : ?>

          <p>No partners found.</p>

        <?php endif; ?>

      </div>

    </main>

  </div>
</div>

<div class="f360-modal" id="f360-modal" role="dialog" aria-modal="true" onclick="f360BgClose(event,this)">
  <div class="f360-modal__drawer"></div>
</div>

<?php get_footer(); ?>