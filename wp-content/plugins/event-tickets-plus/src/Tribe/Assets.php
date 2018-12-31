<?php
class Tribe__Tickets_Plus__Assets {
	/**
	 * Enqueue scripts for front end
	 *
	 * @since 4.6
	 */
	public function enqueue_scripts() {
		// Set up our base list of enqueues
		$enqueue_array = array(
			array( 'event-tickets-plus-tickets-css', 'tickets.css', array( 'dashicons' ) ),
			array( 'jquery-deparam', 'vendor/jquery.deparam/jquery.deparam.js', array( 'jquery' ) ),
			array( 'jquery-cookie', 'vendor/jquery.cookie/jquery.cookie.js', array( 'jquery' ) ),
			array( 'event-tickets-plus-attendees-list-js', 'attendees-list.js', array( 'event-tickets-attendees-list-js' ) ),
			array( 'event-tickets-plus-meta-js', 'meta.js', array( 'jquery-cookie', 'jquery-deparam' ) ),
		);

		// and the engine...
		tribe_assets(
			tribe( 'tickets-plus.main' ),
			$enqueue_array,
			'wp_enqueue_scripts'
		);
	}

	/**
	 * Enqueue scripts for admin views
	 *
	 * @since 4.6
	 */
	public function admin_enqueue_scripts() {
		// Set up our base list of enqueues
		$enqueue_array = array(
			array( 'event-tickets-plus-meta-admin-css', 'meta.css', array() ),
			array( 'event-tickets-plus-meta-report-js', 'meta-report.js', array() ),
			array( 'event-tickets-plus-attendees-list-js', 'attendees-list.js', array( 'event-tickets-attendees-list-js' ) ),
			array( 'event-tickets-plus-meta-admin-js', 'meta-admin.js', array( 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-sortable' ) ),
			array( 'event-tickets-plus-admin-css', 'admin.css', array( 'event-tickets-admin-css' ) ),
			array( 'event-tickets-plus-admin-tables-js', 'tickets-tables.js', array( 'underscore', 'jquery', 'tribe-common' ) ),
			array( 'event-tickets-plus-admin-qr', 'qr.js', array( 'jquery' ) ),
		);

		/**
		 * Filter the array of module names.
		 *
		 * @since 4.6
		 *
		 * @param array the array of modules
		 */
		$modules = Tribe__Tickets__Tickets::modules();
		$modules = array_values( $modules );

		if ( in_array( 'WooCommerce', $modules )  ) {
			$enqueue_array[] = array( 'event-tickets-plus-wootickets-css', 'wootickets.css', array( 'event-tickets-plus-meta-admin-css' ) );
		}

		// and the engine...
		tribe_assets(
			tribe( 'tickets-plus.main' ),
			$enqueue_array,
			'admin_enqueue_scripts',
			array(
				'priority' => 0,
				'localize' => (object) array(
					'name' => 'tribe_qr',
					'data' => array(
						'generate_qr_nonce'   => wp_create_nonce( 'generate_qr_nonce' ),
					),
				),
			)
		);
	}
}
