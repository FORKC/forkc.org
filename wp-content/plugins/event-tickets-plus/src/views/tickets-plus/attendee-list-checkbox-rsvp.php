<?php
/**
 * Renders the attendee list checkbox for rsvp's
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets-plus/attendee-list-checkbox-rsvp.php
 *
 * @version 4.5
 *
 */
$view = Tribe__Tickets__Tickets_View::instance();
?>
<div class="tribe-tickets-attendees-list-optout">
	<input <?php echo $view->get_restriction_attr( $post_id, esc_attr( $first_attendee['product_id'] ) ); ?> type="checkbox" name="attendee[<?php echo esc_attr( $first_attendee['order_id'] ); ?>][optout]" id="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>" <?php checked( true, esc_attr( $first_attendee['optout'] ) ) ?>>
	<label for="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $first_attendee['order_id'] ); ?>"><?php esc_html_e( 'Don\'t list me on the public attendee list', 'event-tickets-plus' ); ?></label>
</div>
