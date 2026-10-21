<?php get_header(); ?>
<main id="main" class="section-shell content-page">
<?php while (have_posts()) { the_post(); ?><article <?php post_class(); ?>><h1><?php the_title(); ?></h1><?php the_content(); wp_link_pages(); ?></article><?php } ?>
</main>
<?php get_footer(); ?>
