<?php

/**
 * Class Tribe__Tickets_Plus__Ticket_Repository
 *
 * Extension of the base Ticket repository to take the types
 * provided by Event Tickets Plus into account.
 *
 * @since 4.8
 */
class Tribe__Tickets_Plus__Ticket_Repository extends Tribe__Tickets__Ticket_Repository {
	/**
	 * Returns an array of the ticket types handled by this repository.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function ticket_types() {
		$types = parent::ticket_types();
		// WooCommerce
		$types['woo'] = 'product';
		// Eeasy Digital Downloads
		$types['edd'] = 'download';

		return $types;
	}

	/**
	 * Returns the list of meta keys relating a Ticket to a Post (Event).
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function ticket_to_event_keys() {
		$keys = parent::ticket_to_event_keys();
		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_for_event';
		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_for_event';

		return $keys;
	}
}
