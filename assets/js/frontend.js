(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initPluginsShowcase();
    });

    function initPluginsShowcase() {
        var wrappers = document.querySelectorAll('.plugins-showcase-grid-wrapper');

        wrappers.forEach(function(wrapper) {
            var searchInput = wrapper.querySelector('.plugins-showcase-search-input');
            var categorySelect = wrapper.querySelector('.plugins-showcase-category-select');
            var grid = wrapper.querySelector('.plugins-showcase-grid');
            var loadMoreBtn = wrapper.querySelector('.plugins-showcase-load-more');
            var perPage = parseInt(wrapper.dataset.perPage, 10) || 12;
            var columns = parseInt(wrapper.dataset.columns, 10) || 3;
            var currentPage = 1;
            var searchTimeout = null;

            // Search handler with debounce
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        currentPage = 1;
                        fetchPlugins(true);
                    }, 300);
                });
            }

            // Category filter handler
            if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    currentPage = 1;
                    fetchPlugins(true);
                });
            }

            // Load more handler
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    currentPage++;
                    fetchPlugins(false);
                });
            }

            function fetchPlugins(replace) {
                var search = searchInput ? searchInput.value : '';
                var category = categorySelect ? categorySelect.value : '';

                var url = new URL(pluginsShowcase.restUrl + 'plugins');
                url.searchParams.append('search', search);
                url.searchParams.append('category', category);
                url.searchParams.append('per_page', perPage);
                url.searchParams.append('page', currentPage);

                if (replace) {
                    grid.classList.add('loading');
                }

                fetch(url.toString())
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (replace) {
                        grid.innerHTML = '';
                    }

                    if (data.plugins && data.plugins.length > 0) {
                        data.plugins.forEach(function(plugin) {
                            var card = createPluginCard(plugin);
                            grid.appendChild(card);
                        });

                        // Update load more button
                        if (loadMoreBtn) {
                            if (currentPage >= data.total_pages) {
                                loadMoreBtn.style.display = 'none';
                            } else {
                                loadMoreBtn.style.display = 'inline-block';
                                loadMoreBtn.dataset.maxPages = data.total_pages;
                            }
                        }
                    } else if (replace) {
                        grid.innerHTML = '<p class="plugins-showcase-no-results">No plugins found.</p>';
                        if (loadMoreBtn) {
                            loadMoreBtn.style.display = 'none';
                        }
                    }
                })
                .catch(function(error) {
                    console.error('Error fetching plugins:', error);
                })
                .finally(function() {
                    grid.classList.remove('loading');
                });
            }

            function createPluginCard(plugin) {
                var article = document.createElement('article');
                article.className = 'plugins-showcase-card';

                var html = '<a href="' + escapeHtml(plugin.permalink) + '" class="plugins-showcase-card-link">';

                if (plugin.thumbnail) {
                    html += '<div class="plugins-showcase-card-thumbnail">';
                    html += '<img src="' + escapeHtml(plugin.thumbnail) + '" alt="' + escapeHtml(plugin.title) + '">';
                    html += '</div>';
                }

                html += '<div class="plugins-showcase-card-content">';
                html += '<h3 class="plugins-showcase-card-title">' + escapeHtml(plugin.title) + '</h3>';

                if (plugin.excerpt) {
                    html += '<p class="plugins-showcase-card-excerpt">' + escapeHtml(plugin.excerpt) + '</p>';
                }

                html += '<div class="plugins-showcase-card-meta">';

                if (plugin.stars) {
                    html += '<span class="plugins-showcase-stars">';
                    html += '<svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">';
                    html += '<path d="M8 .25a.75.75 0 01.673.418l1.882 3.815 4.21.612a.75.75 0 01.416 1.279l-3.046 2.97.719 4.192a.75.75 0 01-1.088.791L8 12.347l-3.766 1.98a.75.75 0 01-1.088-.79l.72-4.194L.818 6.374a.75.75 0 01.416-1.28l4.21-.611L7.327.668A.75.75 0 018 .25z"></path>';
                    html += '</svg> ' + plugin.stars;
                    html += '</span>';
                }

                if (plugin.language) {
                    html += '<span class="plugins-showcase-language">';
                    html += '<span class="plugins-showcase-language-color" data-language="' + escapeHtml(plugin.language.toLowerCase()) + '"></span>';
                    html += escapeHtml(plugin.language);
                    html += '</span>';
                }

                html += '</div></div></a>';

                if (plugin.github_url) {
                    html += '<a href="' + escapeHtml(plugin.github_url) + '" class="plugins-showcase-github-link" target="_blank" rel="noopener noreferrer">';
                    html += '<svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor">';
                    html += '<path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path>';
                    html += '</svg></a>';
                }

                article.innerHTML = html;
                return article;
            }

            function escapeHtml(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    }

})();
