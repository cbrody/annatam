<?php
/**
 * Responsible for outputting grids.
 *
 * @link       https://bootstrapped.ventures
 * @since      3.0.0
 *
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public
 */

/**
 * Responsible for outputting grids.
 *
 * @since      3.0.0
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/public
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPUPG_Grid_Output {

	/**
	 * Output the entire grid.
	 *
	 * @since    3.0.0
	 * @param	 mixed $grid Grid to output.
	 * @param	 mixed $args Optional arguments.
	 */
	public static function entire( $grid, $args = array() ) {
		$output = '';

		$classes = array(
			'wpupg-grid-with-filters',
		);

		if ( isset( $args['align'] ) && $args['align'] ) {
			$classes[] = 'align' . $args['align'];
		}
		unset( $args['align'] );
		
		// Display filters on side.
		if ( 'left' === $grid->filters_style( 'display' ) || 'right' === $grid->filters_style( 'display' ) ) {
			$classes[] = 'wpupg-grid-with-filters-side';
			$classes[] = 'wpupg-grid-with-filters-side-' . $grid->filters_style( 'display' );

			$output .= '<style type="text/css">';
			$output .= '#wpupg-grid-with-filters-' . $grid->slug_or_id() . ' .wpupg-grid-filters {';
			$output .= 'flex-basis: ' . $grid->filters_style( 'width' ) . 'px;';
			$output .= '}';
			$output .= '</style>';
		}

		$output .= '<div id="wpupg-grid-with-filters-' . $grid->slug_or_id() . '" class="' . implode( ' ', $classes ). '">';

		$output .= self::filters( $grid, $args );
		$output .= self::grid( $grid, $args );

		$output .= '</div>';

		return $output;
	}

	/**
	 * Output filters for a specific grid.
	 *
	 * @since    3.0.0
	 * @param	 mixed $grid Grid to output.
	 * @param	 mixed $args Optional arguments.
	 */
	public static function filters( $grid, $args = array() ) {
		$output = '';

		if ( $grid->filters_enabled() ) {
			$filters = $grid->filters();

			$classes = array(
				'wpupg-grid-filters',
				'wpupg-grid-filters-display-' . $grid->filters_style( 'display' ),
			);

			if ( 'inline' === $grid->filters_style( 'display' ) ) {
				$classes[] = 'wpupg-grid-filters-align-' . $grid->filters_style( 'alignment' );

				$output .= '<style type="text/css">';
				$output .= '#wpupg-grid-' . $grid->slug_or_id() . '-filters .wpupg-filter-container {';
				$output .= 'padding: ' . $grid->filters_style( 'spacing_vertical' ) . 'px ' . $grid->filters_style( 'spacing_horizontal' ) . 'px;';
				$output .= '}';
				$output .= '</style>';
			}

			$output .= '<div id="wpupg-grid-' . $grid->slug_or_id() . '-filters" class="' . implode( ' ', $classes ). '">';

			foreach ( $filters as $index => $filter ) {
				// Make sure filter ID is set.
				if ( ! $filter['id'] ) {
					$filter['id'] = $index + 1;
				}

				$output .= self::filter( $grid, $filter, $args );
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Output a specific filter for a specific grid.
	 *
	 * @since    3.0.0
	 * @param	 mixed $grid Grid to output.
	 * @param	 mixed $filter Filter to output.
	 * @param	 mixed $args Optional arguments.
	 */
	public static function filter( $grid, $filter, $args = array() ) {
		$output = '';

		$filter_output = apply_filters( 'wpupg_output_filter', '', $grid, $filter, $args );
		if ( $filter_output ) {
			$container_classes = array(
				'wpupg-filter-container',
				'wpupg-filter-container-label-' . $grid->filters_style( 'label_display' ),
			);
	
			if ( isset( $args['align'] ) && $args['align'] ) {
				$container_classes[] = 'align' . $args['align'];
			}

			$output .= '<div id="wpupg-grid-' . $grid->slug_or_id() . '-filter-' . $filter['id'] . '-container" class="' . implode( ' ', $container_classes ) .'">';

			// Optional label output.
			if ( $filter['label'] ) {
				$label_classes = array(
					'wpupg-filter-label',
					'wpupg-filter-label-' . $grid->filters_style( 'label_style' ),
					'wpupg-filter-label-align-' . $grid->filters_style( 'label_alignment' ),
				);

				$output .= '<div id="wpupg-grid-' . $grid->slug_or_id() . '-filter-' . $filter['id'] . '-label" class="' . implode( ' ', $label_classes ) .'">';
				$output .= $filter['label'];
				$output .= '</div>';

				$output .= '<style type="text/css">';
				$output .= '#wpupg-grid-' . $grid->slug_or_id() . '-filter-' . $filter['id'] . '-label {';
				$output .= 'font-size: ' . $grid->filters_style( 'label_font_size' ) . 'px;';
				$output .= '}';
				$output .= '</style>';
			}

			// Filter output.
			$filter_classes = array(
				'wpupg-filter',
				'wpupg-filter-' . $filter['type'],
			);

			$output .= '<div id="wpupg-grid-' . $grid->slug_or_id() . '-filter-' . $filter['id'] . '" class="' . implode( ' ', $filter_classes ) .'">';
			$output .= $filter_output;
			$output .= '</div>';

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Output a specific grid.
	 *
	 * @since    3.0.0
	 * @param	 mixed $grid Grid to output.
	 * @param	 mixed $args Optional arguments.
	 */
	public static function grid( $grid, $args = array() ) {
		$output = '';
		$style = array();

		$classes = array(
			'wpupg-grid-container',
		);

		if ( isset( $args['align'] ) && $args['align'] ) {
			$classes[] = 'align' . $args['align'];
		}

		$output = '<div id="wpupg-grid-container-' . $grid->slug_or_id() . '" class="' . implode( ' ', $classes ) . '">';

		// Add styling.
		$is_preview = isset( $args['preview'] ) && $args['preview'];
		$output .= WPUPG_Grid_Layout::get_style( $grid, $is_preview );

		// Add template styling if preview.
		if ( $is_preview ) {
			$template_css = WPUPG_Template_Manager::get_template_css( $grid->template() );
			$output .= '<style type="text/css">' . $template_css . '</style>';
		}

		// Add grid output.
		if ( ! isset( $args['type'] ) ) {
			$args['type'] = 'initial';
		}

		$items = self::items( $grid, $args );
		$output .= '<div id="wpupg-grid-' . $grid->slug_or_id() . '" class="wpupg-grid wpupg-grid-loading" data-grid-id="' . $grid->id() . '">' . $items['html'] . '</div>';

		// Optional empty message.
		if ( $grid->empty_message() ) {
			$output .= '<div id="wpupg-grid-' . $grid->slug_or_id() . '-empty" class="wpupg-grid-empty">' . $grid->empty_message() . '</div>';
		}

		// Output pagination.
		$pagination_output = apply_filters( 'wpupg_output_pagination', '', $grid, $args );
		if ( $pagination_output ) {
			$output .= '<div id="wpupg-grid-' . $grid->slug_or_id() . '-pagination" class="wpupg-pagination wpupg-pagination-' . $grid->pagination_type() . '">';
			$output .= $pagination_output;
			$output .= '</div>';
		}

		// Optionally output metadata.
		if ( $grid->metadata() && 1 < count( $items['metadata'] ) ) {
			$output .= self::metadata( $grid, $items );
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Output the grid metadata.
	 *
	 * @since    3.6.0
	 * @param	 mixed $grid Grid to output the metadata for.
	 * @param	 mixed $items Items metadata to output.
	 */
	public static function metadata( $grid, $items = array() ) {
		$metadata = array(
			'@context' => 'http://schema.org',
			'@type' => 'ItemList',
			'itemListElement' => array(),
			'numberOfItems' => count( $items['metadata'] ),
		);

		// Add link to current post.
		$post = get_post();
		if ( $post ) {
			$metadata['url'] = get_permalink( $post );
		}

		// Optionally add name and description.
		if ( $grid->metadata_name() ) {
			$metadata['name'] = wp_strip_all_tags( $grid->metadata_name() );
		}
		if ( $grid->metadata_description() ) {
			$metadata['description'] = wp_strip_all_tags( $grid->metadata_description() );
		}

		// Add items.
		foreach ( $items['metadata'] as $item ) {
			$metadata['itemListElement'][] = $item;
		}

		return '<script type="application/ld+json">' . wp_json_encode( $metadata ) . '</script>';
	}

	/**
	 * Output the grid items.
	 *
	 * @since    3.0.0
	 * @param	 mixed $grid Grid to output the items for.
	 * @param	 mixed $args Optional arguments.
	 */
	public static function items( $grid, $args = array() ) {
		$metadata = array();
		$output = '';

		// Get current postdata.
		global $post;
		$current_post = $post;

		$counter = 1;
		$ids = $grid->ids( $args );
		foreach ( $ids as $id ) {
			$item = self::item( $grid, $id, $args );

			$output .= $item['output'];

			if ( $item['url'] ) {
				$metadata[] = array(
					'@type'    => 'ListItem',
					'position' => $counter,
					'url'      => $item['url'],
				);

				$counter++;
			}
		}

		// Reset postdata to before the grid output.
		$post = $current_post;
		setup_postdata( $post );

		return array(
			'ids' => $ids,
			'html' => $output,
			'metadata' => $metadata,
		);
	}

	/**
	 * Output a grid item.
	 *
	 * @since    3.0.0
	 * @param	 mixed $grid Grid to output the items for.
	 * @param	 mixed $item_id ID of the item to output.
	 * @param	 mixed $args Optional arguments.
	 */
	public static function item( $grid, $item_id, $args = array() ) {
		$output = '';
		$url = false;

		$item = WPUPG_Item_Manager::get_item( $item_id, $grid->type() );
		$item = apply_filters( 'wpupg_output_item', $item, $grid, $args );

		if ( $item ) {
			// Set gobal post object.
			if ( is_a( $item, 'WPUPG_Item_Post' ) ) {
				global $post;
				$post = $item->post();
				setup_postdata( $post );
			}

			// Get classes.
			$classes = $item->classes();

			$template = apply_filters( 'wpupg_output_item_template', $grid->template(), $grid );
			$classes[] = 'wpupg-template-' . $template;
			
			// Taxonomy terms.
			$item_terms = $grid->get_terms_for_item( $item_id );

			foreach ( $item_terms as $taxonomy => $terms ) {
				foreach ( $terms['terms'] as $term ) {
					$classes[] = 'wpupg-tax-' . $taxonomy . '-' . $term;
					$classes[] = 'wpupg-parent-tax-' . $taxonomy . '-' . $term;
				}
				foreach ( $terms['parent_terms'] as $term ) {
					$classes[] = 'wpupg-parent-tax-' . $taxonomy . '-' . $term;
				}
			}

			// Get data.
			$data = array();
			$data['id'] = $item_id;

			// Custom fields.
			$custom_fields = $grid->filters_custom_fields();

			foreach ( $custom_fields as $custom_field ) {
				$custom_field_value = $item->custom_field( $custom_field );

				if ( is_string( $custom_field_value ) ) {
					$data[ 'wpupg-cf-' . $custom_field ] = $custom_field_value;
				}
			}

			$classes = apply_filters( 'wpupg_output_item_classes', $classes, $grid, $item, $args );
			$data = apply_filters( 'wpupg_output_item_data', $data, $grid, $item, $args );
			$html = apply_filters( 'wpupg_output_item_html', WPUPG_Template_Manager::get_template( $item, $template ), $template, $item, $classes );

			// Prevent duplicate classes.
			$classes = array_unique( $classes );

			// Construct data string.
			$data_string = '';
			foreach ( $data as $data_key => $data_value ) {
				$data_string .= ' data-' . $data_key . '="' . esc_attr( $data_value ) . '"';
			}

			// Don't actually add link when previewing.
			if ( isset( $args['preview'] ) && $args['preview'] && $item->link( $grid ) ) {
				return array(
					'output' => '<div class="' . implode( ' ', $classes ) . '" style="cursor: pointer;"' . $data_string .'>' . $html . '</div>',
					'metadata' => false,
				);
			}

			// Check if this item has a link.
			$link = false;
			if ( $item->link( $grid ) ) {
				$link = 'image' === $grid->link_type() ? $item->image_url( 'full' ) : $item->url();
				$link = apply_filters( 'wpupg_output_item_link', $link, $grid, $item, $args );
			}

			if ( $link ) {
				$classes[] = 'wpupg-item-link';
				$rel = 'image' === $grid->link_type() ? ' rel="lightbox"' : '';

				// Strip any other links in HTML.
				$html = str_ireplace( '<a ', '<span ', $html );
				$html = str_ireplace( '<a', '<span', $html );
				$html = str_ireplace( '</a>', '</span>', $html );

				$output = '<a class="' . implode( ' ', $classes ) . '" href="' . esc_attr( $link ) . '" target="' . esc_attr( $item->link_target( $grid ) ) . '"' . $rel . '' . $data_string .'>' . $html . '</a>';
				$url = $link;
			} else {
				$output = '<div class="' . implode( ' ', $classes ) . '"' . $data_string .'>' . $html . '</div>';
			}
		}

		return array(
			'output' => $output,
			'url' => $url,
		);
	}
}
