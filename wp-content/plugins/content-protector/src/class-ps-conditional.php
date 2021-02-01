<?php

namespace passster;

class PS_Conditional {

	/**
	 * Check if valid authentication exists.
	 *
	 * @param  array $atts array of attributes.
	 * @return boolean
	 */
	public static function is_valid( $atts ) {

		// valid user.
		if ( self::is_user_valid( $atts ) ) {
			return true;
		}

		// valid link.
		if ( self::is_link_valid( $atts ) ) {
			return true;
		}

		// is Cookie set?
		if ( ! isset( $_COOKIE['passster'] ) || empty( $_COOKIE['passster'] ) ) {
			return false;
		}

		$input = sanitize_text_field( $_COOKIE['passster'] );

		// password.
		if ( isset( $atts['password'] ) ) {
			if ( $input == $atts['password'] ) {
				return true;
			}
		}

		// passwords.
		if ( isset( $atts['passwords'] ) ) {
			if ( strpos( $atts['passwords'], $input ) !== false ) {
				return true;
			}
		}

		// password lists.
		if ( isset( $atts['password_list'] ) ) {
			$passwords = get_post_meta( $atts['password_list'], 'passster_passwords', true );
			if ( strpos( $passwords, $input ) != false ) {
				return true;
			}
		}

		// captcha.
		if ( isset( $atts['captcha'] ) ) {
			if ( 'captcha' == $input ) {
				return true;
			}
		}

		// recaptcha.
		if ( isset( $atts['recaptcha'] ) ) {
			if ( 'recaptcha' == $input ) {
				return true;
			}
		}

		// if nothing was correct.
		return false;
	}

	/**
	 * Return user name and role as array
	 *
	 * @param array $atts added attributes.
	 * @return boolean
	 */
	public static function is_user_valid( $atts ) {

		$unlock = false;
		$user   = wp_get_current_user();

		if ( isset( $atts['role'] ) && ! empty( $atts['role'] ) ) {

			$roles = $user->roles;

			if ( strpos( $atts['role'], ',' ) !== false ) {
				$roles_array = explode( ',', $atts['role'] );

				foreach ( $roles_array as $role ) {
					if ( in_array( $role, $roles ) ) {
						$unlock = true;
					}
				}
			} else {
				if ( in_array( $atts['role'], $roles ) ) {
					$unlock = true;
				}
			}
		}

		if ( isset( $atts['user'] ) && ! empty( $atts['user'] ) ) {

			if ( strpos( $atts['user'], ',' ) !== false ) {
				$users_array = explode( ',', $atts['user'] );

				foreach ( $users_array as $user ) {
					if ( $user === $user->user_login ) {
						$unlock = true;
					}
				}
			} else {
				if ( $atts['user'] === $user->user_login ) {
					$unlock = true;
				}
			}
		}
		return $unlock;
	}

	/**
	 * Check if link is valid.
	 *
	 * @param  array $atts array of attributes.
	 * @return boolean
	 */
	public static function is_link_valid( $atts ) {

		// is link set?
		if ( isset( $_GET['pass'] ) && ! empty( $_GET['pass'] ) ) {
			$input = PS_Helper::base64_url_decode( $_GET['pass'] );

			// password.
			if ( isset( $atts['password'] ) ) {
				if ( $input == $atts['password'] ) {
					return true;
				}
			}

			// passwords.
			if ( isset( $atts['passwords'] ) ) {
				if ( strpos( $atts['passwords'], $input ) !== false ) {
					return true;
				}
			}

			// password lists.
			if ( isset( $atts['password_list'] ) ) {
				$passwords = get_post_meta( $atts['password_list'], 'passster_passwords', true );
				if ( strpos( $passwords, $input ) != false ) {
					return true;
				}
			}
		}
	}
}
