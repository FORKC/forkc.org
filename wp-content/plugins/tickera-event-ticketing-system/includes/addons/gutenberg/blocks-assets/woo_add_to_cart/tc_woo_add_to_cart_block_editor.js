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

registerBlockType('tickera/woo-add-to-cart', {
    title: __('Woo Ticket Add to Cart'),
    description: __('Woo Ticket Add to Cart button'),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __('Tickera'),
        __('Cart'),
        __('WooCommerce'),
    ],
    supports: {
        html: false,
    },
    attributes: {
        id: {
            type: 'string',
        },
        show_price: {
            type: 'boolean',
            default: false,
        },
    },
    edit: function (props) {
        var ticket_types = jQuery.parseJSON(tc_woo_add_to_cart_block_editor_ticket_types.ticket_types);
        var ticket_ids = [

        ];

        ticket_types.forEach(function (entry) {
            ticket_ids.push({value: entry[0], label: entry[1]});
        });

        return [
            el(
                    InspectorControls,
                    {key: 'controls'},
                    el(
                            SelectControl,
                            {
                                label: __('Ticket Type (product)'),
                                value: props.attributes.id,
                                onChange: function change_val(value) {
                                    return props.setAttributes({id: value});
                                },
                                options: ticket_ids
                            }
                    ),
                    el(
                            ToggleControl,
                            {
                                label: __('Show Price'),
                                checked: props.attributes.show_price,
                                value: props.attributes.show_price,
                                onChange: function onChange(value) {
                                    return props.setAttributes({show_price: value});
                                },
                            }
                    ),
                    ),

            el(ServerSideRender, {
                block: "tickera/woo-add-to-cart",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});