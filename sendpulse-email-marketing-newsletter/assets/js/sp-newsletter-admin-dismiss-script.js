jQuery(document).ready(function($) {
    // Dismiss the notice when the close button is clicked
    $('.notice-dismiss').on('click', function() {
        // AJAX request to dismiss the notice
        $.ajax({
            url: sp_emp_dismiss_script_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'dismiss_sp_emp_file_storage_notice'
            },
            success: function() {
                // Hide the notice
                $('.notice').fadeOut();
            }
        });
    });
});
