<?php
/*
Plugin Name: Passster
Text Domain: content-protector
Description: Plugin to password-protect portions of a Page or Post.
Author: patrickposner
Version: 3.3.9
*/

define( 'PASSSTER_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PASSSTER_ABSPATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
define( 'PASSSTER_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

// load setup.
require_once( PASSSTER_ABSPATH . 'inc' . DIRECTORY_SEPARATOR . 'setup.php' );

// localize.
$textdomain_dir = plugin_basename( dirname( __FILE__ ) ) . '/languages';
load_plugin_textdomain( 'content-protector', false, $textdomain_dir );

// boot autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) && ! class_exists( 'passster\PS_Admin' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// run plugin.
if ( ! function_exists( 'passster_run_plugin' ) ) {
	add_action( 'plugins_loaded', 'passster_run_plugin' );

	/**
	 * Run plugin
	 *
	 * @return void
	 */
	function passster_run_plugin() {
		passster\PS_Admin::init();
		passster\PS_Customizer::get_instance();
		passster\PS_Meta::get_instance();
		passster\PS_Form::get_instance();
		passster\PS_Public::get_instance();
	}
}
