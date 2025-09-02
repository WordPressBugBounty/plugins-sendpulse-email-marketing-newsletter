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

    private function is_allowed_script($script, $allowed_hosts) {
        // Prevent DOMDocument error on empty input
        if (trim($script) === '') {
            return false;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();

        if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
            // Modern PHP: Load without adding <html><body>...</body></html> tags
            $dom->loadHTML($script, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } else {
            // Old PHP fallback: manually wrap inside html/body
            $dom->loadHTML('<html><body>' . $script . '</body></html>');
        }

        $scripts = $dom->getElementsByTagName('script');
        if ($scripts->length !== 1) {
            return false;
        }

        $tag = $scripts->item(0);
        $src = $tag->getAttribute('src');
        if (!$src) {
            return false;
        }

        if (strpos($src, '//') === 0) {
            $src = 'https:' . $src;
        }

	    $parts = wp_parse_url( $src );
	    $host = $parts['host'] ?? '';
	    if ( ! $host || ! in_array( strtolower( $host ), array_map( 'strtolower', $allowed_hosts ), true ) ) {
		    return false;
	    }

        $allowed_attrs = ['src', 'async', 'sp-form-id', 'type'];
        foreach ($tag->attributes as $attr) {
            $name = strtolower($attr->name);
            $value = strtolower($attr->value);

            if (!in_array($name, $allowed_attrs, true)) {
                return false;
            }

            if ($name === 'type' && $value !== 'text/javascript') {
                return false;
            }
        }

        return true;
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