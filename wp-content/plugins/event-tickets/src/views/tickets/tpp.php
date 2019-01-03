<?php
/**
 * This template renders the Tribe Commerce ticket form
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/tickets/tpp.php
 *
 * @version 4.9.3
 *
 * @var bool $must_login
 * @var bool $display_login_link
 */

$is_there_any_product         = false;
$is_there_any_product_to_sell = false;
$are_products_available       = false;

/** @var Tribe__Tickets__Commerce__PayPal__Main $commerce */
$commerce       = tribe( 'tickets.commerce.paypal' );
$messages       = $commerce->get_messages();
$messages_class = $messages ? 'tribe-tpp-message-display' : '';
$now            = current_time( 'timestamp' );
$cart_url       = '';
?>
<form
	id="tpp-buy-tickets"
	action="<?php echo esc_url( $cart_url ); ?>"
	class="tribe-tickets-tpp cart <?php echo esc_attr( $messages_class ); ?>"
	method="post"
	enctype='multipart/form-data'
>
	<input type="hidden" name="provider" value="Tribe__Tickets__Commerce__PayPal__Main">
	<input type="hidden" name="add" value="1">
	<h2 class="tribe-events-tickets-title tribe--tpp">
		<?php echo esc_html_x( 'Tickets', 'form heading', 'event-tickets' ) ?>
	</h2>

	<div class="tribe-tpp-messages">
		<?php
		if ( $messages ) {
			foreach ( $messages as $message ) {
				?>
				<div class="tribe-tpp-message tribe-tpp-message-<?php echo esc_attr( $message->type ); ?>">
					<?php echo esc_html( $message->message ); ?>
				</div>
				<?php
			}//end foreach
		}//end if
		?>

		<div
			class="tribe-tpp-message tribe-tpp-message-error tribe-tpp-message-confirmation-error" style="display:none;">
			<?php esc_html_e( 'Please fill in the ticket confirmation name and email fields.', 'event-tickets' ); ?>
		</div>
	</div>

	<table class="tribe-events-tickets tribe-events-tickets-tpp">
		<?php
		$item_counter = 1;
		foreach ( $tickets as $ticket ) {
			// if the ticket isn't a Tribe Commerce ticket, then let's skip it
			if ( 'Tribe__Tickets__Commerce__PayPal__Main' !== $ticket->provider_class ) {
				continue;
			}

			if ( ! $ticket->date_in_range() ) {
				continue;
			}

			$is_there_any_product         = true;
			$is_there_any_product_to_sell = $ticket->is_in_stock();
			$inventory                    = (int) $ticket->inventory();
			$max_quantity                 = $inventory > 0 ? $inventory : '';
			?>
			<tr>
				<td class="tribe-ticket quantity" data-product-id="<?php echo esc_attr( $ticket->ID ); ?>">
					<input type="hidden" name="product_id[]" value="<?php echo absint( $ticket->ID ); ?>">
					<?php if ( $is_there_any_product_to_sell ) : ?>
						<input
							type="number"
							class="tribe-ticket-quantity qty"
							min="0"
							<?php if ( $max_quantity ) { echo 'max="' . esc_attr( $max_quantity ) . '"'; } ?>
							name="quantity_<?php echo absint( $ticket->ID ); ?>"
							value="0"
							<?php disabled( $must_login ); ?>
						>
						<?php if ( $ticket->managing_stock() ) : ?>
							<span class="tribe-tickets-remaining">
							<?php
							$readable_amount = tribe_tickets_get_readable_amount( $ticket->available(), null, false );
							echo sprintf( esc_html__( '%1$s available', 'event-tickets' ), '<span class="available-stock" data-product-id="' . esc_attr( $ticket->ID ) . '">' . esc_html( $readable_amount ) . '</span>' );
							?>
							</span>
						<?php endif; ?>
					<?php else: ?>
						<span class="tickets_nostock"><?php esc_html_e( 'Out of stock!', 'event-tickets' ); ?></span>
					<?php endif; ?>
				</td>
				<td class="tickets_name">
					<?php echo esc_html( $ticket->name ); ?>
				</td>
				<td class="tickets_price">
					<?php echo $this->main->get_price_html( $ticket->ID ); ?>
				</td>
				<td class="tickets_description" colspan="2">
					<?php echo esc_html( ( $ticket->show_description() ? $ticket->description : '' ) ); ?>
				</td>
				<td class="tickets_submit">
					<?php if ( ! $must_login ) : ?>
						<button type="submit" class="tpp-submit tribe-button"><?php esc_html_e( 'Buy now', 'event-tickets' );?></button>
					<?php endif; ?>
				</td>
			</tr>
			<?php

			/**
			 * Allows injection of HTML after an Tribe Commerce ticket table row
			 *
			 * @var WP_Post $post The post object the ticket is attached to.
			 * @var Tribe__Tickets__Ticket_Object $ticket
			 */
			do_action( 'event_tickets_tpp_after_ticket_row', tribe_events_get_ticket_event( $ticket->id ), $ticket );
		}

		$is_there_any_message_to_show = ! is_user_logged_in() && ( $must_login && $display_login_link );
		?>

		<?php if ( $is_there_any_product_to_sell && $is_there_any_message_to_show ) : ?>
			<tr>
				<td colspan="5" class="tpp-add">
					<?php if ( $must_login ) : ?>
						<?php include tribe( 'tickets.commerce.paypal' )->getTemplateHierarchy( 'login-to-purchase' ); ?>
					<?php endif; ?>
					<?php if ( ! $must_login && $display_login_link ) : ?>
						<?php include tribe( 'tickets.commerce.paypal' )->getTemplateHierarchy( 'login-before-purchase' ); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif ?>

		<?php if ( tribe( 'tickets.commerce.paypal.cart' )->has_items() ) : ?>
			<tr>
				<td colspan="5" class="tpp-add">
					<?php include tribe( 'tickets.commerce.paypal' )->getTemplateHierarchy( 'tickets/tpp-return-to-cart' ); ?>
				</td>
			</tr>
		<?php endif ?>

		<noscript>
			<tr>
				<td class="tribe-link-tickets-message">
					<div class="no-javascript-msg"><?php esc_html_e( 'You must have JavaScript activated to purchase tickets. Please enable JavaScript in your browser.', 'event-tickets' ); ?></div>
				</td>
			</tr>
		</noscript>
	</table>
</form>
