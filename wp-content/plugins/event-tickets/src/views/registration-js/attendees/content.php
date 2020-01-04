<?php
/**
 * This template renders the registration/purchase attendee fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/attendees/content.php
 *
 * @since 4.11.0
 *
 * @version 4.11.0
 *
 */
if (
	! class_exists( 'Tribe__Tickets_Plus__Meta' )
	|| ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' )
) {
	return;
}

$storage = new Tribe__Tickets_Plus__Meta__Storage();
/**
* @var Tribe__Tickets_Plus__Meta $meta
*/
$meta     = tribe( 'tickets-plus.main' )->meta();
$provider = $this->get( 'provider' );
$event_id = $this->get( 'event_id' );
$tickets  = $this->get( 'tickets' );

if ( empty( $tickets ) ) {
	// Nothing to see here!
	return;
}
?>
<?php foreach ( $tickets as $ticket ) : ?>
		<?php
		// Sometimes we get an array - let's handle that.
		if ( is_array( $ticket ) ) {
			$ticket = $provider->get_ticket( $event_id, $ticket['id'] );
		}

		// Only include tickets with meta
		$has_meta = get_post_meta( $ticket->ID, '_tribe_tickets_meta_enabled', true );

		if ( empty( $has_meta ) || ! tribe_is_truthy( $has_meta ) ) {
			continue;
		}
		?>
		<script type="text/html" class="registration-js-attendees-content" id="tmpl-tribe-registration--<?php echo esc_attr( $ticket->ID ); ?>">
			<?php
			$ticket_qty = 1;
			$post       = get_post( $ticket->ID );
			$fields     = $meta->get_meta_fields_by_ticket( $post->ID );
			$saved_meta = $storage->get_meta_data_for( $post->ID );
			?>
			<?php // go through each attendee ?>
			<?php while ( 0 < $ticket_qty ) : ?>
				<?php

					$args = [
						'event_id'   => $event_id,
						'ticket'     => $post,
						'fields'     => $fields,
						'saved_meta' => $saved_meta,
					];

					$this->template( 'registration-js/attendees/fields', $args );
					$ticket_qty--;
				?>
			<?php endwhile; ?>
		</script>
<?php
endforeach;
