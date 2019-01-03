<?php
/**
 * Renders the EDD tickets table/form
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/eddtickets/tickets.php
 *
 * @version 4.9
 *
 * @var bool $must_login
 */
global $edd_options;

$is_there_any_product         = false;
$is_there_any_product_to_sell = false;
$unavailability_messaging     = is_callable( array( $this, 'do_not_show_tickets_unavailable_message' ) );
$stock_mananger               = Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance()->stock();

if ( ! empty( $tickets ) ) {
	$tickets = tribe( 'tickets.handler' )->sort_tickets_by_menu_order( $tickets );
}

ob_start();
?>
<form
	id="buy-tickets"
	action="<?php echo esc_url( add_query_arg( 'eddtickets_process', 1, edd_get_checkout_uri() ) ); ?>"
	class="cart"
	method="post"
	enctype='multipart/form-data'
	novalidate
>
	<h2 class="tribe-events-tickets-title"><?php esc_html_e( 'Tickets', 'event-tickets-plus' );?></h2>

	<table class="tribe-events-tickets">
		<?php
		foreach ( $tickets as $ticket ) {
			/**
			 * Changing any HTML to the `$ticket` Arguments you will need apply filters
			 * on the `eddtickets_get_ticket` hook.
			 */

			$product   = edd_get_download( $ticket->ID );
			$available = $ticket->available();

			if ( $ticket->date_in_range() ) {

				$is_there_any_product = true;

				echo sprintf( '<input type="hidden" name="product_id[]"" value="%d">', esc_attr( $ticket->ID ) );

				echo '<tr class="tribe-edd-ticket-row-' . absint( $ticket->ID ) . '">';
				echo '<td width="75" class="edd quantity" data-product-id="' . esc_attr( $ticket->ID ) . '">';


				if ( 0 !== $available ) {
					$stock = $ticket->stock();

					$max = '';
					if ( $ticket->managing_stock() ) {
						$max = 'max="' . absint( $stock ) . '"';
					}

					echo '<input type="number" class="edd-input" min="0" ' . $max . ' name="quantity_' . esc_attr( $ticket->ID ) . '" value="0" ' . disabled( $must_login, true, false ) . '/>';

					$is_there_any_product_to_sell = true;

					if ( $available ) {
						?>
						<span class="tribe-tickets-remaining">
							<?php
							$readable_amount = tribe_tickets_get_readable_amount( $available, null, false );
							echo sprintf( esc_html__( '%1$s available', 'event-tickets-plus' ),
								'<span class="available-stock" data-product-id="' . esc_attr( $ticket->ID ) . '">' . esc_html( $readable_amount ) . '</span>'
							);
							?>
						</span>
						<?php
					}
				}
				else {
					echo '<span class="tickets_nostock">' . esc_html__( 'Out of stock!', 'event-tickets-plus' ) . '</span>';
				}

				echo '</td>';

				echo '<td class="tickets_name">' . $ticket->name . '</td>';

				echo '<td class="tickets_price">' . $this->get_price_html( $product ) . '</td>';

				echo '<td class="tickets_description">' . ( $ticket->show_description() ? $ticket->description : '' ) . '</td>';

				echo '</tr>';

				/**
				 * Use this filter to hide the Attendees List Optout
				 *
				 * @since 4.5.2
				 *
				 * @param bool
				 */
				$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false );

				if ( ! $hide_attendee_list_optout &&
					 class_exists( 'Tribe__Tickets_Plus__Attendees_List' ) &&
					 ! Tribe__Tickets_Plus__Attendees_List::is_hidden_on( get_the_ID() )
				) : ?>
					<tr class="tribe-tickets-attendees-list-optout">
						<td colspan="4">
							<input
								type="checkbox"
								name="optout_<?php echo esc_attr( $ticket->ID ); ?>"
								id="tribe-tickets-attendees-list-optout-edd"
							>
							<label for="tribe-tickets-attendees-list-optout-edd"><?php esc_html_e( "Don't list me on the public attendee list", 'event-tickets-plus' ); ?></label>
						</td>
					</tr>
				<?php
				endif;
			}
		}
		?>

		<?php if ( $is_there_any_product_to_sell ) :
			$color = isset( $edd_options[ 'checkout_color' ] ) ? $edd_options[ 'checkout_color' ] : 'gray';
			$color = ( $color == 'inherit' ) ? '' : $color;
			?>
			<tr>
				<td colspan="4" class="eddtickets-add">
					<?php if ( $must_login ) : ?>
						<?php include Tribe__Tickets_Plus__Main::instance()->get_template_hierarchy( 'login-to-purchase' ); ?>
					<?php else: ?>
						<button type="submit" class="edd-submit tribe-button <?php echo esc_attr( $color ); ?>"><?php esc_html_e( 'Add to cart', 'event-tickets-plus' );?></button>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>

		<noscript>
			<tr>
				<td class="tribe-link-tickets-message">
					<div class="no-javascript-msg"><?php esc_html_e( 'You must have JavaScript activated to purchase tickets. Please enable JavaScript in your browser.', 'event-tickets-plus' ); ?></div>
				</td>
			</tr>
		</noscript>
	</table>
</form>

<?php
$contents = ob_get_clean();
echo $contents;

if ( $is_there_any_product ) {
	// If we have available tickets there is generally no need to display a 'tickets unavailable' message
	// for this post
	$this->do_not_show_tickets_unavailable_message();
} else {
	// Indicate that there are not any tickets, so a 'tickets unavailable' message may be
	// appropriate (depending on whether other ticket providers are active and have a similar
	// result)
	$this->maybe_show_tickets_unavailable_message( $tickets );
}
