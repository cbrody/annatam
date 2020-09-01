<?php
add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
   wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}

// CB mods
add_action( 'init', 'at_add_excerpts_to_pages' );
function at_add_excerpts_to_pages() {
     add_post_type_support( 'page', 'excerpt' );
}

function spacious_footer_copyright() {
	$site_link = '<a href="' . esc_url( home_url( '/' ) ) . '" title="' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '" ><span>' . get_bloginfo( 'name', 'display' ) . '</span></a>';

	$wp_link = '<a href="'.esc_url( 'http://wordpress.org' ).'" target="_blank" title="' . esc_attr__( 'WordPress', 'spacious' ) . '"><span>' . __( 'WordPress', 'spacious' ) . '</span></a>';

	$tg_link =  '<a href="'.esc_url( 'http://themegrill.com/themes/spacious' ).'" target="_blank" title="'.esc_attr__( 'ThemeGrill', 'spacious' ).'" rel="designer"><span>'.__( 'ThemeGrill', 'spacious') .'</span></a>';

	$cb_link = '<a href="'.esc_url( 'http://cbrody.com' ).'" target="_blank" title="' . esc_attr__( 'cbrody.com', 'spacious' ) . '"><span>' . __( 'cbrody.com', 'spacious' ) . '</span></a>';

	$default_footer_value = sprintf( __( 'Copyright &copy; %1$s %2$s.', 'spacious' ), date( 'Y' ), $site_link ).' '.sprintf( __( 'Site created by %s.', 'cbrody.com' ), $cb_link );

	$spacious_footer_copyright = '<div class="copyright">'.$default_footer_value.'</div>';
	echo $spacious_footer_copyright;
}

/**
 * Change number or products per row to 3
 */
add_filter('loop_shop_columns', 'loop_columns', 999);
if (!function_exists('loop_columns')) {
	function loop_columns() {
		return 3; // 3 products per row
	}
}

?>
