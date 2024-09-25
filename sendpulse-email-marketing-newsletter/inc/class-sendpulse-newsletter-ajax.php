<?php

/**
 * Handle ajax actions.
 *
 * Class Send_Pulse_Newsletter_Ajax
 */
class Send_Pulse_Newsletter_Ajax {


	/**
	 * Register ajax actions for logged and un-logged user.
	 *
	 * Send_Pulse_Newsletter_Ajax constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_sendpulse_import', array( $this, 'import' ) );

	}

	/**
	 * Handle import ajax action.
	 */
	public function import() {

		check_ajax_referer( 'sendpulse_import' );

		$book = isset( $_POST['book'] ) ? sanitize_text_field( $_POST['book'] ) : '';
		$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';

		$msg = array(); // log emulation

		if ( empty( $book ) ) {
			$msg[] = ( __( 'Please, select Address Book', 'sendpulse-email-marketing-newsletter' ) );
		}

		if ( empty( $role ) ) {
			$msg[] = ( __( 'Please, select Users Role', 'sendpulse-email-marketing-newsletter' ) );
		}

		if ( empty( $msg ) ) {

			$msg[] = current_time( 'mysql' ) . ' ' . __( 'Import start', 'sendpulse-email-marketing-newsletter' );


			$api = new Send_Pulse_Newsletter_API();

			$emails = array();

			$users = get_users( array(
					'role' => $role
				)
			);


			foreach ( $users as $user ) {
				$email = array(
					'email'     => $user->user_email,
					'variables' => array(
						'name' => $user->display_name
					)
				);

				$user_ip = Send_Pulse_Newsletter_Users::get_user_ip( $user->ID );

				if ( $user_ip ) {
					$email['variables']['subscribe_ip'] = $user_ip;
				}

				$emails[] = $email;

				$msg[] = sprintf( '%s: %s %s', __( 'Add user', 'sendpulse-email-marketing-newsletter' ), $user->user_email, $user->display_name );
			}

			$result = $api->addEmails( $book, $emails );

			if ( isset( $result->is_error ) && $result->is_error ) {
				$msg[] = isset( $result->message ) ? $result->message : __( 'Something went wrong. Import unsuccessful', 'sendpulse-email-marketing-newsletter' );
			}


			$msg[] = current_time( 'mysql' ) . ' ' . __( 'Import finished', 'sendpulse-email-marketing-newsletter' );

		}

		wp_send_json_success( array( 'msg' => implode( "\n", $msg ) ) );


	}

}

new Send_Pulse_Newsletter_Ajax();