var el = wp.element.createElement,
        registerBlockType = wp.blocks.registerBlockType,
        BlockControls = wp.editor.BlockControls,
        InspectorControls = wp.editor.InspectorControls,
        ServerSideRender = wp.components.ServerSideRender;

var AlignmentToolbar = wp.editor.AlignmentToolbar;
var RichText = wp.editor.RichText;
var SelectControl = wp.components.SelectControl;
var RangeControl = wp.components.RangeControl;
var TextControl = wp.components.TextControl;
var ToggleControl = wp.components.ToggleControl;

var __ = wp.i18n.__;

registerBlockType('tickera/seating-charts', {
    title: __('Seating Chart'),
    description: __('Show seating chart button.'),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __('Tickera'),
        __('Seating'),
        __('Chart'),
    ],
    supports: {
        html: false,
    },
    attributes: {
        id: {
            type: 'string',
        },
        show_legend: {
            type: 'boolean',
            default: false,
        },
        button_title: {
            type: 'string',
            default: __('Pick your seat(s)')
        },
        subtotal_title: {
            type: 'string',
            default: __('Subtotal')
        },
        cart_title: {
            type: 'string',
            default: __('Go to Cart')
        },

    },
    edit: function (props) {
        var seating_charts = jQuery.parseJSON(tc_seating_charts_block_editor.seating_charts);
        var seating_charts_ids = [

        ];

        seating_charts.forEach(function (entry) {
            seating_charts_ids.push({value: entry[0], label: entry[1]});
        });

        return [
            el(
                    InspectorControls,
                    {key: 'controls'},
                    el(
                            SelectControl,
                            {
                                label: __('Seating Chart'),
                                value: props.attributes.id,
                                onChange: function change_val(value) {
                                    return props.setAttributes({id: value});
                                },
                                options: seating_charts_ids
                            }
                    ),
                    el(
                            ToggleControl,
                            {
                                label: __('Show Legend'),
                                checked: props.attributes.show_legend,
                                value: props.attributes.show_legend,
                                onChange: function onChange(value) {
                                    return props.setAttributes({show_legend: value});
                                },
                            }
                    ),
            
            
                    el(
                            TextControl,
                            {
                                label: __('Button Title'),
                                value: props.attributes.button_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({button_title: value});
                                },
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Subtotal Title'),
                                value: props.attributes.subtotal_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({subtotal_title: value});
                                },
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Cart Title'),
                                value: props.attributes.cart_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({cart_title: value});
                                },
                            }
                    ),
                    ),

            el(ServerSideRender, {
                block: "tickera/seating-charts",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});