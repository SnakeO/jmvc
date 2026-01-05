/**
 * JMVC Blocks Editor Script
 *
 * Registers JMVC-powered blocks in the Gutenberg editor
 * using server-side rendering.
 *
 * @package JMVC
 */
(function (wp) {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const {
        PanelBody,
        TextControl,
        SelectControl,
        ToggleControl,
        RangeControl,
        Placeholder,
        Spinner
    } = wp.components;
    const { createElement: el, Fragment, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const ServerSideRender = wp.serverSideRender;

    // Get block configurations from localized data
    const blocksConfig = window.jmvcBlocks || {};
    const blocks = blocksConfig.blocks || {};
    const i18n = blocksConfig.i18n || {};

    /**
     * Generate control component based on attribute type
     *
     * @param {string} key Attribute key
     * @param {Object} attr Attribute configuration
     * @param {Object} attributes Current attribute values
     * @param {Function} setAttributes Function to update attributes
     * @returns {Object} React element
     */
    function generateControl(key, attr, attributes, setAttributes) {
        const value = attributes[key];
        const onChange = (newValue) => setAttributes({ [key]: newValue });
        const label = attr.label || key.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        switch (attr.type) {
            case 'boolean':
                return el(ToggleControl, {
                    key: key,
                    label: label,
                    help: attr.description || '',
                    checked: !!value,
                    onChange: onChange
                });

            case 'number':
            case 'integer':
                if (attr.min !== undefined && attr.max !== undefined) {
                    return el(RangeControl, {
                        key: key,
                        label: label,
                        help: attr.description || '',
                        value: value || attr.default || 0,
                        onChange: onChange,
                        min: attr.min,
                        max: attr.max,
                        step: attr.step || 1
                    });
                }
                return el(TextControl, {
                    key: key,
                    label: label,
                    help: attr.description || '',
                    value: value || '',
                    onChange: (val) => onChange(parseInt(val, 10) || 0),
                    type: 'number'
                });

            case 'string':
            default:
                if (attr.enum) {
                    const options = attr.enum.map(v => ({
                        label: typeof v === 'object' ? v.label : v,
                        value: typeof v === 'object' ? v.value : v
                    }));

                    return el(SelectControl, {
                        key: key,
                        label: label,
                        help: attr.description || '',
                        value: value || attr.default || '',
                        onChange: onChange,
                        options: options
                    });
                }

                return el(TextControl, {
                    key: key,
                    label: label,
                    help: attr.description || '',
                    value: value || '',
                    onChange: onChange
                });
        }
    }

    /**
     * Create edit component for a block
     *
     * @param {Object} blockConfig Block configuration
     * @param {string} blockName Block name
     * @returns {Function} React component
     */
    function createEditComponent(blockConfig, blockName) {
        return function EditComponent(props) {
            const { attributes, setAttributes, isSelected } = props;
            const blockProps = useBlockProps();

            // Build inspector controls from attributes
            const attributeConfigs = blockConfig.attributes || {};
            const controls = Object.keys(attributeConfigs).map(function (key) {
                return generateControl(key, attributeConfigs[key], attributes, setAttributes);
            });

            const hasControls = controls.length > 0;

            return el(Fragment, {},
                // Inspector Controls (sidebar)
                hasControls && el(InspectorControls, {},
                    el(PanelBody, {
                        title: i18n.blockSettings || __('Block Settings', 'jmvc'),
                        initialOpen: true
                    }, ...controls)
                ),

                // Block content
                el('div', blockProps,
                    el(ServerSideRender, {
                        block: blockName,
                        attributes: attributes,
                        LoadingResponsePlaceholder: function () {
                            return el(Placeholder, {
                                icon: blockConfig.icon || 'admin-generic',
                                label: blockConfig.title || blockName
                            },
                                el(Spinner)
                            );
                        },
                        ErrorResponsePlaceholder: function ({ response }) {
                            return el(Placeholder, {
                                icon: 'warning',
                                label: __('Error loading block', 'jmvc')
                            },
                                el('p', {}, response?.errorMsg || __('Unknown error', 'jmvc'))
                            );
                        },
                        EmptyResponsePlaceholder: function () {
                            return el(Placeholder, {
                                icon: blockConfig.icon || 'admin-generic',
                                label: blockConfig.title || blockName
                            },
                                el('p', {}, __('No content to display. Configure block settings.', 'jmvc'))
                            );
                        }
                    })
                )
            );
        };
    }

    /**
     * Register each JMVC block
     */
    Object.keys(blocks).forEach(function (blockName) {
        const config = blocks[blockName];

        // Build attributes with defaults
        const attributes = {};
        const attrConfigs = config.attributes || {};

        Object.keys(attrConfigs).forEach(function (key) {
            const attr = attrConfigs[key];
            attributes[key] = {
                type: attr.type || 'string'
            };

            if (attr.default !== undefined) {
                attributes[key].default = attr.default;
            }

            if (attr.enum) {
                attributes[key].enum = attr.enum.map(v =>
                    typeof v === 'object' ? v.value : v
                );
            }
        });

        // Register the block
        registerBlockType(blockName, {
            title: config.title || blockName.split('/').pop(),
            description: config.description || '',
            category: config.category || 'jmvc-blocks',
            icon: config.icon || 'admin-generic',
            keywords: config.keywords || [],
            supports: config.supports || {},
            attributes: attributes,
            example: config.example || {},

            edit: createEditComponent(config, blockName),

            save: function () {
                // Server-side rendered blocks return null
                return null;
            }
        });
    });

    // Log registered blocks in development
    if (typeof console !== 'undefined' && Object.keys(blocks).length > 0) {
        console.log('JMVC Blocks registered:', Object.keys(blocks));
    }

})(window.wp);
