<?php

/**
 * Loader plugin class.
 *
 * Class Send_Pulse_Newsletter_Loader
 */
class Send_Pulse_Newsletter_Loader {

	/**
	 * @var string Plugin version
	 */
	private $version = SP_EMAIL_MARKETING_VERSION;

	/**
	 * @var string Plugin url. Useful for enqueue assets.
	 */
	private $plugin_url;

	/**
	 * Send_Pulse_Newsletter constructor.
	 *
	 * @param string $plugin_url
	 *
	 */
	public function __construct( $plugin_url ) {
		$this->plugin_url = $plugin_url;
		$this->inc();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	/**
	 * Include libraries and additional class.
	 */
	protected function inc() {
		$vendor_dir = dirname( __FILE__ ) . '/../vendor/';
		include_once( $vendor_dir . 'sendpulse/rest-api/src/Contracts/TokenStorageInterface.php' );
        include_once( $vendor_dir . 'sendpulse/rest-api/src/Contracts/ApiInterface.php' );
		include_once( $vendor_dir . 'sendpulse/rest-api/src/Storage/FileStorage.php' );
		include_once( $vendor_dir . 'sendpulse/rest-api/src/Storage/SessionStorage.php' );
        include_once( $vendor_dir . 'sendpulse/rest-api/src/ApiClient.php' );
        include_once( $vendor_dir . 'sendpulse/rest-api/src/ApiClientException.php' );
		include_once( 'class-senpulse-newsletter-forms.php' );
		include_once( 'class-sendpulse-newsletter-api.php' );
		include_once( 'class-sendpulse-newsletter-settings.php' );
		include_once( 'class-senpulse-newsletter-shortcodes.php' );
		include_once( 'class-sendpulse-newsletter-ajax.php' );
		include_once( 'class-sendpulse-newsletter-users.php' );
	}

	public function admin_assets() {
		wp_enqueue_style( 'sp-admin-style', $this->plugin_url . "assets/css/sp-newsletter-admin.css", array(), $this->version );
        wp_enqueue_script( 'sp-admin-dismiss-script', $this->plugin_url . "assets/js/sp-newsletter-admin-dismiss-script.js", array( 'jquery' ), $this->version, true );
        wp_enqueue_script( 'sp-admin-importer-script', $this->plugin_url . "assets/js/sp-newsletter-admin-importer.js", array( 'jquery' ), $this->version, true );

        wp_localize_script( 'sp-admin-importer-script', 'sp_admin_params', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            '_ajax_nonce' => wp_create_nonce( 'sendpulse_import' )
        ] );
	}
	
}