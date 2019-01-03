<?php
/**
 * Renders the WooCommerce tickets table/form
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/wootickets/tickets.php
 *
 * @version 4.9
 *
 * @var bool $global_stock_enabled
 * @var bool $must_login
 */
global $woocommerce;

$is_there_any_product         = false;
$is_there_any_product_to_sell = false;
$unavailability_messaging     = is_callable( array( $this, 'do_not_show_tickets_unavailable_message' ) );

if ( ! empty( $tickets ) ) {
	$tickets = tribe( 'tickets.handler' )->sort_tickets_by_menu_order( $tickets );
}

ob_start();

/**
 * Filter classes on the Cart Form
 *
 * @since  4.3.2
 *
 * @param array $cart_classes
 */
$cart_classes = (array) apply_filters( 'tribe_events_tickets_woo_cart_class', array( 'cart' ) );
?>
<form
	id="buy-tickets"
	action="<?php echo esc_url( wc_get_cart_url() ) ?>"
	class="<?php echo esc_attr( implode( ' ', $cart_classes ) ); ?>"
	method="post"
	enctype='multipart/form-data'
	novalidate
>

	<h2 class="tribe-events-tickets-title tribe--tickets">
		<?php esc_html_e( 'Tickets', 'event-tickets-plus' ) ?>
	</h2>

	<table class="tribe-events-tickets">
		<?php
		/**
		 * Reorder the tickets per the admin interface order
		 *
		 * @since 4.6
		 */
		foreach ( $tickets as $ticket ) :
			/**
			 * Changing any HTML to the `$ticket` Arguments you will need apply filters
			 * on the `wootickets_get_ticket` hook.
			 */

			/**
			 * @var Tribe__Tickets__Ticket_Object $ticket
			 * @var WC_Product $product
			 */
			global $product;

			if ( class_exists( 'WC_Product_Simple' ) ) {
				$product = new WC_Product_Simple( $ticket->ID );
			} else {
				$product = new WC_Product( $ticket->ID );
			}

			$data_product_id      = '';

			if ( $ticket->date_in_range() ) {

				$is_there_any_product = true;

				echo sprintf( '<input type="hidden" name="product_id[]" value="%d">', esc_attr( $ticket->ID ) );

				/**
				 * Filter classes on the Price column
				 *
				 * @since  4.3.2
				 *
				 * @param array $column_classes
				 * @param int $ticket->ID
				 */
				$column_classes = (array) apply_filters( 'tribe_events_tickets_woo_quantity_column_class', array( 'woocommerce' ), $ticket->ID );

				// Max quantity will be left open if backorders allowed, restricted to 1 if the product is
				// constrained to be sold individually or else set to the available stock quantity
				$max_quantity = $product->backorders_allowed() ? '' : $product->get_stock_quantity();
				$max_quantity = $product->is_sold_individually() ? 1 : $max_quantity;
				$available    = $ticket->available();

				/**
				 * Filter classes on the row
				 *
				 * @since  4.5.5
				 *
				 * @param array $row_classes
				 * @param int $ticket->ID
				 */
				$row_classes = (array) apply_filters( 'tribe_events_tickets_row_class', array( 'woocommerce', 'tribe-tickets-form-row' ), $ticket->ID );
				echo '<tr class="' . esc_attr( implode( ' ', $row_classes ) ) . '" data-product-id="' . esc_attr( $ticket->ID ) . '">';

				/**
				 * Filter classes on the Price column
				 *
				 * @since  4.3.2
				 *
				 * @param array $column_classes
				 */
				$column_classes = (array) apply_filters( 'tribe_events_tickets_woo_quantity_column_class', array( 'woocommerce' ) );
				echo '<td class="' . esc_attr( implode( ' ', $column_classes ) ) . '" data-product-id="' . esc_attr( $ticket->ID ) . '">';

				if ( 0 !== $available ) {
					// Max quantity will be left open if backorders allowed, restricted to 1 if the product is
					// constrained to be sold individually or else set to the available stock quantity
					$stock        = $ticket->stock();
					$max_quantity = $product->backorders_allowed() ? '' : $stock;
					$max_quantity = $product->is_sold_individually() ? 1 : $max_quantity;
					$available    = $ticket->available();

					$input = woocommerce_quantity_input( array(
						'input_name'  => 'quantity_' . $ticket->ID,
						'input_value' => 0,
						'min_value'   => 0,
						'max_value'   => $max_quantity,
					), null, false );

					$is_there_any_product_to_sell = true;
					$disabled_attr = disabled( $must_login, true, false );
					$input = str_replace( '<input type="number"', '<input type="number"' . $disabled_attr, $input );

					echo $input;

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

					do_action( 'wootickets_tickets_after_quantity_input', $ticket, $product );
				} else {
					echo '<span class="tickets_nostock">' . esc_html__( 'Out of stock!', 'event-tickets-plus' ) . '</span>';
				}

				echo '</td>';

				echo '<td class="tickets_name">' . $ticket->name . '</td>';

				echo '<td class="tickets_price">';

				if ( method_exists( $product, 'get_price' ) && $product->get_price() ) {
					echo $this->get_price_html( $product );
				} else {
					esc_html_e( 'Free', 'event-tickets-plus' );
				}

				echo '</td>';

				echo '<td class="tickets_description">' . ( $ticket->show_description() ? $ticket->description : '' ) . '</td>';

				echo '</tr>';

				if ( $product->is_in_stock() ) {
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
									name="optout_<?php echo esc_attr( $ticket->ID ); ?>"
									id="tribe-tickets-attendees-list-optout-edd"
								>
								<label for="tribe-tickets-attendees-list-optout-edd"><?php esc_html_e( "Don't list me on the public attendee list", 'event-tickets-plus' ); ?></label>
							</td>
						</tr>
						<?php
					}
				}
			}

		endforeach; ?>

		<?php if ( $is_there_any_product_to_sell ) : ?>
			<tr>
				<td colspan="4" class="woocommerce add-to-cart">
					<?php if ( $must_login ) : ?>
						<?php include Tribe__Tickets_Plus__Main::instance()->get_template_hierarchy( 'login-to-purchase' ); ?>
					<?php else: ?>
						<button
							type="submit"
							name="wootickets_process"
							value="1"
							class="tribe-button"
						>
							<?php esc_html_e( 'Add to cart', 'event-tickets-plus' );?>
						</button>
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
$content = ob_get_clean();
echo $content;

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
