/**
 * Ajax subscribe and display result.
 */
(function ($) {
    $(function () {
        'use strict';
        var $body = $(document.body),
            $controls = $('.sp-import-controls'),
            $log = $('#sp-import-log'),
            init = function () {
                $body
                    .on('click', '#sp-import', startImport);


            },
            startImport = function (e) {

                var data,
                    value = {
                        book: $('#sp_import_setting\\[import_to_book\\]').val(),
                        role: $('#sp_import_setting\\[import_users_group\\]').val()
                    };

                e.preventDefault();

                $controls.addClass('loading');

                data = $.extend({}, $(this).data(), value);

                $.post(sp_admin_params.ajax_url, data, function (response) {

                    var data = response.data;

                    $controls
                        .removeClass('loading');

                    $log
                        .text(data.msg)
                        .show();

                });

            };

        init();
    });
})(jQuery);
