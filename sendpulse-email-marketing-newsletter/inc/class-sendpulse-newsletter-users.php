<?php

/**
 * Add Wordpress users to address book.
 *
 * Class Send_Pulse_Newsletter_Users
 */
class Send_Pulse_Newsletter_Users {

	/**
	 * Send_Pulse_Newsletter_Users constructor.
	 */
	public function __construct() {

		$is_subscribe_after_register = Send_Pulse_Newsletter_Settings::get_option( 'is_subscribe_after_register', 'sp_api_setting' );

		add_action( 'user_register', array( $this, 'save_user_ip' ), 20 );

		if ( 'on' == $is_subscribe_after_register ) {
			add_action( 'user_register', array( $this, 'subscribe_after_register' ), 30 );
		}

	}

	/**
	 * Save user IP in DB
	 *
	 * @param int $user_id User ID.
	 */
	public function save_user_ip( $user_id ) {
		if ( ! is_admin() ) {
			update_user_meta( $user_id, '_sp_user_ip', self::define_user_ip() );
		}

	}

	/**
	 * @param int $user_id User ID.
	 *
	 * @return string User IP.
	 *
	 */

	public static function get_user_ip( $user_id ) {
		return get_user_meta( $user_id, '_sp_user_ip', true );
	}

	/**
	 * Subscribe new user
	 *
	 * @param int $user_id User ID.
	 */
	public function subscribe_after_register( $user_id ) {

		$user = new WP_User( $user_id );

		$user_ip = self::get_user_ip( $user_id );

		$api = new Send_Pulse_Newsletter_API();


		$emails = array(
			array(
				'email'     => $user->user_email,
				'variables' => array(
					'name' => $user->display_name
				)
			)
		);

		if ( $user_ip ) {
			$emails[0]['variables']['subscribe_ip'] = $user_ip;
		}

		$result = $api->addEmails( $api->default_book, $emails );

		if ( isset( $result->is_error ) && $result->is_error ) {
			$msg = isset( $result->message ) ? $result->message : __( 'Something went wrong', 'sendpulse-email-marketing-newsletter' );

			error_log( 'SendPulse Newsletter: ' . $msg );
		}
	}

	/**
	 * Get user IP
	 *
	 * @return string Users IP
	 *
	 */

	public static function define_user_ip() {
		$client  = @$_SERVER['HTTP_CLIENT_IP'];
		$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote  = $_SERVER['REMOTE_ADDR'];

		if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
			$ip = $client;
		} elseif ( filter_var( $forward, FILTER_VALIDATE_IP ) ) {
			$ip = $forward;
		} else {
			$ip = $remote;
		}

		return $ip;
	}


}

new Send_Pulse_Newsletter_Users();