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

registerBlockType('tickera/event-tickets-sold', {
    title: __('Event Tickets Sold'),
    description: __('Shows number of sold tickets for an event'),
    icon: 'info',
    category: 'widgets',
    keywords: [ 
      __( 'Tickera' ), 
      __( 'Tickets' ), 
      __( 'Sold' ) 
    ],
    supports: {
        html: false,
    },
    attributes: {
        event_id: {
            type: 'string',
        },
    },
    edit: function (props) {
        var events = jQuery.parseJSON(tc_event_tickets_sold_block_editor_events.events);
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
                    ),

            el(ServerSideRender, {
                block: "tickera/event-tickets-sold",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});