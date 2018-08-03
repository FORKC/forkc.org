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

registerBlockType('tickera/woo-event-add-to-cart', {
    title: __('Event - Add to Cart'),
    description: __('Event Tickets (products) Add to Cart table'),
    icon: 'cart',
    category: 'widgets',
    keywords: [
        __('Tickera'),
        __('Event'),
        __('WooCommerce'),
    ],
    supports: {
        html: false,
    },
    attributes: {
        id: {
            type: 'string',
        },
        ticket_type_title: {
            type: 'string',
            default: __('Ticket Type')
        },
        price_title: {
            type: 'string',
            default: __('Price')
        },
        cart_title: {
            type: 'string',
            default: __('Cart')
        },

    },
    edit: function (props) {
        var events = jQuery.parseJSON(tc_woo_event_add_to_cart_block_editor_events.events);
        var event_ids = [

        ];

        console.log(events);

        events.forEach(function (entry) {
            event_ids.push({value: entry[0], label: entry[1]});
        });

        return [
            el(
                    InspectorControls,
                    {key: 'controls'},
                    el(
                            SelectControl,
                            {
                                label: __('Event'),
                                value: props.attributes.id,
                                onChange: function change_val(value) {
                                    return props.setAttributes({id: value});
                                },
                                options: event_ids
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Ticket Type Column Title'),
                                value: props.attributes.ticket_type_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({ticket_type_title: value});
                                },
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Price Column Title'),
                                value: props.attributes.price_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({price_title: value});
                                },
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Cart Column Title'),
                                value: props.attributes.cart_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({cart_title: value});
                                },
                            }
                    ),
                    ),

            el(ServerSideRender, {
                block: "tickera/woo-event-add-to-cart",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});