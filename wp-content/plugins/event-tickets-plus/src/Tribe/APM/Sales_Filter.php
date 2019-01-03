<?php


class Tribe__Tickets_Plus__APM__Sales_Filter extends Tribe__Tickets_Plus__APM__Abstract_Filter {

	/**
	 * @var string
	 */
	protected $type = 'custom_ticket_sales';

	/**
	 * @var string
	 */
	public static $key = 'tickets_plus_sales_filter_key';

	/**
	 * Sets up the `$query_search_options` field.
	 */
	protected function set_up_query_search_options() {
		$this->query_search_options = array(
			'is'  => __( 'Are', 'event-tickets-plus' ),
			'not' => __( 'Are Not', 'event-tickets-plus' ),
			'gte' => __( 'Are at least', 'event-tickets-plus' ),
			'lte' => __( 'Are at most', 'event-tickets-plus' ),
		);
	}

	/**
	 * Returns the filter identifying key.
	 *
	 * Workaround for missing late static binding.
	 *
	 * @return mixed
	 */
	protected function key() {
		return self::$key;
	}

	/**
	 * Returns the total numeric value of an event meta.
	 *
	 * E.g. the total tickets sales, stock.
	 *
	 * @param WP_Post $event
	 *
	 * @return int|WP_Error
	 */
	public function get_total_value( $event ) {
		$event_post           = get_post( $event );
		$supported_post_types = Tribe__Tickets__Main::instance()->post_types();
		if ( empty( $event_post ) || ! in_array( $event_post->post_type, $supported_post_types ) ) {
			return new WP_Error( 'not-an-event', sprintf( 'The post with ID "%s" is not an event.', $event_post->ID ) );
		}

		$sum = 0;

		$all_tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $event_post->ID );
		/** @var Tribe__Tickets__Ticket_Object $ticket */
		foreach ( $all_tickets as $ticket ) {
			$sum += $ticket->qty_sold();
		}

		// return the sum
		return $sum;
	}

}