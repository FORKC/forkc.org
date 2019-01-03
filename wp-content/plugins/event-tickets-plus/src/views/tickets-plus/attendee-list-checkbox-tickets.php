<?php
/**
 * Renders the attendee list checkbox for tickets
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets-plus/attendee-list-checkbox-tickets.php
 *
 * @version 4.5
 *
 */
$view = Tribe__Tickets__Tickets_View::instance();
?>
<div class="tribe-tickets-attendees-list-optout">
	<input
		<?php echo $view->get_restriction_attr( $post_id, esc_attr( $first_attendee['product_id'] ) ); ?>
		type="checkbox"
		name="optout[<?php echo esc_attr( $first_attendee['order_id'] ); ?>]"
		id="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>"
		<?php checked( true, esc_attr( $first_attendee['optout'] ) ); ?>
	>
	<label for="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>"><?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets-plus' ); ?></label>
</div>