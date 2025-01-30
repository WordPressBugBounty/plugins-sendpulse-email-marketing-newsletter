<?php

/**
 * Register and render plugins shortcodes
 *
 * Class Send_Pulse_Newsletter_Shortcodes
 */
class Send_Pulse_Newsletter_Shortcodes {

	/**
	 * SP_Shortcodes constructor.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Init action
	 */
	public function init() {
		add_shortcode( 'sendpulse-form', array( $this, 'subscribe_form' ) );
	}

    public function is_allowed_script($output, $allowed_urls) {
        foreach ($allowed_urls as $url) {
            if (strpos($output, $url) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate subscribe form shortcode
     *
     * @return string Subscribe form html.
     */
    public function subscribe_form( $atts ) {
        $output = '';

        if ( $atts && isset( $atts['id'] ) ) {
            $post_id = $atts['id'];

            $allowed_urls = array(
                'web.webformscr.com',
                'static-login.sendpulse.com'
            );

            $output  = get_post_meta( $post_id, '_sp_form_code', true );
            if ($this->is_allowed_script($output, $allowed_urls)) {
                return $output; // Safe to output
            } else {
                return esc_html($output); // Escape unexpected input
            }
        }

        return esc_html($output);
    }

}

new Send_Pulse_Newsletter_Shortcodes();