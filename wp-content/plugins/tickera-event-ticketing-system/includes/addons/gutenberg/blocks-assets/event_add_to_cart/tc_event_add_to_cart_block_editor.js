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

registerBlockType('tickera/event-add-to-cart', {
    title: __('Event - Add to Cart'),
    description: __('Event Tickets Add to Cart table'),
    icon: 'cart',
    category: 'widgets',
    keywords: [ 
      __( 'Tickera' ), 
      __( 'Event' ), 
      __( 'Cart' ) 
    ],
    supports: {
        html: false,
    },
    attributes: {
        event_id: {
            type: 'string',
        },
        button_title: {
            type: 'string',
            default: __('Add to Cart')
        },
        link_type: {
            type: 'string',
            default: 'cart'
        },
        quantity: {
            type: 'boolean',
            default: false,
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
        quantity_title: {
            type: 'string',
            default: __('QTY')
        },
        soldout_message: {
            type: 'string',
            default: __('Tickets are sold out.')
        },

    },
    edit: function (props) {
        var events = jQuery.parseJSON(tc_event_add_to_cart_block_editor_events.events);
        var event_ids = [

        ];

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
                                value: props.attributes.event_id,
                                onChange: function change_val(value) {
                                    return props.setAttributes({event_id: value});
                                },
                                options: event_ids
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Add to Cart button title'),
                                help: __('Title of the Add to Cart button'),
                                value: props.attributes.button_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({button_title: value});
                                },
                            }
                    ),
                    el(
                            SelectControl,
                            {
                                label: __('Button Type'),
                                value: props.attributes.link_type,
                                onChange: function change_val(value) {
                                    return props.setAttributes({link_type: value});
                                },
                                options: [
                                    {value: 'cart', label: __('Cart')},
                                    {value: 'buynow', label: __('Buy Now')},
                                ]
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
                    el(
                            ToggleControl,
                            {
                                label: __('Show Quantity Column'),
                                checked: props.attributes.quantity,
                                value: props.attributes.quantity,
                                onChange: function onChange(value) {
                                    return props.setAttributes({quantity: value});
                                },
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Quantity Column Title'),
                                value: props.attributes.quantity_title,
                                onChange: function change_val(value) {
                                    return props.setAttributes({quantity_title: value});
                                },
                            }
                    ),
                    el(
                            TextControl,
                            {
                                label: __('Soldout Message'),
                                help: __('The message which will be shown when all tickets are sold'),
                                value: props.attributes.soldout_message,
                                onChange: function change_val(value) {
                                    return props.setAttributes({soldout_message: value});
                                },
                            }
                    ),
                    ),

            el(ServerSideRender, {
                block: "tickera/event-add-to-cart",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});