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

registerBlockType('tickera/order-history', {
    title: __('User Order History'),
    description: __('Shows order history for current (logged in) user.'),
    icon: 'dashicons-media-spreadsheet',
    category: 'widgets',
    keywords: [ 
      __( 'Tickera' ), 
      __( 'Order' ), 
      __( 'History' ) 
    ],
    supports: {
        html: false,
    },
    attributes: {
        /*ticket_type_id: {
            type: 'string',
        },*/
    },
    edit: function (props) {
        return [
            el(ServerSideRender, {
                block: "tickera/order-history",
                attributes: props.attributes
            })

        ];
    },
    save: function (props) {
        return null;
    },
});