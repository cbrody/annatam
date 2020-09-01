<?php
/**
 * Handle the grid shortcodes.
 *
 * @link       https://bootstrapped.ventures
 * @since      3.0.0
 *
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public
 */

/**
 * Handle the grid shortcodes.
 *
 * @since      3.0.0
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPUPG_Shortcode {

	/**
	 * Register actions and filters.
	 *
	 * @since    3.0.0
	 */
	public static function init() {		
		add_shortcode( 'wpupg-grid-with-filters', array( __CLASS__, 'grid_with_filters_shortcode' ) );
		add_shortcode( 'wpupg-grid', array( __CLASS__, 'grid_shortcode' ) );
		add_shortcode( 'wpupg-filter', array( __CLASS__, 'filter_shortcode' ) );
	}

	/**
	 * Output the entire grid shortcode.
	 *
	 * @since    3.0.0
	 * @param	 mixed $atts Shortcode attributes.
	 */
	public static function grid_with_filters_shortcode( $atts ) {
		$output = '';
		$atts = shortcode_atts( array(
			'id' => '',
			'align' => '',
		), $atts, 'wpupg_grid_with_filters' );

		$slug = strtolower( trim( $atts['id'] ) );

		unset( $atts['id'] );

		$grid = WPUPG_Grid_Manager::get_grid( $slug );

		if ( $grid ) {
			WPUPG_Assets::load();

			$output = WPUPG_Grid_Output::entire( $grid, $atts );
			wp_localize_script( 'wpupg-public', 'wpupg_grid_args_' . $grid->id(), $grid->get_javascript_args() );
		}

		return $output;
	}

	/**
	 * Output the grid shortcode.
	 *
	 * @since    3.0.0
	 * @param	 mixed $atts Shortcode attributes.
	 */
	public static function grid_shortcode( $atts ) {
		$output = '';
		$atts = shortcode_atts( array(
			'id' => '',
			'align' => '',
		), $atts, 'wpupg_grid' );

		$slug = strtolower( trim( $atts['id'] ) );

		unset( $atts['id'] );

		$grid = WPUPG_Grid_Manager::get_grid( $slug );

		if ( $grid ) {
			WPUPG_Assets::load();

			$output = WPUPG_Grid_Output::grid( $grid, $atts );
			wp_localize_script( 'wpupg-public', 'wpupg_grid_args_' . $grid->id(), $grid->get_javascript_args() );
		}

		return $output;
	}

	/**
	 * Output the filter shortcode.
	 *
	 * @since    3.0.0
	 * @param	 mixed $atts Shortcode attributes.
	 */
	public static function filter_shortcode( $atts ) {
		$output = '';
		$atts = shortcode_atts( array(
			'id' => '',
			'filter' => '',
			'align' => '',
		), $atts, 'wpupg_filter' );

		$slug = strtolower( trim( $atts['id'] ) );

		unset( $atts['id'] );

		$grid = WPUPG_Grid_Manager::get_grid( $slug );

		if ( $grid ) {
			WPUPG_Assets::load();

			if ( ! $atts['filter'] ) {
				$output = WPUPG_Grid_Output::filters( $grid, $atts );
			} else {
				$filter = $grid->filter( $atts['filter'] );

				if ( $filter ) {
					$output = WPUPG_Grid_Output::filter( $grid, $filter, $atts );
				}
			}
		}

		return $output;
	}
}

WPUPG_Shortcode::init();
