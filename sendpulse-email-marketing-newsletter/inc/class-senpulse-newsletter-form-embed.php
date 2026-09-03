<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize and validate SendPulse form embed code.
 */
if ( ! function_exists( 'sendpulse_email_marketing_newsletter_validate_script_node' ) ) {
	function sendpulse_email_marketing_newsletter_validate_script_node( $script_node, $allowed_paths, $allow_numeric_query, $require_form_id ) {
		if ( ! ( $script_node instanceof DOMElement ) || '' !== trim( $script_node->textContent ) ) {
			return false;
		}

		$script_src     = '';
		$script_form_id = '';
		$script_has_async = false;
		$script_type    = '';

		foreach ( $script_node->attributes as $attribute ) {
			$attribute_name  = strtolower( $attribute->name );
			$attribute_value = trim( (string) $attribute->value );

			if ( ! in_array( $attribute_name, array( 'async', 'sp-form-id', 'src', 'type' ), true ) ) {
				return false;
			}

			switch ( $attribute_name ) {
				case 'async':
					if ( '' !== $attribute_value && 'async' !== strtolower( $attribute_value ) ) {
						return false;
					}
					$script_has_async = true;
					break;

				case 'sp-form-id':
					if ( '' === $attribute_value ) {
						return false;
					}
					$script_form_id = $attribute_value;
					break;

				case 'src':
					$script_src = $attribute_value;
					break;

				case 'type':
					if ( '' !== $attribute_value && 'text/javascript' !== strtolower( $attribute_value ) ) {
						return false;
					}
					$script_type = 'text/javascript';
					break;
			}
		}

		$script_src = html_entity_decode( trim( (string) $script_src ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		if ( '' === $script_src ) {
			return false;
		}

		if ( 0 === strpos( $script_src, '//' ) ) {
			$script_src = 'https:' . $script_src;
		}

		$parsed_url = wp_parse_url( $script_src );
		if ( ! is_array( $parsed_url ) || empty( $parsed_url['scheme'] ) || 'https' !== strtolower( $parsed_url['scheme'] ) ) {
			return false;
		}

		if ( ! empty( $parsed_url['user'] ) || ! empty( $parsed_url['pass'] ) || ! empty( $parsed_url['port'] ) ) {
			return false;
		}

		$host = strtolower( $parsed_url['host'] ?? '' );
		if ( ! in_array( $host, array( 'static-login.sendpulse.com', 'web.webformscr.com' ), true ) ) {
			return false;
		}

		$path = $parsed_url['path'] ?? '';
		if ( ! in_array( $path, $allowed_paths, true ) ) {
			return false;
		}

		if ( isset( $parsed_url['query'] ) && '' !== $parsed_url['query'] && ( ! $allow_numeric_query || ! preg_match( '/^\d+$/', $parsed_url['query'] ) ) ) {
			return false;
		}

		if ( isset( $parsed_url['fragment'] ) && '' !== $parsed_url['fragment'] ) {
			return false;
		}

		if ( $require_form_id && '' === $script_form_id ) {
			return false;
		}

		return array(
			'src'       => 'https://' . $host . $path,
			'form_id'   => $script_form_id,
			'has_async' => $script_has_async,
			'type'      => $script_type,
		);
	}
}

if ( ! function_exists( 'sendpulse_email_marketing_newsletter_parse_legacy_form_embed_code' ) ) {
	function sendpulse_email_marketing_newsletter_parse_legacy_form_embed_code( $code ) {
		if ( ! is_string( $code ) || false === strpos( $code, 'default-handler.js' ) ) {
			return null;
		}

		$sanitized_code = wp_kses(
			$code,
			array(
				'style'  => array(),
				'div'    => array(
					'class'           => true,
					'id'              => true,
					'style'           => true,
					'sp-id'           => true,
					'sp-hash'         => true,
					'sp-lang'         => true,
					'sp-show-options' => true,
				),
				'form'   => array(
					'class'     => true,
					'novalidate' => true,
					'style'     => true,
				),
				'button' => array(
					'class' => true,
					'id'    => true,
					'style' => true,
					'type'  => true,
				),
				'p'      => array( 'class' => true, 'style' => true ),
				'span'   => array( 'class' => true, 'style' => true, 'translate' => true ),
				'strong' => array( 'class' => true, 'style' => true ),
				'label'  => array( 'class' => true, 'style' => true ),
				'input'  => array(
					'autocomplete' => true,
					'class'        => true,
					'name'         => true,
					'placeholder'  => true,
					'required'     => true,
					'style'        => true,
					'sp-tips'      => true,
					'sp-type'      => true,
					'type'         => true,
				),
				'a'      => array( 'class' => true, 'href' => true, 'style' => true, 'target' => true ),
				'script' => array( 'async' => true, 'src' => true, 'type' => true ),
			),
			array( 'http', 'https' )
		);

		$sanitized_code = trim( $sanitized_code );
		if ( '' === $sanitized_code ) {
			return null;
		}

		$previous_use_internal_errors = libxml_use_internal_errors( true );
		$dom                          = new DOMDocument();
		$loaded                       = $dom->loadHTML( $sanitized_code, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_use_internal_errors );

		if ( ! $loaded ) {
			return null;
		}

		$script_nodes = $dom->getElementsByTagName( 'script' );
		if ( 1 !== $script_nodes->length ) {
			return null;
		}

		if ( false === sendpulse_email_marketing_newsletter_validate_script_node(
			$script_nodes->item( 0 ),
			array( '/apps/fc3/build/default-handler.js' ),
			true,
			false
		) ) {
			return null;
		}

		foreach ( $dom->getElementsByTagName( 'style' ) as $style_node ) {
			if ( preg_match( '/(?:expression\s*\(|javascript\s*:|vbscript\s*:|behavior\s*:|-moz-binding\s*:|@import)/i', $style_node->textContent ) ) {
				return null;
			}
		}

		return array(
			'normalized_html' => $sanitized_code,
		);
	}
}

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

		if ( false !== strpos( $code, 'default-handler.js' ) && (
			false !== strpos( $code, '<style' ) ||
			false !== strpos( $code, 'sp-id=' ) ||
			false !== strpos( $code, 'sp-show-options=' ) ||
			false !== strpos( $code, 'sp-tips=' ) ||
			false !== strpos( $code, 'sp-type=' )
		) ) {
			$legacy_parsed = sendpulse_email_marketing_newsletter_parse_legacy_form_embed_code( $code );
			if ( is_array( $legacy_parsed ) ) {
				return $legacy_parsed;
			}
		}

		$previous_use_internal_errors = libxml_use_internal_errors( true );
		$dom                          = new DOMDocument();
		$loaded                       = $dom->loadHTML( $code, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
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

		$parsed = sendpulse_email_marketing_newsletter_validate_script_node(
			$script_node,
			array( '/apps/fc3/build/default-handler.js', '/apps/fc3/build/loader.js' ),
			false,
			true
		);
		if ( false === $parsed ) {
			return null;
		}

		return $parsed;
	}
}

if ( ! function_exists( 'sendpulse_email_marketing_newsletter_normalize_form_embed_code' ) ) {
	function sendpulse_email_marketing_newsletter_normalize_form_embed_code( $code ) {
		$parsed = sendpulse_email_marketing_newsletter_parse_form_embed_code( $code );

		if ( '' === $parsed || null === $parsed ) {
			return $parsed;
		}

		if ( isset( $parsed['normalized_html'] ) ) {
			return $parsed['normalized_html'];
		}

		$document = new DOMDocument();
		$script   = $document->createElement( 'script' );
		$script->setAttribute( 'src', esc_url( $parsed['src'] ) );
		$script->setAttribute( 'async', 'async' );
		$script->setAttribute( 'sp-form-id', esc_attr( $parsed['form_id'] ) );
		$script->setAttribute( 'type', 'text/javascript' );
		$document->appendChild( $script );

		return str_replace( ' async="async"', ' async', trim( $document->saveHTML( $script ) ) );
	}
}
