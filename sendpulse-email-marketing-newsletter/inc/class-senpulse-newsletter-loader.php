<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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

    public function admin_assets( $hook ) {
        // Always load CSS in admin (menu icon & basic styling).
        wp_enqueue_style(
            'sendpulse-email-marketing-newsletter-admin-style',
            $this->plugin_url . 'assets/css/sp-newsletter-admin.css',
            array(),
            $this->version
        );

        // Always load the dismiss script (safe, no sensitive data).
        wp_enqueue_script(
            'sendpulse-email-marketing-newsletter-dismiss-script',
            $this->plugin_url . 'assets/js/sp-newsletter-admin-dismiss-script.js',
            array( 'jquery' ),
            $this->version,
            true
        );

        // Localize vars used by sp-newsletter-admin-dismiss-script.js.
        wp_localize_script(
            'sendpulse-email-marketing-newsletter-dismiss-script',
            'sp_emp_dismiss_script_vars',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
            )
        );

        // From here on, only care about importer (sensitive) stuff.
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        $screen = get_current_screen();

        // Plugin-specific pages (settings & import).
        $plugin_pages = array(
            'sendpulse_form_page_send_pulse_settings',
            'sendpulse_form_page_send_pulse_import',
        );

        $is_plugin_page = $screen && in_array( $screen->id, $plugin_pages, true );

        // Only load importer JS + nonce on plugin pages AND only for admins.
        if ( $is_plugin_page && current_user_can( 'manage_options' ) ) {
            wp_enqueue_script(
                'sendpulse-email-marketing-newsletter-importer-script',
                $this->plugin_url . 'assets/js/sp-newsletter-admin-importer.js',
                array( 'jquery' ),
                $this->version,
                true
            );

            wp_localize_script(
                'sendpulse-email-marketing-newsletter-importer-script',
                'sp_admin_params',
                array(
                    'ajax_url'    => admin_url( 'admin-ajax.php' ),
                    '_ajax_nonce' => wp_create_nonce( 'sendpulse_import' ),
                )
            );
        }
    }
}
