<?php
/**
 * Handle the item custom field shortcode.
 *
 * @link       https://bootstrapped.ventures
 * @since      3.0.0
 *
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public/shortcodes/item
 */

/**
 * Handle the item custom field shortcode.
 *
 * @since      3.0.0
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public/shortcodes/item
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPUPG_SC_Custom_Field extends WPUPG_Template_Shortcode {
	public static $shortcode = 'wpupg-item-custom-field';

	public static function init() {
		$atts = array(
			'key' => array(
				'default' => '',
				'type' => 'text',
				'help' => 'Key of the Custom Field you want to display.',
			),
			'display' => array(
				'default' => 'inline',
				'type' => 'dropdown',
				'options' => 'display_options',
			),
			'align' => array(
				'default' => 'left',
				'type' => 'dropdown',
				'options' => array(
					'left' => 'Left',
					'center' => 'Center',
					'right' => 'Right',
				),
				'dependency' => array(
					'id' => 'display',
					'value' => 'block',
				),
			),
			'text_style' => array(
				'default' => 'normal',
				'type' => 'dropdown',
				'options' => 'text_styles',
			),
		);

		$atts = array_merge( $atts, WPUPG_Template_Helper::get_label_container_atts() );
		$atts = array_merge( $atts, WPUPG_Template_Helper::limit_text_atts() );

		self::$attributes = $atts;
		parent::init();
	}

	/**
	 * Output for the shortcode.
	 *
	 * @since	3.0.0
	 * @param	array $atts Options passed along with the shortcode.
	 */
	public static function shortcode( $atts ) {
		$atts = parent::get_attributes( $atts );

		$item = WPUPG_Template_Shortcodes::get_item();
		$custom_field = $item->custom_field( $atts['key'] );
		if ( ! $item || ! $custom_field || ! is_string( $custom_field ) ) {
			return '';
		}

		// Output.
		$classes = array(
			'wpupg-item-custom-field',
			'wpupg-block-text-' . $atts['text_style'],
		);

		// Alignment.
		if ( 'block' === $atts['display'] && 'left' !== $atts['align'] ) {
			$classes[] = 'wpupg-align-' . $atts['align']; 
		}

		// Limit text.
		$custom_field = WPUPG_Template_Helper::limit_text( $atts, $custom_field );

		$label_container = WPUPG_Template_Helper::get_label_container( $atts, 'custom-field' );
		$tag = 'block' === $atts['display'] ? 'div' : 'span';
		$output = '<' . $tag . ' class="' . implode( ' ', $classes ) . '">' . $label_container . $custom_field . '</' . $tag . '>';
		return apply_filters( parent::get_hook(), $output, $atts, $item );
	}
}

WPUPG_SC_Custom_Field::init();