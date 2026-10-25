<?php
/**
 * Template Name: Finelevate Awards Page
 * @package finelevate-awards
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header(); ?>

<main class="finelevate-awards-main">
<?php
while ( have_posts() ) :
	the_post();
	include FA_DIR . 'templates/finelevate-awards-content.php';
endwhile;
?>
</main>

<?php get_footer();
