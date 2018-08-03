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

registerBlockType('tickera/event-tickets-left', {
    title: __('Event Tickets Left'),
    description: __('Shows number of tickets left (unsold) for an event'),
    icon: 'info',
    category: 'widgets',
    keywords: [ 
      __( 'Tickera' ), 
      __( 'Tickets' ), 
      __( 'Left' ) 
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
        var events = jQuery.parseJSON(tc_event_tickets_left_block_editor_events.events);
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
                block: "tickera/event-tickets-left",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});