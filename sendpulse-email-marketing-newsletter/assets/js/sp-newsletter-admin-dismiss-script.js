jQuery(document).ready(function($) {
    $(document).on('click', '.notice-dismiss', function() {
        var $notice = $(this).closest('.notice');
        var dismissAction = $notice.data('dismiss-action');

        if (!dismissAction) {
            return;
        }

        $.ajax({
            url: sp_emp_dismiss_script_vars.ajaxurl,
            type: 'POST',
            data: {
                action: dismissAction,
                nonce: sp_emp_dismiss_script_vars.nonce
            },
            success: function() {
                $notice.fadeOut();
            }
        });
    });
});
