<?php
/**
 * Template Name: Custom Error Page
 */

// Load WordPress environment
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');
require_once(ABSPATH . 'wp-admin/admin-header.php');

$storage_text    = wp_kses_post('<b>storage</b>');
$permission_text = wp_kses_post('<b>0775</b>');
$group_text      = wp_kses_post('<b>www-data</b>');
?>

    <div class="wrap">
        <h1><?php esc_html_e('"SendPulse Email Marketing Newsletter" Plugin cannot be activated.', 'sendpulse-email-marketing-newsletter'); ?></h1>
        <h4><?php esc_html_e('Something wrong with your hosting setup. Please, ask hosting team to check for correct permissions for WordPress folders, owner and group.', 'sendpulse-email-marketing-newsletter'); ?></h4>
        <h4><?php esc_html_e('Also check for error messages at server log files.', 'sendpulse-email-marketing-newsletter'); ?></h4>

        <p>
			<?php
			// translators: 1. Folder path, 2. Permissions value, 3. Group name.
			echo wp_kses_post( sprintf(__('Please make sure the "sendpulse-email-marketing-newsletter/%1$s", folder is writable, has the correct permissions %2$s and group set to %3$s.', 'sendpulse-email-marketing-newsletter'),
				$storage_text,
				$permission_text,
				$group_text
			) );
			?>
            <br>
			<?php esc_html_e('If you cannot change permissions on your own, ask your hosting company for help.', 'sendpulse-email-marketing-newsletter'); ?>
        </p>
        <p><?php esc_html_e('"SendPulse Email Marketing Newsletter" Plugin will be deactivated to prevent your site from crashing.', 'sendpulse-email-marketing-newsletter'); ?></p>
        <h4>
			<?php esc_html_e('You will be redirected in ', 'sendpulse-email-marketing-newsletter'); ?>
            <span id="countdown" style="color: red">6</span>
			<?php esc_html_e('seconds back to your plugins page.', 'sendpulse-email-marketing-newsletter'); ?>
        </h4>
    </div>

    <script>
        function countdown() {
            var seconds = parseInt(document.getElementById('countdown').innerHTML, 10);

            if (seconds === 0) {
                window.location.href = "<?php echo esc_url(admin_url('plugins.php')); ?>";
            } else {
                document.getElementById('countdown').innerHTML = seconds - 1;
                setTimeout(countdown, 1000);
            }
        }
        countdown();
    </script>

<?php require_once(ABSPATH . 'wp-admin/admin-footer.php'); ?>