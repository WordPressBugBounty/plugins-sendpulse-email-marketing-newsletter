<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 *
 * Class Send_Pulse_Newsletter_Requirement
 */
class Send_Pulse_Newsletter_Requirement {

	/**
	 * @var bool Is check requirement?
	 */
	protected $success = false;

	/**
	 * @var array Message for user
	 */
	protected $error_msg = array();


	/**
	 * Send_Pulse_Newsletter_Requirement constructor.
	 */
	public function __construct() {

		$this->php_check();
		if ( ! class_exists( 'DOMDocument' ) ) {
			add_action( 'admin_notices', array( $this, 'dom_document_notice' ) );
		}
		if ( ! $this->success ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

	}

	/**
	 *
	 * Checking PHP version
	 *
	 */
	public function php_check() {
		$this->success = version_compare( PHP_VERSION, '8.0.0', '>=' );
		$this->error_msg[] = 'php';
	}

	public function is_folder_writable( $folder_path ) {
		global $wp_filesystem;

		// Initialize WP Filesystem
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Check if the folder exists and is writable using WP_Filesystem
		if ( $wp_filesystem->is_dir( $folder_path ) && $wp_filesystem->is_writable( $folder_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Display message for user
	 */
	public function admin_notices() {

		$message = '';

		if ( in_array( 'php', $this->error_msg ) ) {
			$message = sprintf( '<p><strong>%s</strong></p><p>%s</p>', __( 'The "SendPulse Email Marketing Newsletter" plugin requires PHP 8.0 or higher and cannot run on this site.', 'sendpulse-email-marketing-newsletter' ), __( 'Please ask your hosting provider to <a href="https://wordpress.org/about/requirements/">upgrade PHP</a>.', 'sendpulse-email-marketing-newsletter' ) );
		}

		printf( '<div class="notice notice-error">%s</div>', wp_kses_post( $message ) );
	}

	/**
	 * Notify admins that the PHP DOM extension is missing.
	 */
	public function dom_document_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>%1$s</strong></p><p>%2$s</p></div>',
			esc_html__( 'SendPulse form embeds require the PHP DOM extension.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'The plugin is active, but SendPulse forms cannot be rendered until ext-dom is available on this server.', 'sendpulse-email-marketing-newsletter' )
		);
	}

	/**
	 * @return bool Getter.
	 *
	 */
	public function is_success() {
		return $this->success;
	}

}
