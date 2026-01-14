(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var syncBtn = document.getElementById('sync-now');
        var deleteBtn = document.getElementById('delete-all');
        var progress = document.getElementById('sync-progress');
        var progressFill = document.querySelector('.ps-progress-fill');
        var progressText = document.querySelector('.ps-progress-text');
        var results = document.getElementById('sync-results');
        var strings = pluginsShowcaseAdmin.strings;

        // Helper: AJAX request
        function ajax(action, data, callback) {
            var formData = new FormData();
            formData.append('action', action);
            formData.append('nonce', pluginsShowcaseAdmin.nonce);

            if (data) {
                Object.keys(data).forEach(function(key) {
                    formData.append(key, data[key]);
                });
            }

            fetch(pluginsShowcaseAdmin.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(response) { callback(null, response); })
            .catch(function(error) { callback(error, null); });
        }

        // Sync button
        if (syncBtn) {
            syncBtn.addEventListener('click', function() {
                syncBtn.disabled = true;
                syncBtn.classList.add('loading');
                if (deleteBtn) deleteBtn.disabled = true;

                if (results) results.style.display = 'none';
                if (progress) progress.style.display = 'block';
                if (progressFill) progressFill.style.width = '10%';

                ajax('plugins_showcase_sync', null, function(err, response) {
                    if (progressFill) progressFill.style.width = '100%';

                    if (!err && response && response.success) {
                        if (progressText) progressText.textContent = strings.syncComplete;

                        var syncedCount = document.getElementById('synced-count');
                        var failedCount = document.getElementById('failed-count');
                        var skippedCount = document.getElementById('skipped-count');
                        var totalCount = document.getElementById('total-count');

                        if (syncedCount) syncedCount.textContent = response.data.synced;
                        if (failedCount) failedCount.textContent = response.data.failed;
                        if (skippedCount) skippedCount.textContent = response.data.skipped;
                        if (totalCount) totalCount.textContent = response.data.total;

                        setTimeout(function() {
                            if (progress) progress.style.display = 'none';
                            if (results) results.style.display = 'flex';
                        }, 500);
                    } else {
                        var msg = (response && response.data && response.data.message) || 'Unknown error';
                        if (progressText) progressText.textContent = strings.syncError + ' ' + msg;
                    }

                    syncBtn.disabled = false;
                    syncBtn.classList.remove('loading');
                    if (deleteBtn) deleteBtn.disabled = false;
                });
            });
        }

        // Delete all button
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (!confirm(strings.confirm)) return;

                deleteBtn.disabled = true;
                deleteBtn.classList.add('loading');
                if (syncBtn) syncBtn.disabled = true;

                ajax('plugins_showcase_delete_all', null, function(err, response) {
                    if (!err && response && response.success) {
                        location.reload();
                    } else {
                        var msg = (response && response.data && response.data.message) || 'Unknown error';
                        alert('Error: ' + msg);
                        deleteBtn.disabled = false;
                        deleteBtn.classList.remove('loading');
                        if (syncBtn) syncBtn.disabled = false;
                    }
                });
            });
        }

        // Test token
        var testTokenBtn = document.getElementById('test-token');
        if (testTokenBtn) {
            testTokenBtn.addEventListener('click', function() {
                var status = document.getElementById('token-status');
                var tokenInput = document.getElementById('plugins_showcase_github_token');
                var token = tokenInput ? tokenInput.value : '';

                if (!token) {
                    if (status) {
                        status.textContent = 'Enter token first';
                        status.className = 'error';
                    }
                    return;
                }

                testTokenBtn.disabled = true;
                if (status) {
                    status.textContent = 'Testing...';
                    status.className = '';
                }

                ajax('plugins_showcase_test_token', { token: token }, function(err, response) {
                    if (!err && response && response.success) {
                        if (status) {
                            status.textContent = '✓ Valid';
                            status.className = 'success';
                        }
                    } else {
                        var msg = (response && response.data && response.data.message) || 'Network error';
                        if (status) {
                            status.textContent = '✗ ' + msg;
                            status.className = 'error';
                        }
                    }
                    testTokenBtn.disabled = false;
                });
            });
        }

        // Refresh rate limit
        var refreshBtn = document.getElementById('refresh-rate-limit');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                refreshBtn.textContent = '...';

                ajax('plugins_showcase_get_rate_limit', null, function(err, response) {
                    if (!err && response && response.success) {
                        location.reload();
                    } else {
                        refreshBtn.textContent = 'Refresh';
                    }
                });
            });
        }
    });
})();
