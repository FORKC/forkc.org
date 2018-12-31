<?php
/**
 * Block: RSVP
 * Status Not Going
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/status/not-going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9.3
 *
 */


/**
 * @todo: Create a hook for the get_ticket method in order to set dynamic or custom properties into
 * the instance variable so we can set a new one called $ticket->show_not_going.
 *
 * Method is located on:
 * - https://github.com/moderntribe/event-tickets/blob/9e77f61f191bbc86ee9ec9a0277ed7dde66ba0d8/src/Tribe/RSVP.php#L1130
 *
 * For now we need to access directly the value of the meta field in order to render this field.
 */
$show_not_going = tribe_is_truthy(
	get_post_meta( $ticket->ID, '_tribe_ticket_show_not_going', true )
);

if ( ! $show_not_going ) {
    return;
}
?>
<span>
	<button class="tribe-block__rsvp__status-button tribe-block__rsvp__status-button--not-going">
		<?php $this->template( 'blocks/rsvp/status/not-going-icon' ); ?>
		<span><?php esc_html_e( 'Not going', 'event-tickets' ); ?></span>
	</button>
</span>