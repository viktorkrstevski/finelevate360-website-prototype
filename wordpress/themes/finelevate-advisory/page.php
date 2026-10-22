<?php get_header(); ?>
<main id="main" class="section-shell content-page">
<?php while (have_posts()) { the_post(); ?>
  <article <?php post_class(); ?>>
    <?php if (get_post_type() !== 'event' || !preg_match('/<h1\b/i', get_the_content())) { ?>
      <h1><?php the_title(); ?></h1>
    <?php } ?>
    <?php the_content(); wp_link_pages();
    $path = get_post_meta(get_the_ID(), '_fe_public_path', true);
    if ($path && (fe_routes()['routes'][$path]['group'] ?? '') === 'news_index') { fe_news_archive(); }
    ?>
  </article>
<?php } ?>
</main>
<?php get_footer(); ?>
