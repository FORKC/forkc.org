<?php
/**
 * Renders the meta fields for order editing
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets-plus/orders-edit-meta.php
 *
 * @version 4.4.3
 *
 */
$ticket = get_post( $attendee['product_id'] );

if ( empty( $ticket ) ) {
	?>
		<p><?php esc_html_e( 'Ticket deleted: attendee info cannot be updated.', 'event-tickets-plus' ); ?></p>
	<?php

	return;
}

$meta = Tribe__Tickets_Plus__Main::instance()->meta();
if ( $meta->meta_enabled( $ticket->ID ) ) {
	?>
	<div class="tribe-event-tickets-plus-meta" id="tribe-event-tickets-plus-meta-<?php echo esc_attr( $ticket->ID ); ?>" data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>">
		<a class="attendee-meta toggle show"><?php esc_html_e( 'Toggle attendee info', 'event-tickets-plus' ); ?></a>
		<div class="attendee-meta-row">
			<?php
			$meta_fields = $meta->get_meta_fields_by_ticket( $ticket->ID );
			foreach ( $meta_fields as $field ) {
				echo $field->render( $attendee['attendee_id'] );
			}
			?>
		</div>
	</div>
<?php }
