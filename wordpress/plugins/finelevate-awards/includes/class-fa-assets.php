<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FA_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts',    [ __CLASS__, 'frontend' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin' ] );
	}

	public static function frontend() {
		if ( ! FA_Template::is_awards_page() ) return;
		wp_enqueue_style( 'finelevate-awards', FA_URL . 'assets/css/finelevate-awards.css', [], FA_VERSION );
	}

	public static function admin( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
		global $post;
		if ( ! $post || $post->post_type !== 'page' ) return;
		if ( get_post_meta( $post->ID, '_wp_page_template', true ) !== FA_Template::SLUG ) return;

		wp_enqueue_media();
		wp_enqueue_style(  'fa-admin', FA_URL . 'assets/css/fa-admin.css', [], FA_VERSION );
		wp_enqueue_script( 'fa-admin', FA_URL . 'assets/js/fa-admin.js', ['jquery'], FA_VERSION, true );

		wp_localize_script( 'fa-admin', 'faL10n', [
			'confirmRemovePillar' => __( 'Remove this pillar and all its awards?', 'finelevate-awards' ),
			'labelPillar'         => __( 'Pillar', 'finelevate-awards' ),
			'labelAward'          => __( 'Award', 'finelevate-awards' ),
			'labelRemove'         => __( 'Remove', 'finelevate-awards' ),
			'labelRemovePillar'   => __( 'Remove pillar', 'finelevate-awards' ),
			'labelAddAward'       => __( '+ Add Award', 'finelevate-awards' ),
			'labelPillarTitle'    => __( 'Pillar title', 'finelevate-awards' ),
			'labelPillarDesc'     => __( 'Pillar description', 'finelevate-awards' ),
			'labelBadgeUrl'       => __( 'Badge / icon URL', 'finelevate-awards' ),
			'labelAwardTitle'     => __( 'Title', 'finelevate-awards' ),
			'labelAwardDesc'      => __( 'Description', 'finelevate-awards' ),
			'labelWinner'         => __( 'Winner (company name)', 'finelevate-awards' ),
			'labelStepPlaceholder'=> __( 'Step label', 'finelevate-awards' ),
			'labelBlockTitle'     => __( 'Block title', 'finelevate-awards' ),
			'labelBlockDesc'      => __( 'Block description', 'finelevate-awards' ),
			'labelRemoveBlock'    => __( 'Remove block', 'finelevate-awards' ),
			'mediaTitle'          => __( 'Choose Sponsor Logo', 'finelevate-awards' ),
			'mediaButton'         => __( 'Use this image', 'finelevate-awards' ),
		] );
	}
}
