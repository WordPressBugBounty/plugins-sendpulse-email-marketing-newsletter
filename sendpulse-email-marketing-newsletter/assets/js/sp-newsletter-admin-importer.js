(function ($) {
    $(function () {
        'use strict';

        const $body = $(document.body);
        const $controls = $('.sp-import-controls');
        const $log = $('#sp-import-log');
        const nonce = sp_admin_params._ajax_nonce;
        let defaultBook = $('#sp-book').val();
        let defaultRole = $('#sp-role').val();
        let pollInterval;

        const fetchLog = function () {
            $.post(sp_admin_params.ajax_url, {
                action: 'sendpulse_get_import_log',
                _ajax_nonce: nonce
            }, function (response) {
                if (response.success) {
                    const log = response.data.log;
                    $log.val(log);
                    $log.scrollTop($log[0].scrollHeight);

                    // If import is finished, stop polling
                    if (log.includes('Import finished')) {
                        clearInterval(pollInterval);
                        $controls.removeClass('loading');
                    }
                }
            });
        };

        const startImport = function (e) {
            e.preventDefault();

            const book = $('#sp-book').val();
            const role = $('#sp-role').val();

            if (!book || !role) {
                $log.val('Please select both address book and user role.');
                return;
            }

            //$log.val('Starting import...\n').show();
            $log.val('Starting import...\n');
            $log.stop(true, true).fadeIn(200);

            $controls.addClass('loading');

            // Clear previous log transient
            $.post(sp_admin_params.ajax_url, {
                action: 'sendpulse_get_import_log',
                _ajax_nonce: nonce
            });

            // Start polling
            pollInterval = setInterval(fetchLog, 1500);

            // Kick off import
            $.post(sp_admin_params.ajax_url, {
                action: 'sendpulse_import',
                _ajax_nonce: nonce,
                book: book,
                role: role
            }).fail(function () {
                clearInterval(pollInterval);
                $controls.removeClass('loading');
                $log.val('AJAX error during import.');
            });
        };

        const init = function () {
            $body.on('click', '#sp-import', startImport);
        };

        init();
    });
})(jQuery);
