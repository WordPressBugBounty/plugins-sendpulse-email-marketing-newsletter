<?php
/**
 * Plugin Name: SendPulse Email Marketing Newsletter
 * Plugin URI: https://wordpress.org/plugins/sendpulse-email-marketing-newsletter/
 * Description: Add e-mail subscription form, send marketing newsletters and create autoresponders.
 * Version: 2.2.7
 * Author: SendPulse
 * Author URI: https://sendpulse.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 8.0
 * Text Domain: sendpulse-email-marketing-newsletter
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Minimum PHP version check.
 */
if ( version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
	if ( is_admin() ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'The "SendPulse Email Marketing Newsletter" plugin requires PHP version 8.0 or higher. Please upgrade your PHP version.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'Plugin Incompatible', 'sendpulse-email-marketing-newsletter' ),
			array( 'back_link' => true )
		);
	} else {
		exit;
	}
}

require_once __DIR__ . '/vendor/autoload.php';

const SP_EMAIL_MARKETING_VERSION = '2.2.7';
define( 'SP_EMAIL_MARKETING_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'SP_EMAIL_MARKETING_PLUGIN_BASE_DIR', plugin_dir_path( __FILE__ ) );
const SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR = SP_EMAIL_MARKETING_PLUGIN_BASE_DIR . 'storage/';

include_once 'inc/class-senpulse-newsletter-requirement.php';

/**
 * Global requirement object for this plugin.
 *
 * Prefixed to comply with WordPressCS PrefixAllGlobals.
 *
 * @var Send_Pulse_Newsletter_Requirement $sendpulse_email_marketing_newsletter_requirement
 */
$sendpulse_email_marketing_newsletter_requirement = new Send_Pulse_Newsletter_Requirement();

/**
 * Deactivate a plugin by its slug.
 *
 * @param string $plugin_slug Plugin basename.
 *
 * @return void
 */
function sendpulse_email_marketing_newsletter_deactivate_plugin_by_slug( $plugin_slug ) {
	$plugins = get_option( 'active_plugins', array() );

	if ( is_array( $plugins ) ) {
		$key = array_search( $plugin_slug, $plugins, true );
		if ( false !== $key ) {
			unset( $plugins[ $key ] );
			update_option( 'active_plugins', $plugins );
		}
	}
}

register_activation_hook( __FILE__, 'sendpulse_email_marketing_newsletter_plugin_activation' );

/**
 * Create storage directory on plugin activation if it doesn't exist.
 *
 * @return void
 */
function sendpulse_email_marketing_newsletter_plugin_activation() {
	global $wp_filesystem;

	// Load the WP Filesystem.
	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( ! WP_Filesystem() ) {
		return; // Could not initialize filesystem.
	}

	if ( ! $wp_filesystem->is_dir( SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR ) ) {
		$wp_filesystem->mkdir( SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR, FS_CHMOD_DIR );
	}
}

/**
 * Remove dismissed options from wp_options on plugin deactivation.
 *
 * @return void
 */
function sendpulse_email_marketing_newsletter_plugin_deactivation() {
	delete_option( 'sp_emp_session_storage_notice_dismissed' );
	delete_option( 'sp_emp_file_storage_notice_dismissed' );
}

register_deactivation_hook( __FILE__, 'sendpulse_email_marketing_newsletter_plugin_deactivation' );

/**
 * AJAX callback to dismiss the file storage notice.
 *
 * @return void
 */
function sendpulse_email_marketing_newsletter_dismiss_file_storage_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You are not allowed to perform this action.', 'sendpulse-email-marketing-newsletter' ),
			),
			403
		);
	}

	check_ajax_referer( 'sendpulse_notice_dismiss', 'nonce' );

	update_option( 'sp_emp_file_storage_notice_dismissed', true );
	wp_die(); // This is necessary to end the AJAX request properly.
}

add_action(
	'wp_ajax_dismiss_sp_emp_file_storage_notice',
	'sendpulse_email_marketing_newsletter_dismiss_file_storage_notice'
);

/**
 * AJAX callback to dismiss the session storage notice.
 *
 * @return void
 */
function sendpulse_email_marketing_newsletter_dismiss_session_storage_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You are not allowed to perform this action.', 'sendpulse-email-marketing-newsletter' ),
			),
			403
		);
	}

	check_ajax_referer( 'sendpulse_notice_dismiss', 'nonce' );

	update_option( 'sp_emp_session_storage_notice_dismissed', true );
	wp_die(); // This is necessary to end the AJAX request properly.
}

add_action(
	'wp_ajax_dismiss_sp_emp_session_storage_notice',
	'sendpulse_email_marketing_newsletter_dismiss_session_storage_notice'
);

/**
 * Boot plugin if requirements are met, otherwise deactivate and redirect to error page.
 */
if ( $sendpulse_email_marketing_newsletter_requirement->is_success() ) {
	include_once 'inc/class-senpulse-newsletter-loader.php';

	new Send_Pulse_Newsletter_Loader(
		plugins_url( '/', __FILE__ )
	);
} else {
	sendpulse_email_marketing_newsletter_deactivate_plugin_by_slug( SP_EMAIL_MARKETING_PLUGIN_BASE_NAME );

	$sendpulse_email_marketing_newsletter_error_page_url = plugin_dir_url( __FILE__ ) . 'custom-error-page.php';

	wp_safe_redirect( $sendpulse_email_marketing_newsletter_error_page_url );
	exit;
}
