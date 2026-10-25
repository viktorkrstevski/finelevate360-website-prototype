<?php
if (!defined('ABSPATH')) exit;
get_header();

$archive_page  = finelevate360_get_archive_page('advisory-board');
$archive_title = $archive_page ? get_the_title($archive_page) : 'Advisory Board';
$archive_desc  = $archive_page ? wp_kses_post($archive_page->post_content) : '';
$archive_image = $archive_page ? get_the_post_thumbnail_url($archive_page->ID, 'full') : '';
?>

<div class="finelevate-container">
  <div class="finelevate-row">

    <main class="finelevate360-wrap">

      <div class="finelevate360-hero-page">
        <div class="finelevate360-hero-page-intro">
          <h1><?php echo esc_html($archive_title); ?></h1>
          <?php if ($archive_desc) : ?>
            <p class="finelevate360-archive-desc"><?php echo $archive_desc; ?></p>
          <?php endif; ?>
        </div>
        <?php if ($archive_image) : ?>
          <div class="finelevate360-hero-page-image">
            <img src="<?php echo esc_url($archive_image); ?>" alt="<?php echo esc_attr($archive_title); ?>">
          </div>
        <?php endif; ?>
      </div>

      <div class="finelevate360-container finelevate360-advisory">
        <?php if (have_posts()) : while (have_posts()) : the_post();
          $full_text  = wp_strip_all_tags(get_the_content());
          $words      = explode(' ', trim($full_text));
          $word_count = count($words);
          $short_text = implode(' ', array_slice($words, 0, 12));
          $has_more   = $word_count > 5;
          $post_id    = get_the_ID();
          $job_title  = trim(get_post_meta($post_id, '_finelevate360_advisory_job_title', true));
          $company    = trim(get_post_meta($post_id, '_finelevate360_advisory_company',   true));
          $contact    = trim(get_post_meta($post_id, '_finelevate360_advisory_contact',   true));
          $website    = get_post_meta($post_id, '_finelevate360_advisory_website',  true);
          $linkedin   = get_post_meta($post_id, '_finelevate360_advisory_linkedin', true);
        ?>

          <article class="finelevate360-item">

            <div class="finelevate360-item-image">
              <?php the_post_thumbnail('full', ['loading' => 'lazy', 'class' => 'finelevate360-item-img']); ?>
            </div>

            <div class="finelevate360-item-content">
              <h3 class="finelevate360-item-title"><?php the_title(); ?></h3>

              <?php if ($job_title) : ?>
                <div class="finelevate360-item-role"><?php echo esc_html($job_title); ?></div>
              <?php endif; ?>

              <?php if ($company) : ?>
                <div class="finelevate360-item-company"><?php echo esc_html($company); ?></div>
              <?php endif; ?>

              <p class="finelevate360-item-desc">
                <?php echo esc_html($short_text); ?>
                <?php if ($has_more) : ?><span class="finelevate360-item-dots"> …</span><?php endif; ?>
              </p>

              <?php if ($contact) : ?>
                <div class="finelevate360-item-info">
                  <span class="finelevate360-item-contact"><?php echo esc_html(wp_trim_words($contact, 10)); ?></span>
                </div>
              <?php endif; ?>

              <?php if ($has_more) : ?>
                <button
                  class="finelevate360-read-more"
                  onclick="f360OpenModal(this)"
                  aria-haspopup="dialog"
                  data-modal-class="f360-modal--advisory"
                  data-title="<?php echo esc_attr(get_the_title()); ?>"
                  data-job-title="<?php echo esc_attr($job_title); ?>"
                  data-company="<?php echo esc_attr($company); ?>"
                  data-text="<?php echo esc_attr($full_text); ?>"
                  data-thumb="<?php echo esc_attr(get_the_post_thumbnail_url($post_id, 'medium') ?: ''); ?>"
                  data-contact="<?php echo esc_attr($contact); ?>"
                  data-website="<?php echo esc_attr($website); ?>"
                  data-linkedin="<?php echo esc_attr($linkedin); ?>"
                >Read more</button>
              <?php endif; ?>
            </div>

          </article>

        <?php endwhile; endif; ?>
      </div>

      <div class="finelevate360-pagination">
        <?php the_posts_pagination(); ?>
      </div>

    </main>

  </div>
</div>

<div class="f360-modal" id="f360-modal" role="dialog" aria-modal="true" onclick="f360BgClose(event,this)">
  <div class="f360-modal__drawer"></div>
</div>

<?php get_footer(); ?>
