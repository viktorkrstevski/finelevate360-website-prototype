<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FA_Template {

	const SLUG = 'finelevate-awards-page-template.php';

	public static function init() {
		add_filter( 'theme_page_templates', [ __CLASS__, 'register' ] );
		add_filter( 'template_include',     [ __CLASS__, 'load' ] );
	}

	public static function register( $templates ) {
		$templates[ self::SLUG ] = __( 'Finelevate Awards Page', 'finelevate-awards' );
		return $templates;
	}

	public static function load( $template ) {
		if ( ! is_page() ) return $template;

		global $post;
		if ( ! $post ) return $template;

		if ( get_post_meta( $post->ID, '_wp_page_template', true ) !== self::SLUG ) {
			return $template;
		}

		$file = FA_DIR . 'templates/' . self::SLUG;
		return file_exists( $file ) ? $file : $template;
	}

	/** Is the current page using our template? */
	public static function is_awards_page() {
		if ( ! is_page() ) return false;
		global $post;
		return $post && get_post_meta( $post->ID, '_wp_page_template', true ) === self::SLUG;
	}
}
