<?php
/**
 * Finelevate_Awards_Template
 *
 * Injects the plugin's page template into WordPress's template hierarchy.
 *
 * Uses TWO hooks:
 *   1. theme_page_templates  – so the template appears in the Page Attributes dropdown
 *   2. template_include      – the reliable hook that actually swaps the file on the frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Finelevate_Awards_Template {

	const TEMPLATE_SLUG = 'finelevate-awards-page-template.php';

	public static function init() {
		// Register in the dropdown (Appearance → Page Attributes)
		add_filter( 'theme_page_templates', [ __CLASS__, 'register_template' ] );

		// Actually serve the file – this is the reliable hook
		add_filter( 'template_include', [ __CLASS__, 'load_template' ] );
	}

	/**
	 * Register template so it appears in the Page Attributes dropdown.
	 */
	public static function register_template( $templates ) {
		$templates[ self::TEMPLATE_SLUG ] = __( 'Finelevate Awards Page', 'finelevate-awards' );
		return $templates;
	}

	/**
	 * Swap in the plugin template file when the page has it selected.
	 * Works on every page – no dependency on finelevate_awards_page_id.
	 */
	public static function load_template( $template ) {
		if ( ! is_page() ) {
			return $template;
		}

		global $post;
		if ( ! $post ) {
			return $template;
		}

		$page_template = get_post_meta( $post->ID, '_wp_page_template', true );

		if ( $page_template !== self::TEMPLATE_SLUG ) {
			return $template;
		}

		$plugin_template = FINELEVATE_AWARDS_DIR . 'templates/' . self::TEMPLATE_SLUG;

		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}
