<?php
/**
 * Gutenberg Feature Extension: Container & Column Link.
 *
 * Adds link URL, target (open in new tab), rel, and aria-label options
 * to the default Column (core/column), Columns (core/columns), and Group/Row/Stack/Grid (core/group) blocks,
 * and renders a fully clickable, accessible overlay link on the frontend.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Register custom block attributes and editor controls for layout blocks
 */
add_action('enqueue_block_editor_assets', 'metamint_column_link_register_editor_assets');
function metamint_column_link_register_editor_assets() {
    wp_register_script(
        'metamint-column-link-editor',
        false,
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-hooks', 'wp-i18n'],
        defined('METAMINT_VERSION') ? METAMINT_VERSION : '1.0.0',
        true
    );
    wp_enqueue_script('metamint-column-link-editor');

    $script = <<<JS
(function () {
    if (typeof window === 'undefined' || !window.wp) return;

    const { addFilter } = window.wp.hooks;
    const { createHigherOrderComponent } = window.wp.compose;
    const { InspectorControls } = window.wp.blockEditor;
    const { PanelBody, TextControl, ToggleControl, ExternalLink } = window.wp.components;
    const { createElement: el, Fragment } = window.wp.element;
    const { __ } = window.wp.i18n || { __: (s) => s };

    const SUPPORTED_BLOCKS = ['core/column', 'core/columns', 'core/group'];

    /**
     * Add link attributes to supported layout blocks
     */
    function addColumnLinkAttribute(settings, name) {
        if (!SUPPORTED_BLOCKS.includes(name)) {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                columnLink: {
                    type: 'string',
                    default: '',
                },
                columnLinkOpenInNewTab: {
                    type: 'boolean',
                    default: false,
                },
                columnLinkRel: {
                    type: 'string',
                    default: '',
                },
                columnLinkAriaLabel: {
                    type: 'string',
                    default: '',
                },
            },
        };
    }
    addFilter(
        'blocks.registerBlockType',
        'metamint/add-column-link-attribute',
        addColumnLinkAttribute
    );

    /**
     * Determine dynamic title for the block link panel
     */
    function getBlockPanelTitle(props) {
        if (props.name === 'core/column') {
            return __('Column Link Settings', 'metamint');
        }
        if (props.name === 'core/columns') {
            return __('Columns Link Settings', 'metamint');
        }
        if (props.name === 'core/group') {
            const layout = props.attributes?.layout;
            if (layout?.type === 'flex' && layout?.orientation === 'horizontal') {
                return __('Row Link Settings', 'metamint');
            }
            if (layout?.type === 'flex' && layout?.orientation === 'vertical') {
                return __('Stack Link Settings', 'metamint');
            }
            if (layout?.type === 'grid') {
                return __('Grid Link Settings', 'metamint');
            }
            return __('Group Link Settings', 'metamint');
        }
        return __('Link Settings', 'metamint');
    }

    /**
     * Add InspectorControls sidebar panel to supported layout blocks
     */
    const withColumnLinkControl = createHigherOrderComponent((BlockEdit) => {
        return (props) => {
            if (!SUPPORTED_BLOCKS.includes(props.name)) {
                return el(BlockEdit, props);
            }

            const { attributes, setAttributes, isSelected } = props;
            const {
                columnLink = '',
                columnLinkOpenInNewTab = false,
                columnLinkRel = '',
                columnLinkAriaLabel = '',
            } = attributes;

            const panelTitle = getBlockPanelTitle(props);

            return el(
                Fragment,
                null,
                el(BlockEdit, props),
                isSelected &&
                    el(
                        InspectorControls,
                        null,
                        el(
                            PanelBody,
                            {
                                title: panelTitle,
                                initialOpen: Boolean(columnLink),
                                icon: 'admin-links',
                            },
                            el(TextControl, {
                                label: __('Link URL', 'metamint'),
                                value: columnLink,
                                placeholder: 'https://example.com or /contact',
                                help: __(
                                    'Make this block clickable by adding a destination URL.',
                                    'metamint'
                                ),
                                onChange: (val) => setAttributes({ columnLink: val }),
                            }),
                            columnLink
                                ? el(
                                      Fragment,
                                      null,
                                      el(ToggleControl, {
                                          label: __('Open in new tab', 'metamint'),
                                          checked: Boolean(columnLinkOpenInNewTab),
                                          onChange: (val) =>
                                              setAttributes({
                                                  columnLinkOpenInNewTab: val,
                                              }),
                                      }),
                                      el(TextControl, {
                                          label: __('Link Rel (optional)', 'metamint'),
                                          value: columnLinkRel,
                                          placeholder: 'nofollow noopener',
                                          help: __(
                                              'Optional relationship attribute for SEO or security.',
                                              'metamint'
                                          ),
                                          onChange: (val) =>
                                              setAttributes({ columnLinkRel: val }),
                                      }),
                                      el(TextControl, {
                                          label: __('Aria Label (optional)', 'metamint'),
                                          value: columnLinkAriaLabel,
                                          placeholder: __(
                                              'Accessible description for screen readers',
                                              'metamint'
                                          ),
                                          onChange: (val) =>
                                              setAttributes({
                                                  columnLinkAriaLabel: val,
                                              }),
                                      }),
                                      columnLink.startsWith('http')
                                          ? el(
                                                'div',
                                                {
                                                    style: {
                                                        marginTop: '8px',
                                                        fontSize: '12px',
                                                    },
                                                },
                                                el(
                                                    ExternalLink,
                                                    { href: columnLink },
                                                    __('Test destination link', 'metamint')
                                                )
                                            )
                                          : null
                                  )
                                : null
                        )
                    )
            );
        };
    }, 'withColumnLinkControl');

    addFilter(
        'editor.BlockEdit',
        'metamint/with-column-link-control',
        withColumnLinkControl
    );

    /**
     * Add visual class in the editor when block has a link
     */
    const withColumnLinkClass = createHigherOrderComponent((BlockListBlock) => {
        return (props) => {
            if (!SUPPORTED_BLOCKS.includes(props.name)) {
                return el(BlockListBlock, props);
            }

            const { attributes } = props;
            if (attributes && attributes.columnLink) {
                return el(BlockListBlock, {
                    ...props,
                    className: ((props.className || '') + ' has-column-link has-container-link').trim(),
                });
            }

            return el(BlockListBlock, props);
        };
    }, 'withColumnLinkClass');

    addFilter(
        'editor.BlockListBlock',
        'metamint/with-column-link-class',
        withColumnLinkClass
    );
})();
JS;

    wp_add_inline_script('metamint-column-link-editor', $script);

    // Editor styling for blocks with links
    $editor_css = '
        .has-column-link,
        .has-container-link {
            position: relative;
        }
        .has-column-link.is-selected::before,
        .has-container-link.is-selected::before {
            content: "🔗 Link Active";
            position: absolute;
            top: -24px;
            right: 8px;
            background: #0f7173;
            color: #ffffff;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 4px;
            z-index: 9999;
            pointer-events: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
    ';
    wp_add_inline_style('metamint-editor-fonts', $editor_css);
}

/**
 * 2. Render frontend markup: inject accessible stretched link overlay into supported layout blocks
 */
add_filter('render_block', 'metamint_render_column_link', 10, 2);
function metamint_render_column_link($block_content, $block) {
    $supported_blocks = ['core/column', 'core/columns', 'core/group'];

    if (!in_array($block['blockName'], $supported_blocks, true) || empty($block['attrs']['columnLink'])) {
        return $block_content;
    }

    $url = esc_url($block['attrs']['columnLink']);
    if (empty($url)) {
        return $block_content;
    }

    $open_in_new_tab = !empty($block['attrs']['columnLinkOpenInNewTab']);
    $target_attr = $open_in_new_tab ? ' target="_blank"' : '';

    $rel_parts = [];
    if ($open_in_new_tab) {
        $rel_parts[] = 'noopener';
        $rel_parts[] = 'noreferrer';
    }
    if (!empty($block['attrs']['columnLinkRel'])) {
        $custom_rels = array_filter(array_map('trim', explode(' ', $block['attrs']['columnLinkRel'])));
        $rel_parts = array_unique(array_merge($rel_parts, $custom_rels));
    }
    $rel_attr = !empty($rel_parts) ? ' rel="' . esc_attr(implode(' ', $rel_parts)) . '"' : '';

    $aria_label = !empty($block['attrs']['columnLinkAriaLabel'])
        ? esc_attr($block['attrs']['columnLinkAriaLabel'])
        : '';
    $aria_attr = !empty($aria_label) ? ' aria-label="' . $aria_label . '"' : '';

    // Create the overlay anchor tag
    $link_overlay = sprintf(
        '<a href="%s" class="wp-block-column-link-overlay wp-block-container-link-overlay"%s%s%s tabindex="0"></a>',
        $url,
        $target_attr,
        $rel_attr,
        $aria_attr
    );

    // Use WP_HTML_Tag_Processor to append classes to the wrapper tag
    $tag_name = 'div';
    if (class_exists('WP_HTML_Tag_Processor')) {
        $processor = new WP_HTML_Tag_Processor($block_content);
        if ($processor->next_tag()) {
            $tag_name = strtolower($processor->get_tag());
            $processor->add_class('has-column-link');
            $processor->add_class('has-container-link');
            $block_content = $processor->get_updated_html();
        }
    }

    // Insert the overlay link immediately inside the opening tag (e.g. <div...>, <section...>)
    $block_content = preg_replace(
        '/(<' . preg_quote($tag_name, '/') . '[^>]*>)/i',
        '$1' . $link_overlay,
        $block_content,
        1
    );

    return $block_content;
}

/**
 * 3. Frontend CSS for column & container link overlay
 */
add_action('wp_enqueue_scripts', 'metamint_column_link_frontend_assets');
function metamint_column_link_frontend_assets() {
    $css = '
        .has-column-link,
        .has-container-link {
            position: relative !important;
            cursor: pointer;
        }
        .wp-block-column-link-overlay,
        .wp-block-container-link-overlay {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            display: block;
            text-decoration: none;
            background: transparent;
            border: none;
            outline: none;
            border-radius: inherit;
        }
        .wp-block-column-link-overlay:focus-visible,
        .wp-block-container-link-overlay:focus-visible {
            outline: 2px solid var(--wp--preset--color--teal, #0F7173);
            outline-offset: 2px;
        }
        /* Keep any nested links or buttons inside the container functional above the overlay */
        .has-column-link a:not(.wp-block-column-link-overlay):not(.wp-block-container-link-overlay),
        .has-container-link a:not(.wp-block-column-link-overlay):not(.wp-block-container-link-overlay),
        .has-column-link button,
        .has-container-link button,
        .has-column-link input,
        .has-container-link input,
        .has-column-link select,
        .has-container-link select,
        .has-column-link textarea,
        .has-container-link textarea {
            position: relative;
            z-index: 2;
        }
    ';
    wp_add_inline_style('metamint-main', $css);
}
