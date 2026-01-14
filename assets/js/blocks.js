(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, RangeControl, ToggleControl, SelectControl, Placeholder } = wp.components;
    const { __ } = wp.i18n;

    // Plugins Grid Block
    registerBlockType('plugins-showcase/plugins-grid', {
        title: __('Plugins Grid', 'plugins-showcase'),
        description: __('Display a grid of plugins with search and filters.', 'plugins-showcase'),
        icon: 'grid-view',
        category: 'widgets',
        attributes: {
            columns: { type: 'number', default: 3 },
            perPage: { type: 'number', default: 12 },
            showSearch: { type: 'boolean', default: true },
            showFilters: { type: 'boolean', default: true },
            category: { type: 'string', default: '' },
            showStars: { type: 'boolean', default: true },
            showLanguage: { type: 'boolean', default: true }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const categories = pluginsShowcaseBlocks.categories || [];

            const categoryOptions = [
                { value: '', label: __('All Categories', 'plugins-showcase') }
            ];

            categories.forEach(function(cat) {
                categoryOptions.push({
                    value: cat.slug,
                    label: cat.name
                });
            });

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Grid Settings', 'plugins-showcase') },
                        el(RangeControl, {
                            label: __('Columns', 'plugins-showcase'),
                            value: attributes.columns,
                            onChange: function(value) { setAttributes({ columns: value }); },
                            min: 1,
                            max: 4
                        }),
                        el(RangeControl, {
                            label: __('Plugins per Page', 'plugins-showcase'),
                            value: attributes.perPage,
                            onChange: function(value) { setAttributes({ perPage: value }); },
                            min: 3,
                            max: 24
                        }),
                        el(SelectControl, {
                            label: __('Category Filter', 'plugins-showcase'),
                            value: attributes.category,
                            options: categoryOptions,
                            onChange: function(value) { setAttributes({ category: value }); }
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Display Options', 'plugins-showcase') },
                        el(ToggleControl, {
                            label: __('Show Search', 'plugins-showcase'),
                            checked: attributes.showSearch,
                            onChange: function(value) { setAttributes({ showSearch: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Category Filter', 'plugins-showcase'),
                            checked: attributes.showFilters,
                            onChange: function(value) { setAttributes({ showFilters: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Stars', 'plugins-showcase'),
                            checked: attributes.showStars,
                            onChange: function(value) { setAttributes({ showStars: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Language', 'plugins-showcase'),
                            checked: attributes.showLanguage,
                            onChange: function(value) { setAttributes({ showLanguage: value }); }
                        })
                    )
                ),
                el(
                    'div',
                    { className: 'plugins-showcase-block-preview' },
                    el(
                        Placeholder,
                        {
                            icon: 'grid-view',
                            label: __('Plugins Grid', 'plugins-showcase'),
                            instructions: __('Displays a grid of plugins from your GitHub organization.', 'plugins-showcase')
                        },
                        el(
                            'div',
                            { className: 'plugins-showcase-preview-info' },
                            el('p', null,
                                __('Columns:', 'plugins-showcase') + ' ' + attributes.columns + ' | ' +
                                __('Per Page:', 'plugins-showcase') + ' ' + attributes.perPage
                            ),
                            attributes.showSearch && el('span', { className: 'plugins-showcase-badge' }, __('Search', 'plugins-showcase')),
                            attributes.showFilters && el('span', { className: 'plugins-showcase-badge' }, __('Filters', 'plugins-showcase'))
                        )
                    )
                )
            );
        },

        save: function() {
            return null; // Server-side render
        }
    });

    // Single Plugin Block
    registerBlockType('plugins-showcase/single-plugin', {
        title: __('Single Plugin', 'plugins-showcase'),
        description: __('Display a single plugin with its README.', 'plugins-showcase'),
        icon: 'admin-plugins',
        category: 'widgets',
        attributes: {
            pluginId: { type: 'number', default: 0 },
            showReadme: { type: 'boolean', default: true },
            showMeta: { type: 'boolean', default: true }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const plugins = pluginsShowcaseBlocks.plugins || [];

            const pluginOptions = [
                { value: 0, label: __('Select a plugin...', 'plugins-showcase') }
            ];

            plugins.forEach(function(plugin) {
                pluginOptions.push({
                    value: plugin.id,
                    label: plugin.title
                });
            });

            const selectedPlugin = plugins.find(function(p) {
                return p.id === attributes.pluginId;
            });

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Plugin Settings', 'plugins-showcase') },
                        el(SelectControl, {
                            label: __('Select Plugin', 'plugins-showcase'),
                            value: attributes.pluginId,
                            options: pluginOptions,
                            onChange: function(value) { setAttributes({ pluginId: parseInt(value, 10) }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show README Content', 'plugins-showcase'),
                            checked: attributes.showReadme,
                            onChange: function(value) { setAttributes({ showReadme: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Meta Information', 'plugins-showcase'),
                            checked: attributes.showMeta,
                            onChange: function(value) { setAttributes({ showMeta: value }); }
                        })
                    )
                ),
                el(
                    'div',
                    { className: 'plugins-showcase-block-preview' },
                    el(
                        Placeholder,
                        {
                            icon: 'admin-plugins',
                            label: __('Single Plugin', 'plugins-showcase'),
                            instructions: selectedPlugin
                                ? __('Displaying:', 'plugins-showcase') + ' ' + selectedPlugin.title
                                : __('Select a plugin from the sidebar.', 'plugins-showcase')
                        },
                        !attributes.pluginId && el(
                            SelectControl,
                            {
                                value: attributes.pluginId,
                                options: pluginOptions,
                                onChange: function(value) { setAttributes({ pluginId: parseInt(value, 10) }); }
                            }
                        )
                    )
                )
            );
        },

        save: function() {
            return null; // Server-side render
        }
    });

})(window.wp);
