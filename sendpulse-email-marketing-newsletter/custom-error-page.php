<?php
/**
 * Template Name: Custom Error Page
 */

// Load WordPress environment
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

// Get the admin header
require_once(ABSPATH . 'wp-admin/admin-header.php');

$storage_text = '<b>storage</b>';
$permission_text = '<b>0775</b>';
$group_text = '<b>www-data</b>';

?>
    <div class="wrap">
        <h1><?php _e('"SendPulse Email Marketing Newsletter" Plugin cannot be activated.', 'sendpulse-email-marketing-newsletter'); ?></h1>
        <h4><?php _e('Something wrong with your hosting setup. Please, ask hosting team to check for correct permissions for WordPress folders, owner and group.', 'sendpulse-email-marketing-newsletter'); ?></h4>
        <h4><?php _e('Also check for error messages at server log files.', 'sendpulse-email-marketing-newsletter'); ?></h4>

        <p>
			<?php echo sprintf(__('Please make sure the "sendpulse-email-marketing-newsletter/%s", folder is writable, has the correct permissions %s and group set to %s .', 'sendpulse-email-marketing-newsletter'), $storage_text, $permission_text, $group_text); ?><br>
			<?php _e('If you cannot change permissions on your own, ask your hosting company for help.', 'sendpulse-email-marketing-newsletter'); ?>
        </p>
        <p><?php _e('"SendPulse Email Marketing Newsletter" Plugin will be deactivated to prevent your site from crashing.', 'sendpulse-email-marketing-newsletter'); ?></p>
        <h4>
			<?php _e('You will be redirected in ', 'sendpulse-email-marketing-newsletter'); ?>
            <span id="countdown" style="color: red">6</span> <?php _e('seconds back to your plugins page.', 'sendpulse-email-marketing-newsletter'); ?>
        </h4>
    </div>
    <script>
        // Countdown function
        function countdown() {
            var seconds = document.getElementById('countdown').innerHTML;
            seconds = parseInt(seconds, 10);

            if (seconds == 0) {
                // Redirect to plugins page
                window.location.href = "<?php echo esc_url(admin_url('plugins.php')); ?>";
            } else {
                seconds--;
                document.getElementById('countdown').innerHTML = seconds;
                setTimeout(countdown, 1000); // Call countdown function after 1 second
            }
        }

        // Start the countdown
        countdown();
    </script>

<?php
// Get the admin footer
require_once(ABSPATH . 'wp-admin/admin-footer.php');