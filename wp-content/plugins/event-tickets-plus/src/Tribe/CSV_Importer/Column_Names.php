<?php


class Tribe__Tickets_Plus__CSV_Importer__Column_Names {

	/**
	 * @var self
	 */
	protected static $instance;
	/**
	 * @var Tribe__Tickets_Plus__Commerce__Loader
	 */
	protected $commerce_loader;

	/**
	 * The class singleton constructor.
	 *
	 * @return Tribe__Tickets_Plus__CSV_Importer__Column_Names
	 */
	public static function instance( Tribe__Tickets_Plus__Commerce__Loader $commerce_loader ) {
		if ( empty( self::$instance ) ) {
			self::$instance = new self( $commerce_loader );
		}

		return self::$instance;
	}

	/**
	 * Tribe__Tickets_Plus__CSV_Importer__Column_Names constructor.
	 *
	 * @param Tribe__Tickets_Plus__Commerce__Loader $commerce_loader
	 */
	public function __construct( Tribe__Tickets_Plus__Commerce__Loader $commerce_loader ) {
		$this->commerce_loader = $commerce_loader;
	}

	/**
	 * Adds RSVP column names to the importer mapping options.
	 *
	 * @param array $column_names
	 *
	 * @return array
	 */
	public function filter_tickets_woo_column_names( array $column_names ) {
		$column_names = array_merge( $column_names, array(
			'event_name'              => esc_html__( 'Event Name or ID or Slug', 'event-tickets-plus' ),
			'ticket_name'             => esc_html__( 'Ticket Name', 'event-tickets-plus' ),
			'ticket_description'      => esc_html__( 'Ticket Description', 'event-tickets-plus' ),
			'ticket_show_description' => esc_html__( 'Ticket Show Description', 'event-tickets-plus' ),
			'ticket_start_sale_date'  => esc_html__( 'Ticket Start Sale Date', 'event-tickets-plus' ),
			'ticket_start_sale_time'  => esc_html__( 'Ticket Start Sale Time', 'event-tickets-plus' ),
			'ticket_end_sale_date'    => esc_html__( 'Ticket End Sale Date', 'event-tickets-plus' ),
			'ticket_end_sale_time'    => esc_html__( 'Ticket End Sale Time', 'event-tickets-plus' ),
			'ticket_price'            => esc_html__( 'Ticket Price', 'event-tickets-plus' ),
			'ticket_stock'            => esc_html__( 'Ticket Stock', 'event-tickets-plus' ),
			'ticket_sku'              => esc_html__( 'Ticket SKU', 'event-tickets-plus' ),
			'ticket_capacity'         => esc_html__( 'Ticket Capacity', 'event-tickets-plus' ),
		) );

		return $column_names;
	}

	/**
	 * @param array $map
	 *
	 * @return array
	 */
	public function filter_import_type_titles_map( array $map ) {
		if ( $this->commerce_loader->is_woocommerce_active() ) {
			$map['tickets_woo'] = __( 'Tickets', 'event-tickets-plus' );
		}

		return $map;
	}
}