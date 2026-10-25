<?php
/**
 * Plugin Name: Finelevate Awards
 * Plugin URI:  https://www.finelevate360.com/awards/
 * Description: Awards page with metaboxes. Full Loco Translate support.
 * Version:     2.0.0
 * Author:      Finelevate
 * Text Domain: finelevate-awards
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FA_VERSION', '2.0.0' );
define( 'FA_DIR',     plugin_dir_path( __FILE__ ) );
define( 'FA_URL',     plugin_dir_url( __FILE__ ) );

require_once FA_DIR . 'includes/class-fa-template.php';
require_once FA_DIR . 'includes/class-fa-metaboxes.php';
require_once FA_DIR . 'includes/class-fa-assets.php';

function fa_load_textdomain() {
	load_plugin_textdomain( 'finelevate-awards', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'fa_load_textdomain' );

function fa_boot() {
	FA_Template::init();
	FA_Metaboxes::init();
	FA_Assets::init();
}
add_action( 'plugins_loaded', 'fa_boot' );
