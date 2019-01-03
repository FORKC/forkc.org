<?php
/**
 * EDD cart class
 *
 * @since 4.9
 */
class Tribe__Tickets_Plus__Commerce__EDD__Cart extends Tribe__Tickets_Plus__Commerce__Abstract_Cart {
	/**
	 * Hook relevant actions and filters
	 *
	 * @since 4.9
	 */
	public function hook() {
		parent::hook();

		add_filter( 'tribe_tickets_attendee_registration_checkout_url', array( $this, 'maybe_filter_attendee_registration_checkout_url' ) );
		add_filter( 'tribe_tickets_tickets_in_cart', array( $this, 'get_tickets_in_cart' ) );
	}

	/**
	 * Hijack URL if on cart and there
	 * are attendee registration fields that need to be filled out
	 *
	 * @since 4.9
	 *
	 * @param string $checkout_url
	 *
	 * @return string
	 */
	public function maybe_filter_attendee_registration_checkout_url( $checkout_url ) {
		$on_registration_page = tribe( 'tickets.attendee_registration' )->is_on_page();

		// we only want to override if we are on the cart page or the attendee registration page
		if ( ! $on_registration_page ) {
			return $checkout_url;
		}

		return edd_get_checkout_uri();
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
		$contents = edd_get_cart_contents();

		if ( empty( $contents ) ) {
			return $tickets;
		}

		foreach ( $contents as $item ) {
		    $edd_check = get_post_meta( $item['id'], tribe( 'tickets-plus.commerce.edd' )->event_key, true );
			if ( empty( $edd_check ) ) {
				continue;
			}

			$tickets[ $item['id'] ] = $item['quantity'];
		}

		return $tickets;
	}
}