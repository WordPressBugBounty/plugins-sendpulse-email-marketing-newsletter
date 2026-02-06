<?php

/**
 * Handle ajax actions.
 *
 * Class Send_Pulse_Newsletter_Ajax
 */
class Send_Pulse_Newsletter_Ajax {

    private $log_key = 'sendpulse_import_log';

    private function log_progress( $msg ) {
        $log = get_transient( $this->log_key );
        if ( ! $log ) {
            $log = [];
        }

        $log[] = current_time( 'mysql' ) . ' ' . $msg;
        set_transient( $this->log_key, $log, 60 );
    }

	/**
	 * Register ajax actions for logged and un-logged user.
	 *
	 * Send_Pulse_Newsletter_Ajax constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_sendpulse_import', [ $this, 'import' ] );
        add_action( 'wp_ajax_sendpulse_get_import_data', [ $this, 'get_import_data' ] );
        add_action( 'wp_ajax_sendpulse_get_import_data', [ $this, 'ajax_get_import_data' ] );
        add_action( 'wp_ajax_sendpulse_get_import_log',  [ $this, 'get_import_log' ] );
    }

	/**
	 * Handle import ajax action.
	 */
	public function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You are not allowed to perform this action.', 'sendpulse-email-marketing-newsletter' ) ],
				403
			);
		}

		delete_transient( $this->log_key );
		/** @phpstan-ignore-next-line */
		$this->log_progress( __( 'Import started', 'sendpulse-email-marketing-newsletter' ) );

		check_ajax_referer( 'sendpulse_import' );

		$book = isset( $_POST['book'] ) ? sanitize_text_field( wp_unslash( $_POST['book'] ) ) : '';
		$role = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
		$msg  = [];

		if ( empty( $book ) ) {
			$msg[] = __( 'Please, select Address Book', 'sendpulse-email-marketing-newsletter' );
		}
		if ( empty( $role ) ) {
			$msg[] = __( 'Please, select Users Role', 'sendpulse-email-marketing-newsletter' );
		}

		if ( ! empty( $msg ) ) {
			wp_send_json_success( [ 'msg' => implode( "\n", $msg ) ] );
		}

		$api   = new Send_Pulse_Newsletter_API();
		$users = get_users( [ 'role' => $role ] );

		if ( empty( $users ) ) {
			$msg[] = __( 'No users found with selected role.', 'sendpulse-email-marketing-newsletter' );
			wp_send_json_success( [ 'msg' => implode( "\n", $msg ) ] );
		}

		foreach ( $users as $user ) {
			$email     = sanitize_email( $user->user_email );
			$variables = [ 'name' => $user->display_name ];

			$user_ip = Send_Pulse_Newsletter_Users::get_user_ip( $user->ID );
			if ( ! empty( $user_ip ) ) {
				$variables['subscribe_ip'] = $user_ip;
			}

			/* translators: %1$s: user email, %2$s: user display name */
			$this->log_progress(sprintf(__( 'Adding user: %1$s (%2$s)', 'sendpulse-email-marketing-newsletter' ),
					$email,
					$user->display_name
				)
			);

			$result = $api->add_contact_to_list( $email, $book, $variables );

			if ( is_wp_error( $result ) ) {
				/* translators: %1$s: user email, %2$s: error message */
				$this->log_progress(sprintf(__( 'Error adding %1$s: %2$s', 'sendpulse-email-marketing-newsletter' ),
						$email,
						$result->get_error_message()
					)
				);
			} else {
				/* translators: %s: user email */
				$this->log_progress(sprintf(__( 'Successfully added %s', 'sendpulse-email-marketing-newsletter' ),
						$email
					)
				);
			}
		}

		$this->log_progress( __( 'Import finished', 'sendpulse-email-marketing-newsletter' ) );

		/* translators: This message appears in the import log when the process completes */
		wp_send_json_success(
			[
				'msg' => __( 'Import finished. You can check the log.', 'sendpulse-email-marketing-newsletter' ),
			]
		);
	}

	public function get_import_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[ 'message' => __( 'You are not allowed to perform this action.', 'sendpulse-email-marketing-newsletter' ) ],
				403
			);
		}

        check_ajax_referer( 'sendpulse_import' );

        $api = new Send_Pulse_Newsletter_API();
        $books = $api->listAddressBooks();

        $roles = wp_roles()->roles;
        $formatted_roles = array();

        foreach ( $roles as $key => $role ) {
            $formatted_roles[] = array(
                'value' => $key,
                'label' => $role['name'],
            );
        }

        wp_send_json_success( array(
            'books' => $books,
            'roles' => $formatted_roles,
        ) );
    }

    public function ajax_get_import_data() {
	    if ( ! current_user_can( 'manage_options' ) ) {
		    wp_send_json_error(
			    [ 'message' => __( 'You are not allowed to perform this action.', 'sendpulse-email-marketing-newsletter' ) ],
			    403
		    );
	    }

        check_ajax_referer( 'sendpulse_import' );

        $books = [];
        $roles = [];

        try {
            $api = new Send_Pulse_Newsletter_API();
            $books = $api->get_books(); // Returns array with ['id', 'name']
        } catch ( Exception $e ) {
            wp_send_json_error( [ 'message' => 'Failed to fetch books: ' . $e->getMessage() ] );
        }

        // Get all roles from WP
        global $wp_roles;
        foreach ( $wp_roles->roles as $key => $role ) {
            $roles[] = [
                'value' => $key,
                'label' => $role['name']
            ];
        }

        wp_send_json_success( [
            'books' => $books,
            'roles' => $roles
        ] );
    }

    public function get_import_log() {
	    if ( ! current_user_can( 'manage_options' ) ) {
		    wp_send_json_error(
			    [ 'message' => __( 'You are not allowed to perform this action.', 'sendpulse-email-marketing-newsletter' ) ],
			    403
		    );
	    }

        check_ajax_referer( 'sendpulse_import' );

        $log = get_transient( $this->log_key );
        if ( ! is_array( $log ) ) {
            $log = [];
        }

        wp_send_json_success([
            'log' => implode( "\n", $log )
        ]);
    }

}

new Send_Pulse_Newsletter_Ajax();