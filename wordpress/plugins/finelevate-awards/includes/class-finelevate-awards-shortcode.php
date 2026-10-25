<?php
/**
 * Finelevate_Awards_Shortcode
 *
 * Registers [finelevate_awards] so editors can embed the page content
 * anywhere via the block editor or classic editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Finelevate_Awards_Shortcode {

	public static function init() {
		add_shortcode( 'finelevate_awards', [ __CLASS__, 'render' ] );
	}

	public static function render( $atts ) {
		$page_id = (int) get_option( 'finelevate_awards_page_id' );
		if ( ! $page_id ) {
			return '';
		}

		ob_start();
		// Re-use the same template partial (without the full page wrapper)
		$template = FINELEVATE_AWARDS_DIR . 'templates/finelevate-awards-content.php';
		if ( file_exists( $template ) ) {
			$post = get_post( $page_id );
			include $template;
		}
		return ob_get_clean();
	}
}
