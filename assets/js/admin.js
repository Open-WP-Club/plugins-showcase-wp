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

        // Test token button handler
        $('#test-token').on('click', function() {
            var $btn = $(this);
            var $status = $('#token-status');
            var token = $('#plugins_showcase_github_token').val();

            if (!token) {
                $status.text('Please enter a token first').css('color', '#dc3232');
                return;
            }

            $btn.prop('disabled', true);
            $status.text('Testing...').css('color', '#0073aa');

            $.ajax({
                url: pluginsShowcaseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'plugins_showcase_test_token',
                    nonce: pluginsShowcaseAdmin.nonce,
                    token: token
                },
                success: function(response) {
                    if (response.success) {
                        $status.text(response.data.message).css('color', '#46b450');
                    } else {
                        $status.text(response.data.message).css('color', '#dc3232');
                    }
                },
                error: function() {
                    $status.text('Network error').css('color', '#dc3232');
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
