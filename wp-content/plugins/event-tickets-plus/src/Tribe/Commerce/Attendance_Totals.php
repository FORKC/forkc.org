<?php
/**
 * Calculates attendance totals for a specified event (ie, how many tickets
 * have been sold and how many are pending further action, etc).
 *
 * Also has the capability to print this information as HTML, intended for
 * use in the attendee summary screen.
 *
 * Note that the totals are calculated upon instantiation, effectively making
 * the object a snapshot in time. Therefore if the status of one or more tickets
 * is modified or if tickets are added/deleted later in the request, it would be
 * necessary to obtain a new object of this type to get accurate results.
 */
class Tribe__Tickets_Plus__Commerce__Attendance_Totals extends Tribe__Tickets__Abstract_Attendance_Totals {
	protected $total_sold = 0;
	protected $total_paid = 0;
	protected $total_pending = 0;

	/**
	 * Calculate totals for the current event.
	 */
	protected function calculate_totals() {
		foreach ( Tribe__Tickets__Tickets::get_event_tickets( $this->event_id ) as $ticket ) {
			if ( ! $this->should_count( $ticket ) ) {
				continue;
			}

			$this->total_paid += $ticket->qty_sold();
			$this->total_pending += $ticket->qty_pending();
		}

		$this->total_sold = $this->total_paid + $this->total_pending;
	}

	/**
	 * Indicates if the ticket should be factored into our sales counts.
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return bool
	 */
	protected function should_count( Tribe__Tickets__Ticket_Object $ticket ) {
		$should_count = 'Tribe__Tickets__RSVP' !== $ticket->provider_class;

		/**
		 * Determine if the provided ticket object should be used when building
		 * sales counts.
		 *
		 * By default, tickets belonging to the Tribe__Tickets__RSVP provider
		 * are not to be counted.
		 *
		 * @param bool $should_count
		 * @param Tribe__Tickets__Ticket_Object $ticket
		 */
		return (bool) apply_filters( 'tribe_tickets_plus_should_use_ticket_in_sales_counts', $should_count, $ticket );
	}

	/**
	 * Prints an HTML (unordered) list of attendance totals.
	 */
	public function print_totals() {
		$total_sold_label = esc_html_x( 'Total Tickets Issued:', 'attendee summary', 'event-tickets-plus' );
		$total_paid_label = esc_html_x( 'Complete:', 'attendee summary', 'event-tickets-plus' );

		$total_sold = $this->get_total_sold();
		$total_paid = $this->get_total_paid();

		echo "
			<ul>
				<li> <strong>$total_sold_label</strong> $total_sold </li>
				<li> $total_paid_label $total_paid </li>
			</ul>
		";
	}

	/**
	 * The total number of tickets sold for this event.
	 *
	 * @return int
	 */
	public function get_total_sold() {
		/**
		 * Returns the total tickets sold for an event.
		 *
		 * @param int $total_sold
		 * @param int $original_total_sold
		 * @param int $event_id
		 */
		return (int) apply_filters( 'tribe_tickets_plus_get_total_sold', $this->total_sold, $this->total_sold, $this->event_id );
	}

	/**
	 * The total number of tickets pending further action for this event.
	 *
	 * @return int
	 */
	public function get_total_pending() {
		/**
		 * Returns the total tickets pending further action for an event.
		 *
		 * @param int $total_pending
		 * @param int $original_total_pending
		 * @param int $event_id
		 */
		return (int) apply_filters( 'tribe_tickets_plus_get_total_pending', $this->total_pending, $this->total_pending, $this->event_id );
	}

	/**
	 * The total number of tickets sold and paid for, for this event.
	 *
	 * @deprecated 4.6
	 *
	 * @return int
	 */
	public function get_total_complete() {
		return $this->get_total_paid();
	}

	/**
	 * The total number of tickets sold and paid for, for this event.
	 *
	 * @since  4.6
	 *
	 * @return int
	 */
	public function get_total_paid() {
		/**
		 * Returns the total tickets sold and paid for, for an event.
		 *
		 * @param int $total_paid
		 * @param int $original_total_complete
		 * @param int $event_id
		 */
		return (int) apply_filters( 'tribe_tickets_plus_get_total_paid', $this->total_paid, $this->total_paid, $this->event_id );
	}
}