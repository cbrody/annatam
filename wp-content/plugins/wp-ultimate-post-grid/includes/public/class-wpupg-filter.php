<?php
/**
 * Responsible for grid filters.
 *
 * @link       https://bootstrapped.ventures
 * @since      3.0.0
 *
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public
 */

/**
 * Responsible for grid filters.
 *
 * @since      3.0.0
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPUPG_Filter {
	/**
	 * Get filter defaults.
	 *
	 * @since    3.0.0
	 * @param	 mixed $type Optional filter type to get the defaults for.
	 */
	public static function get_defaults( $type = false ) {
		$defaults = apply_filters( 'wpupg_filter_defaults', array() );

		if ( false === $type ) {
			return $defaults;
		} else {
			if ( isset( $defaults[ $type ] ) ) {
				return $defaults[ $type ];
			} else {
				return false;
			}
		}
	}

	/**
	 * Get filter with defaults.
	 *
	 * @since    3.0.0
	 * @param	 mixed $filter Filter to get the defaults for.
	 */
	public static function filter_with_defaults( $filter ) {
		if ( $filter && isset( $filter['type'] ) ) {
			// Set default options.
			$defaults = self::get_defaults( $filter['type'] );
			if ( $defaults ) {
				$filter['options'] = array_replace_recursive( $defaults, $filter['options'] );
			}

			// Set default label.
			if ( ! isset( $filter['label'] ) ) {
				$filter['label'] = '';
			}
		}

		return $filter;
	}

	/**
	 * Get general filter style defaults.
	 *
	 * @since    3.1.0
	 */
	public static function get_general_style_defaults() {
		$defaults = apply_filters( 'wpupg_filter_general_style_defaults', array(
			'display' => 'block',
			'alignment' => 'left',
			'spacing_vertical' => 10,
			'spacing_horizontal' => 10,
			'width' => 250,
			'label_display' => 'block',
			'label_alignment' => 'left',
			'label_font_size' => 14,
			'label_style' => 'bold',
		) );

		return $defaults;
	}
}
