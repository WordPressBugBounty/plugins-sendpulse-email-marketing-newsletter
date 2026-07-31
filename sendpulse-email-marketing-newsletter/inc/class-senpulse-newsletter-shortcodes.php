<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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

	/**
	 * Normalize a shortcode form ID without changing legacy absint semantics.
	 *
	 * @param mixed $id Shortcode attribute value.
	 *
	 * @return int
	 */
	public function normalize_form_id( $id ) {
		if ( ! is_scalar( $id ) ) {
			return 0;
		}

		return absint( $id );
	}

	/**
     * Generate subscribe form shortcode
     *
     * @return string Subscribe form html.
     */
	    public function subscribe_form( $atts ) {
	        $atts = shortcode_atts(
				array(
					'id' => 0,
				),
				(array) $atts,
				'sendpulse-form'
				);

	        $post_id = $this->normalize_form_id( $atts['id'] );

	        if ( ! $post_id ) {
		        return '';
	        }

	        if ( 'sendpulse_form' !== get_post_type( $post_id ) ) {
	        	return '';
	        }

	        $post_status = get_post_status( $post_id );
	        if ( false === $post_status || in_array( $post_status, array( 'trash', 'auto-draft' ), true ) ) {
	        	return '';
	        }

	        $output = get_post_meta( $post_id, '_sp_form_code', true );
	        $normalized_output = sendpulse_email_marketing_newsletter_normalize_form_embed_code( $output );

	        return is_string( $normalized_output ) && '' !== $normalized_output ? $normalized_output : '';
	    }

}

new Send_Pulse_Newsletter_Shortcodes();
