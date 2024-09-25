<?php
/*
	Plugin Name: SendPulse Email Marketing Newsletter
	Plugin URI: https://wordpress.org/plugins/sendpulse-email-marketing-newsletter/
	Description: Add e-mail subscription form, send marketing newsletters and create autoresponders.
	Version: 2.1.5
	Author: SendPulse
	Author URI: https://sendpulse.com
	License:     GPL2
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
	Text Domain: sendpulse-email-marketing-newsletter
	Domain Path: languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const SP_EMAIL_MARKETING_VERSION = '2.1.4';
define('SP_EMAIL_MARKETING_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('SP_EMAIL_MARKETING_PLUGIN_BASE_DIR', plugin_dir_path(__FILE__));
const SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR = SP_EMAIL_MARKETING_PLUGIN_BASE_DIR . 'storage/';

include_once( 'inc/class-senpulse-newsletter-requirement.php' );

$requirement = new Send_Pulse_Newsletter_Requirement();

// Deactivate plugin if critical error
function deactivate_plugin_by_slug($plugin_slug) {
	global $wpdb;

	$plugin = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->options WHERE option_name = %s", 'active_plugins'));

	if ($plugin && isset($plugin->option_value)) {
		$plugins = maybe_unserialize($plugin->option_value);
		if (is_array($plugins)) {
			$key = array_search($plugin_slug, $plugins);
			if ($key !== false) {
				unset($plugins[$key]);
				$wpdb->query($wpdb->prepare("UPDATE $wpdb->options SET option_value = %s WHERE option_name = %s", serialize($plugins), 'active_plugins'));
			}
		}
	}
}

register_activation_hook(__FILE__, 'sp_emp_plugin_activation');

// Create session folder if not exist
function sp_emp_plugin_activation() {

	if (!is_dir(SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR)) {
		mkdir(SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR, 0775);
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

// Enqueue JavaScript for dismiss button
function sp_emp_enqueue_dismiss_script() {
	wp_enqueue_script('sp-emp-dismiss-script', plugin_dir_url(__FILE__) . 'assets/js/dismiss-script.js', array('jquery'), '1.0', true);

	// Localize the script with the AJAX URL
	wp_localize_script('sp-emp-dismiss-script', 'sp_emp_dismiss_script_vars', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
	));
}

add_action('admin_enqueue_scripts', 'sp_emp_enqueue_dismiss_script');


if ($requirement->is_success() ) {
	include_once('inc/class-senpulse-newsletter-loader.php');

	new Send_Pulse_Newsletter_Loader(
		plugins_url('/', __FILE__),
		basename(dirname(__FILE__)) . '/languages/'
	);
} else {
	deactivate_plugin_by_slug(SP_EMAIL_MARKETING_PLUGIN_BASE_NAME);
	$url = plugin_dir_url( __FILE__ ) . 'custom-error-page.php';
	wp_safe_redirect( $url );
	exit;
}