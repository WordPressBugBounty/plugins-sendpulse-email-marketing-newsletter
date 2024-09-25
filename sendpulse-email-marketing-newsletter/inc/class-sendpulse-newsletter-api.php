<?php

/**
 * Extend library class.
 *
 * Class Send_Pulse_Newsletter_API
 */
class Send_Pulse_Newsletter_API extends SendpulseApi {

	/**
	 * @var string|int Id default address book for subscribe.
	 */
	public $default_book;


	/**
	 * Get client option. Api ready for using.
	 *
	 * Send_Pulse_Newsletter_API constructor.
	 */
	public function __construct() {
		$user_id = $this->get_option( 'client_id' );
		$secret  = $this->get_option( 'client_secret' );

		$this->requirement = new Send_Pulse_Newsletter_Requirement();

		$storage = null;
		$notice_action = '';

		switch (true) {
			case $this->requirement->is_folder_writable(SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR) !== true:
				$storage = new SessionStorage();
				$notice_action = 'sp_emp_admin_activated_session_storage_notice';
				break;

			default:
				$storage = new FileStorage(SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR);
				$notice_action = 'sp_emp_admin_activated_file_storage_notice';
				break;
		}

		parent::__construct($user_id, $secret, $storage);
		add_action( 'admin_notices', array( $this, $notice_action ) );

		$this->default_book = $this->get_option( 'default_book' );
	}


	/**
	 * Get plugin API Settings option.
	 *
	 * @param $name string Option name.
	 *
	 * @return string Option value
	 */
	public function get_option( $name ) {
		return Send_Pulse_Newsletter_Settings::get_option( $name, 'sp_api_setting' );
	}

	public function sp_emp_admin_activated_session_storage_notice() {
		$notice_dismissed = get_option('sp_emp_session_storage_notice_dismissed');

		if ($notice_dismissed) {
			return; // Don't display the notice if it has been dismissed
		}

		$message = sprintf(
			'<div class="notice notice-warning is-dismissible">
			<p><strong>%s</strong></p>
			<p>%s</p>
			<p>%s<br>%s</p>
			<p>%s</p>
			<p>%s</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">%s</span>
			</button>
		</div>',
			esc_html__( 'The "SendPulse Email Marketing Newsletter" plugin is activated in safe mode with the SessionStorage feature enabled. The feature stores data only for the time of current session.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'To use the file storage for data, change the directory rights in the plugin storage folder to 775 (/wp-content/plugins/sendpulse-email-marketing-newsletter/storage). ', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'You should also make sure that the owner or group is set as www-data for the plugin to save data to the folder. ', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'If you are using Docker, check the relevant resources on setting up the environment or contact a dedicated tech specialist.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'It is recommended to use only SessionStorage for blogs built with wordpress.com. SessionStorage works same as FileStorage.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'You can close this notification by clicking the close button and it will never show up again.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'Dismiss this notification and never show it again.', 'sendpulse-email-marketing-newsletter' )
		);
		echo wp_kses_post( $message );
	}

	public function sp_emp_admin_activated_file_storage_notice() {
		$notice_dismissed = get_option('sp_emp_file_storage_notice_dismissed');

		if ($notice_dismissed) {
			return; // Don't display the notice if it has been dismissed
		}

		$message = sprintf(
			'<div class="notice notice-success is-dismissible">
			<p><strong>%s</strong></p>
			<p>%s</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">%s</span>
			</button>
		</div>',
			esc_html__( 'The "SendPulse Email Marketing Newsletter" plugin has been successfully activated in normal mode with the FileStorage feature enabled.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'You can close this notification by clicking the close button and it will never show up again.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'Dismiss this notification and never show it again.', 'sendpulse-email-marketing-newsletter' ),
		);
		echo wp_kses_post( $message );
	}


}