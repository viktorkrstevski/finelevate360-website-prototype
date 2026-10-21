<?php get_header(); ?>
<main id="main" class="section-shell content-page">
<?php if (have_posts()) { while (have_posts()) { the_post(); ?>
<article <?php post_class(); ?>><h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1><?php the_excerpt(); ?></article>
<?php } the_posts_pagination(); } else { ?><h1><?php esc_html_e('No content found', 'finelevate-advisory'); ?></h1><?php } ?>
</main>
<?php get_footer(); ?>
