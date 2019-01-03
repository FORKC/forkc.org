<?php

/**
 * Class Tribe__Tickets_Plus__Attendee_Repository
 *
 * Extension of the base Attendee repository to take the types
 * provided by Event Tickets Plus into account.
 *
 * @since 4.8
 */
class Tribe__Tickets_Plus__Attendee_Repository
	extends Tribe__Tickets__Attendee_Repository {
	public function __construct() {
		parent::__construct();
		$this->schema = array_merge( $this->schema, array(
			'woocommerce_order' => array( $this, 'filter_by_woocommerce_order' ),
			'edd_order'         => array( $this, 'filter_by_edd_order' ),
		) );
	}

	/**
	 * Returns an array of the attendee types handled by this repository.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_types() {
		$types = parent::attendee_types();
		// WooCommerce
		$types['woo'] = 'tribe_wooticket';
		// Easy Digital Downloads
		$types['edd'] = 'tribe_eddticket';

		return $types;
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Post (Event).
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_to_event_keys() {
		$keys = parent::attendee_to_event_keys();
		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_event';
		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_event';

		return $keys;
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Ticket.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_to_ticket_keys() {
		$keys = parent::attendee_to_ticket_keys();
		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_product';
		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_product';

		return $keys;
	}

	/**
	 * Returns the list of meta keys denoting an Attendee optout choice.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_optout_keys() {
		$keys = parent::attendee_optout_keys();
		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_attendee_optout';
		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_attendee_optout';

		return $keys;
	}

	/**
	 * Returns meta query arguments to filter attendees by a WooCommerce order ID.
	 *
	 * @since 4.8
	 *
	 * @param int $order A WooCommerce order post ID.
	 *
	 * @return array
	 */
	public function filter_by_woocommerce_order( $order ) {
		return Tribe__Repository__Query_Filters::meta_in(
			'_tribe_wooticket_order',
			$order,
			'by-woocommerce-order'
		);
	}

	/**
	 * Returns meta query arguments to filter attendees by an Easy Digital Downloads order ID.
	 *
	 * @since 4.8
	 *
	 * @param int $order An Easy Digital Downloads order post ID.
	 *
	 * @return array
	 */
	public function filter_by_edd_order( $order ) {
		return Tribe__Repository__Query_Filters::meta_in(
			'_tribe_eddticket_order',
			$order,
			'by-edd-order'
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function checked_in_keys() {
		$keys = parent::checked_in_keys();
		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_checkedin';
		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_checkedin';

		return $keys;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function attendee_to_order_keys() {
		$keys = parent::attendee_to_order_keys();
		// WooCommerce
		$keys['woo'] = '_tribe_wooticket_order';
		// Easy Digital Downloads
		$keys['edd'] = '_tribe_eddticket_order';

		return $keys;
	}
}
