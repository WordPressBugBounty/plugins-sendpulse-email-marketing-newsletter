<?php
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
		$this->success = version_compare( PHP_VERSION, '5.2.4', '>=' );
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
			$message = sprintf( '<p><strong>%s</strong></p><p>%s</p>', __( "We've noticed that you're running an outdated version of PHP and plugin \"SendPulse Email Marketing Newsletter\" can't run.", 'sendpulse-email-marketing-newsletter' ), __( "Ask you hosting <a href=\"https://wordpress.org/about/requirements/\">update PHP</a>.", 'sendpulse-email-marketing-newsletter' ) );
		}

		printf( '<div class="notice notice-error">%s</div>', wp_kses_post( $message ) );
	}

	/**
	 * @return bool Getter.
	 *
	 */
	public function is_success() {
		return $this->success;
	}

}

