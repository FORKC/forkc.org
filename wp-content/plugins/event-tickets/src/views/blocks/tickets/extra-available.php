<?php
/**
 * Block: Tickets
 * Extra column, available
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @since   4.9.3
 * @since   4.11.1 Corrected amount of available/remaining tickets.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @version 4.11.1
 *
 * @var Tribe__Tickets__Editor__Template $this
 */

/** @var Tribe__Tickets__Ticket_Object $ticket */
$ticket = $this->get( 'ticket' );

if ( empty( $ticket->ID ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$available = $tickets_handler->get_ticket_max_purchase( $ticket->ID );

if ( -1 === $available ) {
	return;
}

$post_id   = $this->get( 'post_id' );

/** @var Tribe__Settings_Manager $settings_manager */
$settings_manager = tribe( 'settings.manager' );

$threshold = $settings_manager::get_option( 'ticket-display-tickets-left-threshold', null );

/**
 * Overwrites the threshold to display "# tickets left".
 *
 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
 * @param array $data      Ticket data.
 * @param int   $post_id   WP_Post/Event ID.
 *
 * @since 4.11.1
 */
$threshold = absint( apply_filters( 'tribe_display_tickets_block_tickets_left_threshold', $threshold, $post_id ) );
$available = $ticket->available();

/**
 * Allows hiding of "unlimited" to be toggled on/off conditionally.
 *
 * @param int   $show_unlimited allow showing of "unlimited".
 *
 * @since 4.11.1
 */
$show_unlimited = apply_filters( 'tribe_tickets_block_show_unlimited_availability', false, $available );
?>
<div
	class="tribe-common-b3 tribe-tickets__item__extra__available"
>
	<?php if ( $show_unlimited ) : ?>
		<?php $this->template( 'blocks/tickets/extra-available-unlimited', array( 'ticket' => $ticket, 'key' => $key ) ); ?>
	<?php elseif ( 0 === $threshold || $available <= $threshold ) : ?>
		<?php $this->template( 'blocks/tickets/extra-available-quantity', [ 'ticket' => $ticket, 'available' => $available ] ); ?>
	<?php endif; ?>
</div>
