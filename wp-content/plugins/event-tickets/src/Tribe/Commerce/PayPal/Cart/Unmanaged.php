<?php
/**
 * Class Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged
 *
 * Models a transitional, not managed, cart implementation; cart management functionality
 * is offloaded to PayPal.
 *
 * @since 4.7.3
 */
class Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged implements Tribe__Tickets__Commerce__PayPal__Cart__Interface {

	/**
	 * @var string The invoice number for this cart.
	 */
	protected $invoice_number;

	/**
	 * @var array
	 */
	protected $items = array();

	/**
	 * {@inheritdoc}
	 */
	public function set_id( $id ) {
		$this->invoice_number = $id;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save() {
		if ( ! $this->has_items() ) {
			return;
		}

		set_transient( self::get_transient_name( $this->invoice_number ), $this->items, 900 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_items() {
		if ( null === $this->invoice_number ) {
			if ( ! $this->exists() ) {
				return false;
			}

			$invoice_number = $this->read_invoice_number();

			$transient = (array) get_transient( self::get_transient_name( $invoice_number ) );

			return count( array_filter( $transient ) );
		}

		return count( array_filter( $this->items ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists( array $criteria = array() ) {
		if ( null !== $this->invoice_number ) {
			$invoice_number = $this->invoice_number;
		} else {
			$invoice_number = $this->read_invoice_number();
		}

		if ( false === $invoice_number ) {
			return false;
		}

		return (bool) get_transient( self::get_transient_name( $invoice_number ) );
	}

	/**
	 * Reads the invoice number from the invoice cookie.
	 *
	 * @since 4.7.3
	 *
	 * @return string|bool The invoice number or `false` if not found.
	 *
	 * @see Tribe__Tickets__Commerce__PayPal__Gateway::set_invoice_number()
	 */
	protected function read_invoice_number() {
		return Tribe__Utils__Array::get(
			$_COOKIE, Tribe__Tickets__Commerce__PayPal__Gateway::$invoice_cookie_name,
			false
		);
	}

	/**
	 * Returns the name of the transient used by the cart.
	 *
	 * @since 4.7.3
	 *
	 * @param string $invoice_number
	 *
	 * @return string
	 */
	public static function get_transient_name( $invoice_number ) {
		return 'tpp_cart_' . md5( $invoice_number );
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear() {
		if ( null === $this->invoice_number ) {
			return;
		}

		delete_transient( self::get_transient_name( $this->invoice_number ) );

	}

	/**
	 * {@inheritdoc}
	 */
	public function has_item( $item_id ) {
		if ( null === $this->invoice_number ) {
			if ( ! $this->exists() ) {
				return false;
			}

			$invoice_number = $this->read_invoice_number();

			$items = (array) get_transient( self::get_transient_name( $invoice_number ) );
		} else {
			$items = $this->items;
		}

		return ! empty( $items[ $item_id ] ) ? (int) $items[ $item_id ] : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function remove_item( $item_id, $quantity ) {
		$this->add_item( $item_id, - abs( (int) $quantity ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function add_item( $item_id, $quantity ) {
		$this->items[ $item_id ] = isset( $this->items[ $item_id ] )
			? $this->items[ $item_id ] + (int) $quantity
			: (int) $quantity;

		$this->items[ $item_id ] = max( $this->items[ $item_id ], 0 );
	}
}
