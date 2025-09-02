<?php
/*
	Plugin Name: SendPulse Email Marketing Newsletter
	Plugin URI: https://wordpress.org/plugins/sendpulse-email-marketing-newsletter/
	Description: Add e-mail subscription form, send marketing newsletters and create autoresponders.
	Version: 2.2.1
	Author: SendPulse
	Author URI: https://sendpulse.com
	License:     GPL2
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
	Text Domain: sendpulse-email-marketing-newsletter
	Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( version_compare( PHP_VERSION, '7.2.0', '<' ) ) {
	if ( is_admin() ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'The "SendPulse Email Marketing Newsletter" plugin requires PHP version 7.2.0 or higher. Please upgrade your PHP version.', 'sendpulse-email-marketing-newsletter' ),
			esc_html__( 'Plugin Incompatible', 'sendpulse-email-marketing-newsletter' ),
			array( 'back_link' => true )
		);
	} else {
		exit;
	}
}

require_once __DIR__ . '/vendor/autoload.php';

const SP_EMAIL_MARKETING_VERSION = '2.2.1';
define('SP_EMAIL_MARKETING_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('SP_EMAIL_MARKETING_PLUGIN_BASE_DIR', plugin_dir_path(__FILE__));
const SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR = SP_EMAIL_MARKETING_PLUGIN_BASE_DIR . 'storage/';

include_once( 'inc/class-senpulse-newsletter-requirement.php' );

$requirement = new Send_Pulse_Newsletter_Requirement();

// Deactivate plugin if critical error
function deactivate_plugin_by_slug( $plugin_slug ) {
	$plugins = get_option( 'active_plugins', array() );

	if ( is_array( $plugins ) ) {
		$key = array_search( $plugin_slug, $plugins );
		if ( $key !== false ) {
			unset( $plugins[ $key ] );
			update_option( 'active_plugins', $plugins );
		}
	}
}

register_activation_hook(__FILE__, 'sp_emp_plugin_activation');

// Create session folder if not exist
function sp_emp_plugin_activation() {
	global $wp_filesystem;

	// Load the WP Filesystem
	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( ! WP_Filesystem() ) {
		return; // Could not initialize filesystem
	}

	if ( ! $wp_filesystem->is_dir( SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR ) ) {
		$wp_filesystem->mkdir( SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR, FS_CHMOD_DIR );
	}
}


// Remove dissmised options from wp_options on plugin deactivation
function sp_emp_plugin_deactivation() {
	delete_option('sp_emp_session_storage_notice_dismissed');
	delete_option('sp_emp_file_storage_notice_dismissed');
}

register_deactivation_hook(__FILE__, 'sp_emp_plugin_deactivation');

// AJAX callback to dismiss the notice
function sp_emp_dismiss_file_storage_notice() {
	update_option( 'sp_emp_file_storage_notice_dismissed', true );
	wp_die(); // This is necessary to end the AJAX request properly
}

add_action( 'wp_ajax_dismiss_sp_emp_file_storage_notice', 'sp_emp_dismiss_file_storage_notice' );

// AJAX callback to dismiss the notice
function sp_emp_dismiss_session_storage_notice() {
	update_option('sp_emp_session_storage_notice_dismissed', true);
	wp_die(); // This is necessary to end the AJAX request properly
}

add_action('wp_ajax_dismiss_sp_emp_session_storage_notice', 'sp_emp_dismiss_session_storage_notice');

if ($requirement->is_success() ) {
	include_once('inc/class-senpulse-newsletter-loader.php');

	new Send_Pulse_Newsletter_Loader(
		plugins_url('/', __FILE__)
	);
} else {
	deactivate_plugin_by_slug(SP_EMAIL_MARKETING_PLUGIN_BASE_NAME);
	$url = plugin_dir_url( __FILE__ ) . 'custom-error-page.php';
	wp_safe_redirect( $url );
	exit;
}