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
var PlainText = wp.components.PlainText;

var Editable = wp.blocks.Editable;
//var children = wp.blocks.query.children;

var __ = wp.i18n.__;

registerBlockType('tickera/tickets-sold', {
    title: __('Tickets Sold'),
    description: __('Shows number of sold tickets for a ticket type'),
    icon: 'info',
    category: 'widgets',
    keywords: [
        __('Tickera'),
        __('Tickets'),
        __('Sold')
    ],
    supports: {
        html: false,
    },
    attributes: {
        ticket_type_id: {
            type: 'string',
        },
    },
    edit: function (props) {
        var ticket_types = jQuery.parseJSON(tc_tickets_sold_block_editor_events.ticket_types);
        var ticket_ids = [

        ];

        ticket_types.forEach(function (entry) {
            ticket_ids.push({value: entry[0], label: entry[1]});
        });


        var content = props.attributes.content; // Content in our block.
        var focus = props.focus; // Focus — should be truthy.


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
                    ),
            el(ServerSideRender, {
                block: "tickera/tickets-sold",
                attributes: props.attributes
            }),
        ];
    },
    save: function (props) {
        return null;
    },
});