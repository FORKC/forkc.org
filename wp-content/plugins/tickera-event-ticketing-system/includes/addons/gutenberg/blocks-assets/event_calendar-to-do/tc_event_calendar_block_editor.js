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

registerBlockType('tickera/event-calendar', {
    title: __('Event Calendar'),
    description: __('Shows event calendar'),
    icon: 'calendar',
    category: 'widgets',
    keywords: [
        __('Tickera'),
        __('Event'),
        __('Calendar')
    ],
    supports: {
        html: false,
    },
    attributes: {
        color_scheme: {
            type: 'string',
            default: 'default'
        },
        lang: {
            type: 'string',
            default: 'en'
        },
        show_past_events: {
            type: 'boolean',
            default: false,
        },
    },
    edit: function (props) {

        var color_schemes_list = jQuery.parseJSON(tc_event_calendar_block_editor.color_schemes);
        var color_schemes_ids = [

        ];

        //console.log(color_schemes_list);

        color_schemes_list.forEach(function (entry) {
            color_schemes_ids.push({value: entry[0], label: entry[1]});
        });

        var languages = jQuery.parseJSON(tc_event_calendar_block_editor.languages);
        var languages_ids = [

        ];

        //console.log(languages);

        languages.forEach(function (entry) {
            languages_ids.push({value: entry[0], label: entry[1]});
        });





        return [
            el(
                    InspectorControls,
                    {key: 'controls'},
                    el(
                            SelectControl,
                            {
                                label: __('Color Scheme'),
                                value: props.attributes.color_scheme,
                                onChange: function change_val(value) {
                                    return props.setAttributes({color_scheme: value});
                                },
                                options: color_schemes_ids
                            }
                    ),
                    el(
                            SelectControl,
                            {
                                label: __('Language'),
                                value: props.attributes.lang,
                                onChange: function change_val(value) {
                                    return props.setAttributes({lang: value});
                                },
                                options: languages_ids
                            }
                    ),
                    el(
                            ToggleControl,
                            {
                                label: __('Show Past Events'),
                                checked: props.attributes.show_past_events,
                                value: props.attributes.show_past_events,
                                onChange: function onChange(value) {
                                    return props.setAttributes({show_past_events: value});
                                },
                            }
                    ),
                    ),

            el(ServerSideRender, {
                block: "tickera/event-calendar",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});