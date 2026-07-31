<?php
/**
 * Custom Error Page for SendPulse Email Marketing Newsletter.
 *
 * @package SendPulse_Email_Marketing_Newsletter
 */

// Load WordPress environment if needed.
if ( ! defined( 'ABSPATH' ) ) {
	require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
}

require_once ABSPATH . 'wp-admin/admin-header.php';

/**
 * Small text fragments used in the error message.
 *
 * Prefixed to comply with WordPressCS PrefixAllGlobals.
 */
$sendpulse_email_marketing_newsletter_storage_text    = wp_kses_post( '<b>storage</b>' );
$sendpulse_email_marketing_newsletter_permission_text = wp_kses_post( '<b>0775</b>' );
$sendpulse_email_marketing_newsletter_group_text      = wp_kses_post( '<b>www-data</b>' );
?>

<div class="wrap">
    <h1>
		<?php
		esc_html_e(
			'"SendPulse Email Marketing Newsletter" plugin could not be activated.',
			'sendpulse-email-marketing-newsletter'
		);
		?>
    </h1>

    <div class="notice notice-error">
        <p>
			<?php
			echo wp_kses(
				sprintf(
				/* translators: 1: storage directory name, 2: permissions, 3: web server group/user */
					__(
						'The plugin requires the %1$s directory to be writable (permissions %2$s, group %3$s). Please fix the file permissions and try activating the plugin again.',
						'sendpulse-email-marketing-newsletter'
					),
					$sendpulse_email_marketing_newsletter_storage_text,
					$sendpulse_email_marketing_newsletter_permission_text,
					$sendpulse_email_marketing_newsletter_group_text
				),
				array(
					'b' => array(),
				)
			);
			?>
        </p>

        <p>
			<?php
			esc_html_e(
				'You will be redirected back to the Plugins page automatically.',
				'sendpulse-email-marketing-newsletter'
			);
			?>
        </p>

        <p>
			<?php
			printf(
			/* translators: %d: number of seconds before redirect. */
				esc_html__(
					'Redirecting in %d seconds…',
					'sendpulse-email-marketing-newsletter'
				),
				5
			);
			?>
            <span id="countdown">5</span>
        </p>

        <p>
            <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Go to Plugins page now', 'sendpulse-email-marketing-newsletter' ); ?>
            </a>
        </p>
    </div>
</div>

<script>
    function countdown() {
        var countdownElement = document.getElementById('countdown');
        if (!countdownElement) {
            return;
        }

        var seconds = parseInt(countdownElement.innerHTML, 10);

        if (seconds === 0) {
            window.location.href = "<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>";
        } else {
            countdownElement.innerHTML = String(seconds - 1);
            setTimeout(countdown, 1000);
        }
    }
    countdown();
</script>

<?php require_once ABSPATH . 'wp-admin/admin-footer.php'; ?>
