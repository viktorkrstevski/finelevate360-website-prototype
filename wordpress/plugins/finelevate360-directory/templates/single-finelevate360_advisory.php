<?php
if (!defined('ABSPATH')) exit;
get_header();

the_post();

$contact  = get_post_meta(get_the_ID(), '_finelevate360_advisory_contact', true);
$website  = get_post_meta(get_the_ID(), '_finelevate360_advisory_website', true);
$linkedin = get_post_meta(get_the_ID(), '_finelevate360_advisory_linkedin', true);
?>
<main class="finelevate360-wrap">
  <a href="<?php echo esc_url(get_post_type_archive_link(FINELEVATE360_CPT_ADVISORY)); ?>">&larr; Back to Advisory Board</a>

  <h1><?php the_title(); ?></h1>

  <div class="finelevate360-hero">
    <div class="finelevate360-hero__img"><?php the_post_thumbnail('large', ['loading' => 'lazy']); ?></div>
    <div class="finelevate360-hero__content">
      <div class="finelevate360-content"><?php the_content(); ?></div>

      <div class="finelevate360-links">
        <?php if ($website) : ?><a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener noreferrer">Website</a><?php endif; ?>
        <?php if ($linkedin) : ?><a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a><?php endif; ?>
      </div>

      <?php if ($contact) : ?>
        <h3>Contact</h3>
        <div class="finelevate360-contact"><?php echo nl2br(esc_html($contact)); ?></div>
      <?php endif; ?>
    </div>
  </div>
</main>

<style>
  .finelevate360-wrap{max-width:1100px;margin:0 auto;padding:28px 16px}
  .finelevate360-hero{display:grid;grid-template-columns:360px 1fr;gap:22px;margin-top:14px}
  @media (max-width: 900px){.finelevate360-hero{grid-template-columns:1fr}}
  .finelevate360-links a{margin-right:12px}
  .finelevate360-contact{white-space:normal;border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:12px}
</style>

<?php get_footer(); ?>
