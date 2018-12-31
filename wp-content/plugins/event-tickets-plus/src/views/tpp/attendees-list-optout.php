<?php
/**
 * Renders the PayPal tickets attendee list optout inputs.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tpp/attendees-list-optout.php
 *
 * @version 4.7
 *
 * @var \Tribe__Tickets__Ticket_Object $ticket
 */

/**
 * Use this filter to hide the Attendees List Optout
 *
 * @since 4.5.2
 *
 * @param bool
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false );

if ( ! $hide_attendee_list_optout
     && class_exists( 'Tribe__Tickets_Plus__Attendees_List' )
     && ! Tribe__Tickets_Plus__Attendees_List::is_hidden_on( get_the_ID() )
) { ?>
	<tr class="tribe-tickets-attendees-list-optout">
		<td colspan="4">
			<input
				type="checkbox"
				name="tpp_optout[]"
				id="tribe-tickets-attendees-list-optout-edd"
				value="<?php echo esc_attr( $ticket->ID ); ?>"
			>
			<label for="tribe-tickets-attendees-list-optout-edd"><?php esc_html_e( "Don't list me on the public attendee list", 'event-tickets-plus' ); ?></label>
		</td>
	</tr>
	<?php
}
