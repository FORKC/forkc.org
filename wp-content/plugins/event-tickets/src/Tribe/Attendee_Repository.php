<?php

/**
 * Class Tribe__Tickets__Attendee_Repository
 *
 * The basic Attendee repository.
 *
 * @since 4.8
 */
class Tribe__Tickets__Attendee_Repository extends Tribe__Repository {
	/**
	 * @var array An array of all the order statuses supported by the repository.
	 */
	protected static $order_statuses;

	/**
	 * @var array An array of all the public order statuses supported by the repository.
	 *            This list is hand compiled as reduced and easier to maintain.
	 */
	protected static $public_order_statuses = array(
		'yes',     // RSVP
		'completed', // PayPal
		'wc-completed', // WooCommerce
		'publish', // Easy Digital Downloads
	);

	/**
	 * @var array An array of all the private order statuses supported by the repository.
	 */
	protected static $private_order_statuses;

	/**
	 * Tribe__Tickets__Attendee_Repository constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->default_args = array_merge( $this->default_args, array(
			'post_type'   => $this->attendee_types(),
			'orderby'     => array( 'date', 'title', 'ID' ),
			'post_status' => 'any',
		) );
		$this->schema = array_merge( $this->schema, array(
			'event'             => array( $this, 'filter_by_event' ),
			'ticket'            => array( $this, 'filter_by_ticket' ),
			'event__not_in'     => array( $this, 'filter_by_event_not_in' ),
			'ticket__not_in'    => array( $this, 'filter_by_ticket_not_in' ),
			'optout'            => array( $this, 'filter_by_optout' ),
			'rsvp_status'       => array( $this, 'filter_by_rsvp_status' ),
			'provider'          => array( $this, 'filter_by_provider' ),
			'event_status'      => array( $this, 'filter_by_event_status' ),
			'order_status'      => array( $this, 'filter_by_order_status' ),
			'price_min'         => array( $this, 'filter_by_price_min' ),
			'price_max'         => array( $this, 'filter_by_price_max' ),
			'has_attendee_meta' => array( $this, 'filter_by_attendee_meta_existence' ),
			'checkedin'         => array( $this, 'filter_by_checkedin' ),
		) );

		$this->init_order_statuses();
	}

	/**
	 * Returns an array of the attendee types handled by this repository.
	 *
	 * Extending repository classes should override this to add more attendee types.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_types() {
		return array( 'tribe_rsvp_attendees', 'tribe_tpp_attendees' );
	}

	/**
	 * Provides arguments to filter attendees by a specific event.
	 *
	 * @since 4.8
	 *
	 * @param int|array $event_id A post ID or an array of post IDs.
	 *
	 * @return array
	 */
	public function filter_by_event( $event_id ) {
		return Tribe__Repository__Query_Filters::meta_in(
			$this->attendee_to_event_keys(),
			$event_id,
			'by-related-event'
		);
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_to_event_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_event',
			'tribe-commerce' => '_tribe_tpp_event',
		);
	}

	/**
	 * Provides arguments to get attendees that are not related to an event.
	 *
	 * @since 4.8
	 *
	 * @param int|array $event_id A post ID or an array of post IDs.
	 *
	 * @return array
	 */
	public function filter_by_event_not_in( $event_id ) {
		return Tribe__Repository__Query_Filters::meta_not_in(
			$this->attendee_to_event_keys(),
			$event_id,
			'by-event-not-in'
		);
	}

	/**
	 * Provides arguments to filter attendees by a specific ticket.
	 *
	 * @since 4.8
	 *
	 * @param int|array $ticket_id A ticket post ID or an array of ticket post IDs.
	 *
	 * @return array
	 */
	public function filter_by_ticket( $ticket_id ) {
		return Tribe__Repository__Query_Filters::meta_in(
			$this->attendee_to_ticket_keys(),
			$ticket_id,
			'by-ticket'
		);
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Ticket.
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_to_ticket_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_product',
			'tribe-commerce' => '_tribe_tpp_product',
		);
	}

	/**
	 * Provides arguments to get attendees that are not related to a ticket.
	 *
	 * @since 4.8
	 *
	 * @param int|array $ticket_id A ticket post ID or an array of ticket post IDs.
	 *
	 * @return array
	 */
	public function filter_by_ticket_not_in( $ticket_id ) {
		return Tribe__Repository__Query_Filters::meta_not_in(
			$this->attendee_to_ticket_keys(),
			$ticket_id,
			'by-ticket-not-in'
		);
	}

	/**
	 * Provides arguments to filter attendees by their optout status.
	 *
	 * @since 4.8
	 *
	 * @param string $optout An optout option, supported 'yes','no','any'.
	 *
	 * @return array|null
	 */
	public function filter_by_optout( $optout ) {
		$args = array(
			'meta_query' => array(
				'by-optout-status' => array(),
			),
		);

		switch ( $optout ) {
			case 'any':
				return null;
				break;
			case 'no':
				$this->by( 'meta_not_in', $this->attendee_optout_keys(), 'yes' );
				break;
			case'yes':
				$this->by( 'meta_in', $this->attendee_optout_keys(), 'yes' );
				break;
		}

		return null;
	}

	/**
	 * Returns the list of meta keys denoting an Attendee optout choice.
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_optout_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_attendee_optout',
			'tribe-commerce' => '_tribe_tpp_attendee_optout',
		);
	}

	/**
	 * Provides arguments to filter attendees by a specific RSVP status.
	 *
	 * Mind that we allow tickets not to have an RSVP status at all and
	 * still match. This assumes that all RSVP tickets will have a status
	 * assigned (which is the default behaviour).
	 *
	 * @since 4.8
	 *
	 * @param string $rsvp_status
	 *
	 * @return array
	 */
	public function filter_by_rsvp_status( $rsvp_status ) {
		return Tribe__Repository__Query_Filters::meta_in_or_not_exists(
			Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY,
			$rsvp_status,
			'by-rsvp-status'
		);
	}

	/**
	 * Provides arguments to filter attendees by the ticket provider.
	 *
	 * To avoid lengthy queries we check if a provider specific meta
	 * key relating the Attendee to the event (a post) is set.
	 *
	 * @since 4.8
	 *
	 * @param string|array $provider A provider supported slug or an
	 *                               array of supported provider slugs.
	 *
	 * @return array
	 */
	public function filter_by_provider( $provider ) {
		$providers = Tribe__Utils__Array::list_to_array( $provider );
		$meta_keys = Tribe__Utils__Array::map_or_discard( (array) $providers, $this->attendee_to_event_keys() );

		$this->by( 'meta_exists', $meta_keys );
	}

	/**
	 * Filters attendee to only get those related to posts with a specific status.
	 *
	 * @since 4.8
	 *
	 * @param string|array $event_status
	 *
	 * @throws Tribe__Repository__Void_Query_Exception If the requested statuses are not accessible by the user.
	 */
	public function filter_by_event_status( $event_status ) {
		$statuses = Tribe__Utils__Array::list_to_array( $event_status );

		$can_read_private_posts = current_user_can( 'read_private_posts' );

		// map the `any` meta-status
		if ( 1 === count( $statuses ) && 'any' === $statuses[0] ) {
			if ( ! $can_read_private_posts ) {
				$statuses = array( 'publish' );
			} else {
				// no need to filter if the user can read all posts
				return;
			}
		}

		if ( ! $can_read_private_posts ) {
			$event_status = array_intersect( $statuses, array( 'publish' ) );
		}

		if ( empty( $event_status ) ) {
			throw Tribe__Repository__Void_Query_Exception::because_the_query_would_yield_no_results(
				'The user cannot read posts with the requested post statuses.'
			);
		}

		$this->where_meta_related_by(
			$this->attendee_to_event_keys(),
			'IN',
			'post_status',
			$statuses
		);
	}

	/**
	 * Filters attendee to only get those related to orders with a specific status.
	 *
	 * @since 4.8
	 *
	 * @param string|array $order_status
	 *
	 * @throws Tribe__Repository__Void_Query_Exception If the requested statuses are not accessible by the user.
	 */
	public function filter_by_order_status( $order_status ) {
		$statuses = Tribe__Utils__Array::list_to_array( $order_status );

		$can_read_private_posts = current_user_can( 'read_private_posts' );

		// map the `any` meta-status
		if ( 1 === count( $statuses ) && 'any' === $statuses[0] ) {
			if ( ! $can_read_private_posts ) {
				$statuses = array( 'public' );
			} else {
				// no need to filter if the user can read all posts
				return;
			}
		}

		// Allow the user to define singular statuses or the meta-status "public"
		if ( in_array( 'public', $statuses, true ) ) {
			$statuses = array_unique( array_merge( $statuses, self::$public_order_statuses ) );
		}

		// Allow the user to define singular statuses or the meta-status "private"
		if ( in_array( 'private', $statuses, true ) ) {
			$statuses = array_unique( array_merge( $statuses, self::$private_order_statuses ) );
		}

		// Remove any status the user cannot access
		if ( ! $can_read_private_posts ) {
			$statuses = array_intersect( $statuses, self::$public_order_statuses );
		}

		if ( empty( $statuses ) ) {
			throw Tribe__Repository__Void_Query_Exception::because_the_query_would_yield_no_results(
				'The user cannot access the requested attendee order statuses.'
			);
		}

		/** @var wpdb $wpdb */
		global $wpdb;

		$statuses_in = "'" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "'";

		$has_plus_providers = class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' )
		                      || class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' );

		$this->filter_query->join( "LEFT JOIN {$wpdb->postmeta} order_status_meta "
		                           . "ON {$wpdb->posts}.ID = order_status_meta.post_id" );

		if ( ! $has_plus_providers ) {
			$this->filter_query->where( "order_status_meta.meta_key IN ( '_tribe_rsvp_status', '_tribe_tpp_status' ) "
			                            . "AND order_status_meta.meta_value IN ( {$statuses_in} )" );
		} else {
			$this->filter_query->join( "LEFT JOIN {$wpdb->posts} order_post "
			                           . "ON order_post.ID != {$wpdb->posts}.ID" );
			$this->filter_query->join( "LEFT JOIN {$wpdb->postmeta} attendee_to_order_meta "
			                           . 'ON attendee_to_order_meta.meta_value = order_post.ID' );
			$this->filter_query->where( "(
				(order_status_meta.meta_key IN ( '_tribe_rsvp_status', '_tribe_tpp_status' ) "
			                            . "AND order_status_meta.meta_value IN ( {$statuses_in} ))
				OR
				(
					attendee_to_order_meta.meta_key IN ( '_tribe_wooticket_order','_tribe_eddticket_order' )
					AND order_post.post_status IN ( {$statuses_in} )
				)
			)" );
		}
	}

	/**
	 * Filters Attendees by a minimum paid price.
	 *
	 * @since 4.8
	 *
	 * @param int $price_min
	 */
	public function filter_by_price_min( $price_min ) {
		$this->by( 'meta_gte', '_paid_price', (int) $price_min );
	}

	/**
	 * Filters Attendees by a maximum paid price.
	 *
	 * @since 4.8
	 *
	 * @param int $price_max
	 */
	public function filter_by_price_max( $price_max ) {
		$this->by( 'meta_lte', '_paid_price', (int) $price_max );
	}

	/**
	 * Filters attendee depending on them having additional
	 * information or not.
	 *
	 * @since 4.8
	 *
	 * @param bool $exists
	 */
	public function filter_by_attendee_meta_existence( $exists ) {
		if ( $exists ) {
			$this->by( 'meta_exists', '_tribe_tickets_meta' );
		} else {
			$this->by( 'meta_not_exists', '_tribe_tickets_meta' );
		}
	}

	/**
	 * Filters attendees depending on their checkedin status.
	 *
	 * @since 4.8
	 *
	 * @param bool $checkedin
	 *
	 * @return array
	 */
	public function filter_by_checkedin( $checkedin ) {
		$meta_keys = $this->checked_in_keys();

		if ( tribe_is_truthy( $checkedin ) ) {
			return Tribe__Repository__Query_Filters::meta_in( $meta_keys, '1', 'is-checked-in' );
		}

		return Tribe__Repository__Query_Filters::meta_not_in_or_not_exists( $meta_keys, '1', 'is-not-checked-in' );
	}

	/**
	 * Returns a list of meta keys indicating an attendee checkin status.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function checked_in_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_checkedin',
			'tribe-commerce' => '_tribe_tpp_checkedin',
		);
	}

	/**
	 * Returns a list of meta keys relating an attendee to the order
	 * that generated it.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	protected function attendee_to_order_keys() {
		return array(
			'tribe-commerce' => '_tribe_tpp_order',
		);
	}

	/**
	 * Bootstrap method called once per request to compile the available
	 * order statuses.
	 *
	 * @since 4.8
	 *
	 * @return bool|string
	 */
	protected function init_order_statuses() {
		if ( empty( self::$order_statuses ) ) {
			// For RSVP tickets the order status is the going status
			$statuses = array( 'yes', 'no' );

			if ( Tribe__Tickets__Commerce__PayPal__Main::get_instance()->is_active() ) {
				$statuses = array_merge( $statuses, Tribe__Tickets__Commerce__PayPal__Stati::all_statuses() );
			}

			if (
				class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' )
				&& function_exists( 'wc_get_order_statuses' )
			) {
				$statuses = array_merge( $statuses, wc_get_order_statuses() );
			}

			if (
				class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Main' )
				&& function_exists( 'edd_get_payment_statuses' )
			) {
				$statuses = array_merge( $statuses, array_keys( edd_get_payment_statuses() ) );
			}

			self::$order_statuses         = $statuses;
			self::$private_order_statuses = array_diff( $statuses, self::$public_order_statuses );
		}
	}
}
