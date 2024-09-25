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

	public function is_folder_writable($folder_path) {
		$writable = false;

		// Check if the folder exists
		if (file_exists($folder_path)) {
			// Check if the folder is writable
			if (is_writable($folder_path)) {
				$writable = true;
			}
		}

		return $writable;
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

