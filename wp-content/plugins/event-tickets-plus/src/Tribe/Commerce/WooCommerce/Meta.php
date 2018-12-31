<?php

/**
 * Integration layer for WooCommerce and Custom Meta
 *
 * @since 4.1
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Meta {
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'save_attendee_meta_to_order' ), 5 );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_attendee_meta_to_order' ), 5 );
		add_action( 'event_tickets_woocommerce_ticket_created', array( $this, 'save_attendee_meta_to_ticket' ), 10, 4 );
	}

	/**
	 * Sets attendee data on order posts.
	 *
	 * @since 4.1
	 *
	 * @param int $order_id WooCommerce Order ID
	 */
	public function save_attendee_meta_to_order( $order_id ) {
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();

		// Bail if the order is empty
		if ( empty( $order_items ) ) {
			return;
		}

		$product_ids = array();

		// gather product ids
		foreach ( (array) $order_items as $item ) {
			$product_ids[] = isset( $item['product_id'] ) ? $item['product_id'] : $item['id'];
		}

		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		// build the custom meta data that will be stored in the order meta
		if ( ! $order_meta = $meta_object->build_order_meta( $product_ids ) ) {
			return;
		}

		// store the custom meta on the order
		update_post_meta( $order_id, Tribe__Tickets_Plus__Meta::META_KEY, $order_meta, true );

		// clear out product custom meta data cookies
		foreach ( $product_ids as $product_id ) {
			$meta_object->clear_meta_cookie_data( $product_id );
		}
	}

	/**
	 * Sets attendee data on attendee posts
	 *
	 * @since 4.1
	 *
	 * @param int $attendee_id Attendee Ticket Post ID
	 * @param int $order_id WooCommerce Order ID
	 * @param int $product_id WooCommerce Product ID
	 * @param int $order_attendee_id Attendee number in submitted order
	 */
	public function save_attendee_meta_to_ticket( $attendee_id, $order_id, $product_id, $order_attendee_id ) {
		$meta = get_post_meta( $order_id, Tribe__Tickets_Plus__Meta::META_KEY, true );

		if ( ! isset( $meta[ $product_id ] ) ) {
			return;
		}

		if ( ! isset( $meta[ $product_id ][ $order_attendee_id ] ) ) {
			return;
		}

		update_post_meta( $attendee_id, Tribe__Tickets_Plus__Meta::META_KEY, $meta[ $product_id ][ $order_attendee_id ] );
	}
}
