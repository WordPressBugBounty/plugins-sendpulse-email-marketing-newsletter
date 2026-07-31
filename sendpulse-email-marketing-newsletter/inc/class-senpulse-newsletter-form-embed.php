<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize and validate SendPulse form embed code.
 */
	if ( ! function_exists( 'sendpulse_email_marketing_newsletter_parse_form_embed_code' ) ) {
		function sendpulse_email_marketing_newsletter_parse_form_embed_code( $code ) {
			if ( ! is_string( $code ) ) {
				return null;
			}

			$code = trim( $code );
			if ( '' === $code ) {
				return '';
			}

			if ( ! class_exists( 'DOMDocument' ) ) {
				return null;
			}

			$previous_use_internal_errors = libxml_use_internal_errors( true );
			$dom                          = new DOMDocument();
		$loaded                       = $dom->loadHTML(
			$code,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_use_internal_errors );

		if ( ! $loaded ) {
			return null;
		}

		$script_node = null;

		foreach ( $dom->childNodes as $child_node ) {
			if ( XML_TEXT_NODE === $child_node->nodeType ) {
				if ( '' === trim( $child_node->textContent ) ) {
					continue;
				}

				return null;
			}

			if ( XML_COMMENT_NODE === $child_node->nodeType || XML_PI_NODE === $child_node->nodeType ) {
				return null;
			}

			if ( XML_ELEMENT_NODE !== $child_node->nodeType ) {
				return null;
			}

			if ( null !== $script_node ) {
				return null;
			}

			$script_node = $child_node;
		}

		if ( ! ( $script_node instanceof DOMElement ) || 'script' !== strtolower( $script_node->tagName ) ) {
			return null;
		}

		if ( '' !== trim( $script_node->textContent ) ) {
			return null;
		}

		$allowed_attributes = array( 'async', 'sp-form-id', 'src', 'type' );
		$script_src         = '';
		$script_form_id     = '';
		$script_has_async   = false;
		$script_type        = '';

		foreach ( $script_node->attributes as $attribute ) {
			$attribute_name  = strtolower( $attribute->name );
			$attribute_value = trim( (string) $attribute->value );

			if ( ! in_array( $attribute_name, $allowed_attributes, true ) ) {
				return null;
			}

			switch ( $attribute_name ) {
				case 'async':
					if ( '' !== $attribute_value && 'async' !== strtolower( $attribute_value ) ) {
						return null;
					}
					$script_has_async = true;
					break;

				case 'sp-form-id':
					if ( '' === $attribute_value ) {
						return null;
					}
					$script_form_id = $attribute_value;
					break;

				case 'src':
					if ( '' === $attribute_value ) {
						return null;
					}
					$script_src = html_entity_decode( $attribute_value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					break;

				case 'type':
					if ( '' !== $attribute_value && 'text/javascript' !== strtolower( $attribute_value ) ) {
						return null;
					}
					$script_type = 'text/javascript';
					break;
			}
		}

		if ( '' === $script_src || '' === $script_form_id ) {
			return null;
		}

		$parsed_src = $script_src;
		if ( 0 === strpos( $parsed_src, '//' ) ) {
			$parsed_src = 'https:' . $parsed_src;
		}

		$parsed_url = wp_parse_url( $parsed_src );
		if ( ! is_array( $parsed_url ) ) {
			return null;
		}

		if ( empty( $parsed_url['scheme'] ) || 'https' !== strtolower( $parsed_url['scheme'] ) ) {
			return null;
		}

		if ( ! empty( $parsed_url['user'] ) || ! empty( $parsed_url['pass'] ) || ! empty( $parsed_url['port'] ) ) {
			return null;
		}

		$allowed_hosts = array(
			'static-login.sendpulse.com',
			'web.webformscr.com',
		);

		$host = strtolower( $parsed_url['host'] ?? '' );
		if ( '' === $host || ! in_array( $host, $allowed_hosts, true ) ) {
			return null;
		}

		$allowed_paths = array(
			'/apps/fc3/build/default-handler.js',
			'/apps/fc3/build/loader.js',
		);

		$path = $parsed_url['path'] ?? '';
		if ( ! in_array( $path, $allowed_paths, true ) ) {
			return null;
		}

		if ( isset( $parsed_url['query'] ) && '' !== $parsed_url['query'] ) {
			return null;
		}

		if ( isset( $parsed_url['fragment'] ) && '' !== $parsed_url['fragment'] ) {
			return null;
		}

		return array(
			'src'        => 'https://' . $host . $path,
			'form_id'    => $script_form_id,
			'has_async'  => $script_has_async,
			'type'       => $script_type,
		);
	}
}

if ( ! function_exists( 'sendpulse_email_marketing_newsletter_normalize_form_embed_code' ) ) {
	function sendpulse_email_marketing_newsletter_normalize_form_embed_code( $code ) {
		$parsed = sendpulse_email_marketing_newsletter_parse_form_embed_code( $code );

		if ( '' === $parsed || null === $parsed ) {
			return $parsed;
		}

		$document = new DOMDocument();
		$script   = $document->createElement( 'script' );
		$script->setAttribute( 'src', esc_url( $parsed['src'] ) );
		$script->setAttribute( 'async', 'async' );
		$script->setAttribute( 'sp-form-id', esc_attr( $parsed['form_id'] ) );
		$script->setAttribute( 'type', 'text/javascript' );
		$document->appendChild( $script );

		$normalized_html = trim( $document->saveHTML( $script ) );

		return str_replace( ' async="async"', ' async', $normalized_html );
	}
}
