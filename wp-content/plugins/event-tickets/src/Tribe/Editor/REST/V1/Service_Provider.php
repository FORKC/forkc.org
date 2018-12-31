<?php

/**
 * Class Tribe__Tickets__Editor__REST__V1__Service_Provider
 *
 * Add support to: Add / Create / Delete tickets via the WP REST Api
 *
 * @since 4.9
 */
class Tribe__Tickets__Editor__REST__V1__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public $namespace;

	/**
	 * Registers the classes and functionality needed fro REST API
	 *
	 * @since 4.9
	 */
	public function register() {

		// Prevent to crash when even tickets is disabled
		if ( ! class_exists( 'Tribe__Tickets__REST__V1__Endpoints__Base' ) ) {
			return;
		}

		tribe_singleton(
			'tickets.editor.rest-v1.endpoints.tickets-single',
			new Tribe__Tickets__Editor__REST__V1__Endpoints__Single_Ticket(
				tribe( 'tickets.rest-v1.messages' ),
				tribe( 'tickets.rest-v1.repository' ),
				tribe( 'tickets.rest-v1.validator' )
			)
		);
		$this->hooks();
	}

	/**
	 * Hooks all the methods and actions the class needs.
	 *
	 * @since 4.9
	 */
	private function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_filter(
			'tribe_rest_single_ticket_data',
			array( $this, 'filter_single_ticket_data' ),
			10,
			2
		);
	}

	/**
	 * Registers the REST API endpoints for Event Tickets.
	 *
	 * @since 4.9
	 */
	public function register_endpoints() {
		$this->namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$this->register_single_ticket_endpoint();
		$this->register_ticket_archive_endpoint();
	}

	/**
	 * Registers the REST API endpoint that will handle single ticket requests, to edit and remove
	 * a ticket via the endpoint.
	 *
	 * @since 4.9
	 *
	 * @return Tribe__Tickets__REST__V1__Endpoints__Single_Ticket
	 */
	private function register_single_ticket_endpoint() {
		/** @var Tribe__Tickets__Editor__REST__V1__Endpoints__Single_ticket $endpoint */
		$endpoint = tribe( 'tickets.editor.rest-v1.endpoints.tickets-single' );
		register_rest_route( $this->namespace, '/tickets/(?P<id>\\d+)', array(
			array(
				'methods' => WP_REST_Server::EDITABLE,
				'args' => $endpoint->EDIT_args(),
				'callback' => array( $endpoint, 'update' ),
			),
			array(
				'methods' => WP_REST_Server::DELETABLE,
				'args' => $endpoint->DELETE_args(),
				'callback' => array( $endpoint, 'delete' ),
			),
		) );
		return $endpoint;
	}

	/**
	 * Registers the REST API endpoint that will handle ticket archive requests to create a new
	 * ticket inside of the site.
	 *
	 * @since 4.9
	 *
	 * @return Tribe__Tickets__REST__V1__Endpoints__Ticket_Archive
	 */
	private function register_ticket_archive_endpoint() {
		/** @var Tribe__Tickets__Editor__REST__V1__Endpoints__Single_ticket $endpoint */
		$endpoint = tribe( 'tickets.editor.rest-v1.endpoints.tickets-single' );
		register_rest_route( $this->namespace, '/tickets', array(
			'methods' => WP_REST_Server::CREATABLE,
			'args' => $endpoint->CREATE_args(),
			'callback' => array( $endpoint, 'create' ),
		) );
		return $endpoint;
	}

	/**
	 * Add additional data to the single Ticket API when reading data
	 *
	 * @since 4.9
	 *
	 * @param $data
	 * @param $request
	 * @return mixed
	 */
	public function filter_single_ticket_data( $data, $request ) {
		$ticket_id = $request['id'];
		$ticket = Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );

		if ( $ticket === null ) {
			return $data;
		}

		$capacity_details = empty( $data['capacity_details'] ) ? array() : $data['capacity_details'];
		$available = empty( $capacity_details['available'] ) ? 0 : $capacity_details['available'];
		$capacity_type = $ticket->global_stock_mode();

		// Check for unlimited types
		if ( $available === -1 || $capacity_type === '' ) {
			$capacity_type = 'unlimited';
		}

		$data['capacity_type'] = $capacity_type;
		$data['sku'] = $ticket->sku;
		$data['description'] = $ticket->description;
		$data['available_from_start_time'] = $ticket->start_time;
		$data['available_from_end_time'] = $ticket->end_time;

		$data['totals'] = tribe( 'tickets.handler' )->get_ticket_totals( $ticket_id );

		return $data;
	}
}
