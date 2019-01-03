<?php


/**
 * Class Tribe__Tickets_Plus__Meta__Storage
 *
 * Handles CRUD operations of attendee meta temporary storage.
 *
 * @since 4.2.6
 */
class Tribe__Tickets_Plus__Meta__Storage {

	/**
	 * The index used to store attendee meta information in the $_POST global.
	 */
	const META_DATA_KEY = 'tribe-tickets-meta';

	/**
	 * The prefix prepended to the transient created to store the ticket meta
	 * information; an hash will be appended to it.
	 */
	const TRANSIENT_PREFIX = 'tribe_tickets_meta_';

	/**
	 * The name of the cookie storing the hash of the transient storing the ticket meta.
	 */
	const HASH_COOKIE_KEY = 'tribe-event-tickets-plus-meta-hash';

	/**
	 * @var array
	 */
	protected $data_cache = array();

	/**
	 * The time in seconds after which the ticket meta transient will expire.
	 *
	 * Defaults to a day.
	 *
	 * @var int
	 */
	protected $ticket_meta_expire_time = 86400;

	/**
	 * A flag to prevent maybe_update_ticket_meta_cookie from running more than necessary.
	 *
	 * This is required because we only want to update the ticket meta cookie once per request,
	 * however multiple objects of this type may be created (once by the RSVP provider, for
	 * example, and once by the WooCommerce provider).
	 *
	 * @var boolean
	 */
	private static $has_updated_meta_cookie = false;

	/**
	 * Sets or updates the attendee meta cookies and returns the name
	 * of the transient storing them.
	 *
	 * @return string
	 */
	public function maybe_set_attendee_meta_cookie() {
		$empty_or_wrong_format = empty( $_POST[ self::META_DATA_KEY ] ) || ! is_array( $_POST[ self::META_DATA_KEY ] );
		if ( $empty_or_wrong_format ) {
			return false;
		}

		$cookie_set = ! empty( $_COOKIE[ self::HASH_COOKIE_KEY ] );
		if ( $cookie_set ) {
			$set = $this->maybe_update_ticket_meta_cookie();
		} else {
			$set = $this->set_ticket_meta_cookie();
		}

		return $set;
	}

	/**
	 * Sets the ticket meta cookie.
	 *
	 * @return string|bool The transient hash or `false` if the transient setting
	 *                     failed.
	 */
	protected function set_ticket_meta_cookie() {
		$id          = uniqid();
		$transient   = self::TRANSIENT_PREFIX . $id;
		$ticket_meta = $_POST[ self::META_DATA_KEY ];
		$set         = set_transient( $transient, $ticket_meta, $this->ticket_meta_expire_time );

		if ( ! $set ) {
			return false;
		}

		$this->set_hash_cookie( $id );

		if ( isset( $_POST[ 'wootickets_process' ] ) ) {
			$this->set_woocommerce_hash_session( $id );
		}

		return $id;
	}

	/**
	 * Create a transient to store the attendee meta information if not set already.
	 *
	 * @return string|bool The transient hash or `false` if the cookie setting
	 *                     was not needed or failed.
	 */
	private function maybe_update_ticket_meta_cookie() {
		$id = $_COOKIE[ self::HASH_COOKIE_KEY ];

		/**
		 * Allows for the "has updated meta cookie" flag to be manually overriden.
		 *
		 * @since 4.5.6
		 *
		 * @param boolean $has_updated_meta_cookie
		 */
		if ( apply_filters( 'tribe_tickets_plus_meta_cookie_flag', self::$has_updated_meta_cookie ) ) {
		    return $id;
		} else {
		    self::$has_updated_meta_cookie = true;
		}

		$transient   = self::TRANSIENT_PREFIX . $id;
		$ticket_meta = $_POST[ self::META_DATA_KEY ];

		$stored_ticket_meta = get_transient( $transient );

		// Prevents Catchable Fatal when it doesn't exist or is a scalar
		if ( empty( $stored_ticket_meta ) || is_scalar( $stored_ticket_meta ) ) {
			$stored_ticket_meta = array();
		}

		delete_transient( $transient );
		$merged = $this->combine_new_and_saved_attendee_meta( $ticket_meta, $stored_ticket_meta );

		$set = set_transient( $transient, $merged, $this->ticket_meta_expire_time );

		if ( ! $set ) {
			return false;
		}

		return $id;
	}

	/**
	 * Sets the transient hash id in a cookie.
	 *
	 * @param $transient_id
	 */
	protected function set_hash_cookie( $transient_id ) {
		setcookie( self::HASH_COOKIE_KEY, $transient_id, 0, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl() );
		$_COOKIE[ self::HASH_COOKIE_KEY ] = $transient_id;
	}

	/**
	 * Gets the transient hash id from a cookie.
	 *
	 * @since 4.9
	 *
	 * @param $transient_id
	 *
	 * @return string
	 */
	public function get_hash_cookie() {
		return $_COOKIE[ self::HASH_COOKIE_KEY ];
	}

	/**
	 * Adds attendee meta from currently-being-bought tickets to tickets that are already in the cart.
	 *
	 * @since 4.5.6
	 *
	 * @param array $new The attendee meta data that's not yet been saved.
	 * @param array $saved The existing attendee data storied in cookies/transients.
	 *
	 * @return array
	 */
	protected function combine_new_and_saved_attendee_meta( $new, $saved ) {

		if ( empty( $saved ) ) {
			return $new;
		}

		foreach ( $new as $ticket_id => $data ) {

			$data = array_values( $data );

			if ( isset( $saved[ $ticket_id ] ) && $saved[ $ticket_id ] !== $new[ $ticket_id ] ) {
				// If there's already stored attendee meta for this ticket, add some more meta to that existing entry.
				foreach ( $data as $meta_id => $meta_data ) {
					$saved[ $ticket_id ][ $meta_id ] = $meta_data;
				}
			} else {
				// Otherwise we've got a ticket for which there's no stored data yet, so just add a new entry in the data array.
				$saved[ $ticket_id ] = $data;
			}
		}

		return $saved;
	}

	/**
	 * Sets the transient hash id in a WooCommerce Session.
	 *
	 * @param $transient_id
	 */
	protected function set_woocommerce_hash_session( $transient_id ) {
		WC()->session->set( self::HASH_COOKIE_KEY, $transient_id );
	}

	/**
	 * Store temporary data as a transient.
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public function store_temporary_data( $value ) {
		$id            = uniqid();
		$transient_key = self::TRANSIENT_PREFIX . '_' . $id;
		set_transient( $transient_key, $value, $this->ticket_meta_expire_time );

		return $id;
	}

	/**
	 * Retrieve temporary data from a transient.
	 *
	 * @param $transient_key
	 *
	 * @return array|mixed
	 */
	public function retrieve_temporary_data( $transient_key ) {
		$value = get_transient( self::TRANSIENT_PREFIX . '_' . $transient_key );

		return empty( $value ) ? array() : $value;
	}

	public function get_meta_data( $id = null ) {
		// determine transient id from cookie or WooCommerce session
		$transient_id = '';

		if ( isset( $_COOKIE[ self::HASH_COOKIE_KEY ] ) ) {
			$transient_id = $_COOKIE[ self::HASH_COOKIE_KEY ];
		}

		if ( ! $transient_id && 'product' === get_post_type( $id ) && ! is_admin() ) {
			$wc_session   = WC()->session;

			if ( empty( $wc_session ) ) {
				return array();
			}

			$transient_id = $wc_session->get( self::HASH_COOKIE_KEY );
		}

		if ( ! $transient_id ) {
			return array();
		}

		$transient = self::TRANSIENT_PREFIX . $transient_id;

		return get_transient( $transient );
	}

	/**
	 * Gets the ticket data associated to a specified ticket.
	 *
	 * @param int $id
	 *
	 * @return array|mixed Either the data stored for the specified id
	 *                     or an empty array.
	 */
	public function get_meta_data_for( $id ) {
		if ( isset( $this->data_cache[ $id ] ) ) {
			return $this->data_cache[ $id ];
		}

		$data = $this->get_meta_data( $id );

		if ( ! isset( $data[ intval( $id ) ] ) ) {
			return array();
		}

		$data = array( $id => $data[ $id ] );

		$this->data_cache[ $id ] = $data;

		return $data;
	}

	/**
	 * Clears the stored data associated with a ticket.
	 *
	 * @param int $id A ticket ID
	 *
	 * @return bool Whether the data for the specified ID was stored and cleared; `false`
	 *              otherwise.
	 */
	public function clear_meta_data_for( $id ) {
		if ( empty( $_COOKIE[ self::HASH_COOKIE_KEY ] ) ) {
			return false;
		}

		$transient = self::TRANSIENT_PREFIX . $_COOKIE[ self::HASH_COOKIE_KEY ];
		$data      = get_transient( $transient );

		if ( empty( $data ) ) {
			return false;
		}

		if ( ! isset( $data[ $id ] ) ) {
			return false;
		}

		unset( $data[ $id ] );

		if ( empty( $data ) ) {
			delete_transient( $transient );
			$this->delete_cookie();
			$this->delete_woocommerce_session( $id );
		} else {
			set_transient( $transient, $data, $this->ticket_meta_expire_time );
		}

		return true;
	}

	/**
	 * Deletes the cookie storing the transient hash
	 */
	public function delete_cookie() {
		setcookie( self::HASH_COOKIE_KEY, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl() );
		unset( $_COOKIE[ self::HASH_COOKIE_KEY ] );
	}

	/**
	 * Deletes the WooCommerce session storing the transient hash
	 *
	 * @param int $id A ticket ID
	 */
	protected function delete_woocommerce_session( $id ) {
		$session = function_exists( 'WC' ) ? WC()->session : null;
		$valid_instance = $session && $session instanceof WC_Session;
		if ( 'product' === get_post_type( $id ) && $valid_instance ) {
			$session->__unset( self::HASH_COOKIE_KEY );
		}
	}
}
