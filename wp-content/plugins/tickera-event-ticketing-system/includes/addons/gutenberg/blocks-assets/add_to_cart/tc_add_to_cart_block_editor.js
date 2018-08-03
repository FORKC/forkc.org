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

registerBlockType('tickera/add-to-cart', {
    title: __('Ticket Add to Cart'),
    description: __('Ticket Add to Cart button'),
    icon: 'cart',
    category: 'widgets',
    keywords: [ 
      __( 'Tickera' ), 
      __( 'Cart' ), 
      __( 'Add' ) 
    ],
    supports: {
        html: false,
    },
    attributes: {
        ticket_type_id: {
            type: 'string',
        },

        souldout_message: {
            type: 'string',
            default: __('Tickets are sold out')
        },
        show_price: {
            type: 'boolean',
            default: false,
        },
        price_position: {
            type: 'string',
            default: 'after',
        },
        quantity: {
            type: 'boolean',
            default: false,
        },
        link_type: {
            type: 'string',
            default: 'cart'
        }

    },
    edit: function (props) {
        var ticket_types = jQuery.parseJSON(tc_add_to_cart_block_editor_ticket_types.ticket_types);
        var ticket_ids = [

        ];
        var first = true;
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
                                label: __('Ticket Type'),
                                value: props.attributes.ticket_type_id,
                                onChange: function change_val(value) {
                                    return props.setAttributes({ticket_type_id: value});
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
                    el(
                            SelectControl,
                            {
                                label: __('Price Position'),
                                value: props.attributes.price_position,
                                onChange: function change_val(value) {
                                    return props.setAttributes({price_position: value});
                                },
                                options: [
                                    {value: 'before', label: __('Before')},
                                    {value: 'after', label: __('After')},
                                ]
                            }
                    ),
                    el(
                            ToggleControl,
                            {
                                label: __('Show Quantity'),
                                checked: props.attributes.quantity,
                                value: props.attributes.quantity,
                                onChange: function onChange(value) {
                                    return props.setAttributes({quantity: value});
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
                                label: __('Soldout Message'),
                                help: __('The message which will be shown when all tickets are sold'),
                                value: props.attributes.souldout_message,
                                onChange: function change_val(value) {
                                    return props.setAttributes({souldout_message: value});
                                },
                            }
                    ),
                    ),

            el(ServerSideRender, {
                block: "tickera/add-to-cart",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
        var content = props.attributes.content;
        var alignment = props.attributes.alignment;
        var columns = props.attributes.columns;
        return el(RichText.Content, {
            className: props.className,
            style: {textAlign: alignment},
            value: content
        });
    },
});