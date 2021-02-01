<?php

final class PS_Elementor {

	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';

	/**
	 * Contains instance of PS_Elementor.
	 *
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Returns instance for PS_Elementor.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	/**
	 * Constructor for PS_Elementor.
	 */
	public function __construct() {
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'init_widgets' ] );
	}

	/**
	 * Initialize the widget.
	 *
	 * @return void
	 */
	public function init_widgets() {
		require_once( PASSSTER_PATH . '/src/pagebuilder/elementor/class-ps-elementor-widget.php' );
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \PS_Elementor_Widget() );
	}
}

PS_Elementor::instance();
