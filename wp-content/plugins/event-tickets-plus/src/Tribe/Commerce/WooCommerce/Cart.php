<?php
/**
 * WooCommerce cart class
 *
 * @since 4.9
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Cart extends Tribe__Tickets_Plus__Commerce__Abstract_Cart {
	/**
	 * Hook relevant actions and filters
	 *
	 * @since 4.9
	 */
	public function hook() {
		parent::hook();

		add_filter( 'tribe_tickets_attendee_registration_checkout_url', array( $this, 'maybe_filter_attendee_registration_checkout_url' ) );
		add_filter( 'woocommerce_get_checkout_url', array( $this, 'maybe_filter_checkout_url_to_attendee_registration' ) );
		add_filter( 'tribe_tickets_tickets_in_cart', array( $this, 'get_tickets_in_cart' ) );
	}

	/**
	 * Hooked to the tribe_tickets_attendee_registration_checkout_url filter to hijack URL if on cart and there
	 * are attendee registration fields that need to be filled out
	 *
	 * @since 4.9
	 *
	 * @param string $checkout_url
	 *
	 * @return string
	 */
	public function maybe_filter_attendee_registration_checkout_url( $checkout_url ) {
		$checkout_url = $this->maybe_filter_checkout_url_to_attendee_registration( $checkout_url );

		if ( $checkout_url ) {
			return $checkout_url;
		}

		return wc_get_checkout_url();
	}

	/**
	 * Hooked to the woocommerce_get_checkout_url filter to hijack URL if on cart and there
	 * are attendee registration fields that need to be filled out
	 *
	 * @since 4.9
	 *
	 * @param string $checkout_url
	 *
	 * @return null|string
	 */
	public function maybe_filter_checkout_url_to_attendee_registration( $checkout_url ) {
		$on_registration_page = tribe( 'tickets.attendee_registration' )->is_on_page();

		// we only want to override if we are on the cart page or the attendee registration page
		if ( ! is_cart() && ! $on_registration_page ) {
			return $checkout_url;
		}

		$quantity_by_ticket_id = $this->get_tickets_in_cart();
		$is_stored_meta_up_to_date = tribe( 'tickets-plus.meta.contents' )->is_stored_meta_up_to_date( $quantity_by_ticket_id );

		// if the we are on the attendee registration page and there aren't any required
		// fields that still need to be filled out, we don't want to hijack
		if ( $on_registration_page && $is_stored_meta_up_to_date ) {
			return $checkout_url;
		} elseif ( $on_registration_page || $is_stored_meta_up_to_date ) {
			return $checkout_url;
		}

		return tribe( 'tickets.attendee_registration' )->get_url();
	}

	/**
	 * Get all tickets currently in the cart.
	 *
	 * @since 4.9
	 *
	 * @param array $tickets Array indexed by ticket id with quantity as the value
	 *
	 * @return array
	 */
	public function get_tickets_in_cart( $tickets = array() ) {
		$contents = WC()->cart->get_cart_contents();
		if ( empty( $contents ) ) {
			return $tickets;
		}

		foreach ( $contents as $item ) {
			$id = $item['product_id'];
			$woo_check = get_post_meta( $id, tribe( 'tickets-plus.commerce.woo' )->event_key, true );
			if ( empty( $woo_check ) ) {
				continue;
			}

			$tickets[ $item['product_id'] ] = $item['quantity'];
		}

		return $tickets;
	}
}