<?php
/**
 * Responsible for promoting the plugin.
 *
 * @link       https://bootstrapped.ventures
 * @since      3.0.0
 *
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/admin
 */

/**
 * Responsible for promoting the plugin.
 *
 * @since      3.0.0
 * @package    WP_Ultimate_Post_Grid
 * @subpackage WP_Ultimate_Post_Grid/includes/admin
 * @author     Brecht Vandersmissen <brecht@bootstrapped.ventures>
 */
class WPUPG_Marketing {

	private static $campaign = false;

	/**
	 * Register actions and filters.
	 *
	 * @since    3.1.0
	 */
	public static function init() {
		$campaigns = array(
			'black-friday-2020' => array(
				'start' => new DateTime( '2020-11-25 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'end' => new DateTime( '2020-12-02 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'notice_title' => 'Black Friday & Cyber Monday Deal',
				'notice_text' => 'Get a 30% discount right now!',
				'page_title' => 'Black Friday Discount!',
				'page_text' => 'Good news: we\'re having a Black Friday & Cyber Monday sale and you can get a <strong>30% discount on any of our plugins</strong>. Just use this code on the checkout page: <em>BF2020</em>',
			),
			'birthday-2021' => array(
				'start' => new DateTime( '2021-01-25 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'end' => new DateTime( '2021-02-01 10:00:00', new DateTimeZone( 'Europe/Brussels' ) ),
				'notice_title' => 'Celebrating my 33rd birthday',
				'notice_text' => 'Get a 30% discount right now!',
				'page_title' => 'Birthday Discount!',
				'page_text' => 'Good news: I\'m celebrating my 33rd birthday with a <strong>30% discount on any of our plugins</strong>. Just use this code on the checkout page: <em>BDAY2021</em>',
			),
		);

		$now = new DateTime();

		foreach ( $campaigns as $id => $campaign ) {
			if ( $campaign['start'] < $now && $now < $campaign['end'] ) {
				$campaign['id'] = $id;
				self::$campaign = $campaign;
				break;
			}
		}

		if ( false !== self::$campaign ) {
			add_action( 'admin_menu', array( __CLASS__, 'add_submenu_page' ), 99 );
			add_filter( 'wpupg_admin_notices', array( __CLASS__, 'marketing_notice' ) );
		}
	}

	/**
	 * Add the marketing menu page.
	 *
	 * @since    3.1.0
	 */
	public static function add_submenu_page() {
		if ( ! WPUPG_Addons::is_active( 'premium' ) ) {
			add_submenu_page( 'wpultimatepostgrid', 'WPUPG Discount', '~ 30% Discount! ~', 'manage_options', 'wpupg_marketing', array( __CLASS__, 'page_template' ) );
		}
	}

	/**
	 * Template for the marketing page.
	 *
	 * @since    3.1.0
	 */
	public static function page_template() {
		echo '<div class="wrap">';
		echo '<h1>' . self::$campaign['page_title'] . '</h1>';
		echo '<p style="font-size: 14px; max-width: 600px;">' . self::$campaign['page_text'] . '</p>';

		// Countdown.
		$now = new DateTime();
		$interval = $now->diff( self::$campaign['end'] );
		echo '<p style="color: darkred; font-size: 14px;"><strong>Don\'t miss out!</strong><br/>Only ';
		printf( _n( '%s day', '%s days', $interval->d, 'wp-ultimate-post-grid' ), number_format_i18n( $interval->d ) );
		echo ' ';
		printf( _n( '%s hour', '%s hours', $interval->h, 'wp-ultimate-post-grid' ), number_format_i18n( $interval->h ) );
		echo ' ';
		printf( _n( '%s minute', '%s minutes', $interval->i, 'wp-ultimate-post-grid' ), number_format_i18n( $interval->i ) );
		echo ' left.</p>';

		// CTA.
		$params = '?utm_source=wpupg&utm_medium=plugin&utm_campaign=' . urlencode( self::$campaign['id'] );

		if ( WPUPG_Addons::is_active( 'premium' ) ) {
			echo '<a href="https://bootstrapped.ventures/' . $params . '" target="_blank" class="button button-primary" style="font-size: 14px;">Check out our Plugins</a>';
		} else {
			echo '<a href="https://bootstrapped.ventures/wp-ultimate-post-grid/get-the-plugin/' . $params . '" target="_blank" class="button button-primary" style="font-size: 14px;">Get the Premium plugin now</a>';
		}
		
		echo '</div>';
	}

	/**
	 * Show the marketing notice.
	 *
	 * @since    3.1.0
	 * @param	array $notices Existing notices.
	 */
	public static function marketing_notice( $notices ) {
		if ( ! WPUPG_Addons::is_active( 'premium' ) ) {
			$notices[] = array(
				'id' => 'marketing_' . self::$campaign['id'],
				'title' => self::$campaign['notice_title'],
				'text' => '<a href="' . esc_url( admin_url( 'admin.php?page=wpupg_marketing' ) ). '">' . self::$campaign['notice_text'] . '</a>',
			);
		}

		return $notices;
	}
}

WPUPG_Marketing::init();
