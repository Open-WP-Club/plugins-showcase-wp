(function($) {
    'use strict';

    $(document).ready(function() {
        var $syncBtn = $('#sync-now');
        var $deleteBtn = $('#delete-all');
        var $status = $('#sync-status');
        var $results = $('#sync-results');
        var strings = pluginsShowcaseAdmin.strings;

        // Sync button handler
        $syncBtn.on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true);
            $status.text(strings.syncing).addClass('syncing');
            $results.hide();

            $.ajax({
                url: pluginsShowcaseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'plugins_showcase_sync',
                    nonce: pluginsShowcaseAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.text(strings.syncComplete).removeClass('syncing').addClass('success');
                        $('#synced-count').text(response.data.synced);
                        $('#failed-count').text(response.data.failed);
                        $('#skipped-count').text(response.data.skipped);
                        $('#total-count').text(response.data.total);
                        $results.show();

                        setTimeout(function() {
                            $status.removeClass('success');
                        }, 3000);
                    } else {
                        $status.text(strings.syncError + ' ' + response.data.message).removeClass('syncing').addClass('error');
                    }
                },
                error: function() {
                    $status.text(strings.syncError + ' Network error').removeClass('syncing').addClass('error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Delete all button handler
        $deleteBtn.on('click', function() {
            if (!confirm(strings.confirm)) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true);
            $status.text('Deleting...').addClass('syncing');

            $.ajax({
                url: pluginsShowcaseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'plugins_showcase_delete_all',
                    nonce: pluginsShowcaseAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.text('Deleted ' + response.data.count + ' plugins').removeClass('syncing').addClass('success');
                        $results.hide();
                    } else {
                        $status.text('Error: ' + response.data.message).removeClass('syncing').addClass('error');
                    }
                },
                error: function() {
                    $status.text('Network error').removeClass('syncing').addClass('error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
