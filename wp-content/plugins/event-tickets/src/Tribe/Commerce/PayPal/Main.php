<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Main
 *
 * Logic for tribe commerce PayPal tickets
 *
 * @since 4.7
 */
class Tribe__Tickets__Commerce__PayPal__Main extends Tribe__Tickets__Tickets {
	/**
	 * Name of the CPT that holds Attendees (tickets holders).
	 *
	 * @var string
	 */
	const ATTENDEE_OBJECT = 'tribe_tpp_attendees';

	/**
	 * Name of the CPT that holds Orders
	 */
	const ORDER_OBJECT = 'tribe_tpp_orders';

	/**
	 * Meta key that relates Attendees and Events.
	 *
	 * @var string
	 */
	const ATTENDEE_EVENT_KEY = '_tribe_tpp_event';

	/**
	 * Meta key that relates Attendees and Products.
	 *
	 * @var string
	 */
	const ATTENDEE_PRODUCT_KEY = '_tribe_tpp_product';

	/**
	 * Meta key that relates Attendees and Orders.
	 *
	 * @var string
	 */
	const ATTENDEE_ORDER_KEY = '_tribe_tpp_order';

	/**
	 * Indicates if a ticket for this attendee was sent out via email.
	 *
	 * @var boolean
	 */
	public $attendee_ticket_sent = '_tribe_tpp_attendee_ticket_sent';

	/**
	 * Meta key that if this attendee wants to show on the attendee list
	 *
	 * @var string
	 */
	public $attendee_optout_key = '_tribe_tpp_attendee_optout';

	/**
	 * Meta key that if this attendee PayPal status
	 *
	 * @var string
	 */
	public $attendee_tpp_key = '_tribe_tpp_status';

	/**
	 *Name of the CPT that holds Tickets
	 *
	 * @var string
	 */
	public $ticket_object = 'tribe_tpp_tickets';

	/**
	 * Meta key that relates Products and Events
	 * @var string
	 */
	public $event_key = '_tribe_tpp_for_event';

	/**
	 * Meta key that stores if an attendee has checked in to an event
	 * @var string
	 */
	public $checkin_key = '_tribe_tpp_checkedin';

	/**
	 * Meta key that ties attendees together by order
	 * @var string
	 */
	public $order_key = '_tribe_tpp_order';

	/**
	 * Meta key that ties attendees together by refunded order
	 * @var string
	 */
	public $refund_order_key = '_tribe_tpp_refund_order';

	/**
	 * Meta key that holds the security code that's printed in the tickets
	 * @var string
	 */
	public $security_code = '_tribe_tpp_security_code';

	/**
	 * Meta key that holds the full name of the tickets PayPal "buyer"
	 *
	 * @var string
	 */
	public $full_name = '_tribe_tpp_full_name';

	/**
	 * Meta key that holds the email of the tickets PayPal "buyer"
	 *
	 * @var string
	 */
	public $email = '_tribe_tpp_email';

	/**
	 * Meta key that holds the name of a ticket to be used in reports if the Product is deleted
	 * @var string
	 */
	public $deleted_product = '_tribe_deleted_product_name';

	/**
	 * @var array An array cache to store pending attendees per ticket.
	 */
	public $pending_attendees_by_ticket = array();

	/**
	 * @var bool Whether pending stock logic should be ignored or not no matter the Settings.
	 *           This is an internal property. Use the `tribe_tickets_tpp_pending_stock_ignore`
	 *           filter or the accessor method to manipulate this value from another class.
	 */
	protected $ignore_pending_stock_logic = false;

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Attendance_Totals
	 */
	protected $attendance_totals;

	/**
	 * Messages for submission
	 */
	protected static $messages = array();

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Tickets_View
	 */
	protected $tickets_view;

	/**
	 * A variable holder if PayPal is loaded
	 * @var boolean
	 */
	protected $is_loaded = false;

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @since 4.7
	 *
	 * @static
	 * @return Tribe__Tickets__Commerce__PayPal__Main
	 */
	public static function get_instance() {
		return tribe( 'tickets.commerce.paypal' );
	}

	/**
	 * Class constructor
	 *
	 * @since 4.7
	 */
	public function __construct() {
		$main = Tribe__Tickets__Main::instance();

		/* Set up some parent's vars */
		$this->plugin_name = _x( 'Tribe Commerce', 'ticket provider', 'event-tickets' );
		$this->plugin_path = $main->plugin_path;
		$this->plugin_url  = $main->plugin_url;

		// mirror some properties from the class constants
		$this->attendee_event_key   = self::ATTENDEE_EVENT_KEY;
		$this->attendee_product_key = self::ATTENDEE_PRODUCT_KEY;
		$this->attendee_object      = self::ATTENDEE_OBJECT;

		parent::__construct();

		$this->bind_implementations();

		if ( ! $this->is_active() ) {
			unset( parent::$active_modules['Tribe__Tickets__Commerce__PayPal__Main'] );
		}

		$this->tickets_view = tribe( 'tickets.commerce.paypal.view' );

		$this->register_resources();
		$this->hooks();

		$this->is_loaded = true;
	}

	/**
	 * Whether PayPal tickets will be available as a provider or not.
	 *
	 * This will take into account the enable/disable option and the
	 * configuration status of the current payment handler (IPN or PDT).
	 *
	 * @since 4.7
	 *
	 * @return bool
	 */
	public function is_active() {
		/**
		 * Filters the check for the active status of the PayPal tickets module.
		 *
		 * Returning a non `null` value in this filter will override the default checks.
		 *
		 * @since 4.7
		 *
		 * @param bool                                   $is_active
		 * @param Tribe__Tickets__Commerce__PayPal__Main $this
		 */
		$is_active = apply_filters( 'tribe_tickets_commerce_paypal_is_active', null, $this );

		if ( null !== $is_active ) {
			return (bool) $is_active;
		}

		/** @var Tribe__Tickets__Commerce__PayPal__Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );
		/** @var Tribe__Tickets__Commerce__PayPal__Handler__Interface $handler */
		$handler = $gateway->build_handler();

		return tribe_is_truthy( tribe_get_option( 'ticket-paypal-enable', false ) )
		       && 'complete' === $handler->get_config_status();
	}

	/**
	 * Registers the implementations in the container
	 *
	 * @since 4.7
	 */
	public function bind_implementations() {
		// some classes will require an instance of this class as a dependency so we alias it here
		tribe_singleton( 'Tribe__Tickets__Commerce__PayPal__Main', $this );

		tribe_singleton( 'tickets.commerce.paypal.view', 'Tribe__Tickets__Commerce__PayPal__Tickets_View' );
		tribe_singleton( 'tickets.commerce.paypal.handler.ipn', 'Tribe__Tickets__Commerce__PayPal__Handler__IPN', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.handler.pdt', 'Tribe__Tickets__Commerce__PayPal__Handler__PDT', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.gateway', 'Tribe__Tickets__Commerce__PayPal__Gateway', array( 'hook', 'build_handler' ) );
		tribe_singleton( 'tickets.commerce.paypal.notices', 'Tribe__Tickets__Commerce__PayPal__Notices' );
		tribe_singleton( 'tickets.commerce.paypal.endpoints', 'Tribe__Tickets__Commerce__PayPal__Endpoints', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.endpoints.templates.success', 'Tribe__Tickets__Commerce__PayPal__Endpoints__Success_Template' );
		tribe_singleton( 'tickets.commerce.paypal.orders.tabbed-view', 'Tribe__Tickets__Commerce__Orders_Tabbed_View' );
		tribe_singleton( 'tickets.commerce.paypal.orders.report', 'Tribe__Tickets__Commerce__PayPal__Orders__Report', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.orders.sales', 'Tribe__Tickets__Commerce__PayPal__Orders__Sales' );
		tribe_singleton( 'tickets.commerce.paypal.screen-options', 'Tribe__Tickets__Commerce__PayPal__Screen_Options', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.stati', 'Tribe__Tickets__Commerce__PayPal__Stati' );
		tribe_singleton( 'tickets.commerce.paypal.currency', 'Tribe__Tickets__Commerce__Currency', array( 'hook' ) );
		tribe_singleton( 'tickets.commerce.paypal.links', 'Tribe__Tickets__Commerce__PayPal__Links' );
		tribe_singleton( 'tickets.commerce.paypal.oversell.policies', 'Tribe__Tickets__Commerce__PayPal__Oversell__Policies' );
		tribe_singleton( 'tickets.commerce.paypal.oversell.request', 'Tribe__Tickets__Commerce__PayPal__Oversell__Request' );
		tribe_singleton( 'tickets.commerce.paypal.frontend.tickets-form', 'Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form' );
		tribe_register( 'tickets.commerce.paypal.cart', 'Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged' );

		tribe()->tag( array(
			'tickets.commerce.paypal.shortcodes.tpp-success' => 'Tribe__Tickets__Commerce__PayPal__Shortcodes__Success',
		), 'tpp-shortcodes' );

		/** @var Tribe__Tickets__Commerce__PayPal__Shortcodes__Interface $shortcode */
		foreach ( tribe()->tagged( 'tpp-shortcodes' ) as $shortcode ) {
			add_shortcode( $shortcode->tag(), array( $shortcode, 'render' ) );
		}

		tribe( 'tickets.commerce.paypal.gateway' );
		tribe( 'tickets.commerce.paypal.orders.report' );
		tribe( 'tickets.commerce.paypal.screen-options' );
		tribe( 'tickets.commerce.paypal.endpoints' );
		tribe( 'tickets.commerce.paypal.currency' );
	}

	/**
	 * Registers all actions/filters
	 *
	 * @since 4.7
	 */
	public function hooks() {
		// if the hooks have already been bound, don't do it again
		if ( $this->is_loaded ) {
			return false;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_resources' ), 11 );
		add_action( 'trashed_post', array( $this, 'maybe_redirect_to_attendees_report' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_action( 'tpp_checkin', array( $this, 'purge_attendees_transient' ) );
		add_action( 'tpp_uncheckin', array( $this, 'purge_attendees_transient' ) );
		add_action( 'tribe_events_tickets_attendees_event_details_top', array( $this, 'setup_attendance_totals' ) );

		add_action( 'init', array( $this, 'init' ) );

		add_action( 'event_tickets_attendee_update', array( $this, 'update_attendee_data' ), 10, 3 );
		add_action( 'event_tickets_after_attendees_update', array( $this, 'maybe_send_tickets_after_status_change' ) );
		add_filter(
			'event_tickets_attendees_tpp_checkin_stati',
			array( $this, 'filter_event_tickets_attendees_tpp_checkin_stati' )
		);

		add_action( 'admin_init', tribe_callback( 'tickets.commerce.paypal.notices', 'hook' ) );
		add_action( 'tribe_tickets_attendees_page_inside', tribe_callback( 'tickets.commerce.paypal.orders.tabbed-view', 'render' ) );
		add_action( 'tribe_events_tickets_metabox_edit_advanced', array( $this, 'do_metabox_advanced_options' ), 10, 2 );
		add_filter( 'tribe_tickets_stock_message_available_quantity', tribe_callback( 'tickets.commerce.paypal.orders.sales', 'filter_available' ), 10, 4 );
		add_action( 'admin_init', tribe_callback( 'tickets.commerce.paypal.oversell.request', 'handle' ) );
		add_filter( 'tribe_tickets_get_default_module', array( $this, 'deprioritize_module' ), 5, 2 );

		add_filter( 'tribe_tickets_tickets_in_cart', array( $this, 'get_tickets_in_cart' ), 10, 1 );
		add_action( 'wp_loaded', array( $this, 'maybe_redirect_to_attendees_registration_screen' ), 1 );
		add_action( 'wp_loaded', array( $this, 'maybe_delete_expired_products' ), 0 );
	}

	/**
	 * Hooked to the init action
	 *
	 * @since 4.7
	 */
	public function init() {
		$this->register_types();
	}

	/**
	 * registers resources
	 *
	 * @since 4.7
	 */
	public function register_resources() {
		$main = Tribe__Tickets__Main::instance();

		tribe_assets(
			$main,
			array(
				array(
					'event-tickets-tpp-css',
					'tpp.css',
				),
				array(
					'event-tickets-tpp-js',
					'tpp.js',
					array(
						'jquery',
						'jquery-ui-datepicker',
					),
				),
			),
			null,
			array(
				'localize' => array(
					'event-tickets-tpp-js',
					'tribe_tickets_tpp_strings',
					array(
						'attendee' => _x( 'Attendee %1$s', 'Attendee number', 'event-tickets' ),
					),
				),
			)
		);

		// Admin assets
		tribe_assets(
			$main,
			array(
				array(
					'event-tickets-tpp-admin-js',
					'tpp-admin.js',
					array(
						'jquery',
						'underscore',
					),
				),
			),
			'admin_enqueue_scripts',
			array(
				'conditionals' => 'is_admin',
				'localize' => (object) array(
					'name' => 'tribe_tickets_tpp_admin_strings',
					'data' => array(
						'complete'   => tribe( 'tickets.commerce.paypal.handler.ipn' )->get_config_status( 'label', 'complete' ),
						'incomplete' => tribe( 'tickets.commerce.paypal.handler.ipn' )->get_config_status( 'label', 'incomplete' ),
					),
				),
			)
		);
	}

	/**
	 * Enqueue the plugin admin stylesheet(s) and JS.
	 *
	 * @since  4.7
	 */
	public function enqueue_resources() {
		$post_types = Tribe__Tickets__Main::instance()->post_types();

		if ( ! is_singular( $post_types ) ) {
			return;
		}

		wp_enqueue_style( 'event-tickets-tpp-css' );
		wp_enqueue_script( 'event-tickets-tpp-js' );

		// Check for override stylesheet
		$user_stylesheet_url = Tribe__Templates::locate_stylesheet( 'tribe-events/tickets/tpp.css' );

		// If override stylesheet exists, then enqueue it
		if ( $user_stylesheet_url ) {
			wp_enqueue_style( 'tribe-events-tickets-tpp-override-style', $user_stylesheet_url );
		}
	}

	/**
	 * Register our custom post type
	 *
	 * @since 4.7
	 */
	public function register_types() {

		$ticket_post_args = array(
			'label'           => __( 'Tickets', 'event-tickets' ),
			'labels'          => array(
				'name'          => __( 'Tribe Commerce Tickets', 'event-tickets' ),
				'singular_name' => __( 'Tribe Commerce Ticket', 'event-tickets' ),
			),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
		);

		$attendee_post_args = array(
			'label'           => __( 'Attendees', 'event-tickets' ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
		);

		$order_post_args = array(
			'label'           => __( 'Orders', 'event-tickets' ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_menu'    => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
		);

		/**
		 * Filter the arguments that craft the ticket post type.
		 *
		 * @since 4.7
		 *
		 * @see register_post_type
		 *
		 * @param array $ticket_post_args Post type arguments, passed to register_post_type()
		 */
		$ticket_post_args = apply_filters( 'tribe_tickets_register_ticket_post_type_args', $ticket_post_args );

		register_post_type( $this->ticket_object, $ticket_post_args );

		/**
		 * Filter the arguments that craft the attendee post type.
		 *
		 * @since 4.7
		 *
		 * @see register_post_type
		 *
		 * @param array $attendee_post_args Post type arguments, passed to register_post_type()
		 */
		$attendee_post_args = apply_filters( 'tribe_tickets_register_attendee_post_type_args', $attendee_post_args );

		register_post_type( self::ATTENDEE_OBJECT, $attendee_post_args );

		/**
		 * Filter the arguments that craft the order post type.
		 *
		 * @since 4.7
		 *
		 * @see register_post_type
		 *
		 * @param array $attendee_post_args Post type arguments, passed to register_post_type()
		 */
		$order_post_args = apply_filters( 'tribe_tickets_register_order_post_type_args', $order_post_args );

		register_post_type( self::ORDER_OBJECT, $order_post_args );

		Tribe__Tickets__Commerce__PayPal__Stati::register_order_stati();
	}

	/**
	 * Adds Tribe Commerce attendance totals to the summary box of the attendance
	 * screen.
	 *
	 * Expects to fire during 'tribe_tickets_attendees_page_inside', ie
	 * before the attendee screen is rendered.
	 *
	 * @since 4.7
	 */
	public function setup_attendance_totals() {
		$this->attendance_totals()->integrate_with_attendee_screen();
	}

	/**
	 * @since 4.7
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Attendance_Totals
	 */
	public function attendance_totals() {
		if ( empty( $this->attendance_totals ) ) {
			$this->attendance_totals = new Tribe__Tickets__Commerce__PayPal__Attendance_Totals;
		}

		return $this->attendance_totals;
	}

	/**
	 * Update the PayPalTicket values for this user.
	 *
	 * Note that, within this method, $order_id refers to the attendee or ticket ID
	 * (it does not refer to an "order" in the sense of a transaction that may include
	 * multiple tickets, as is the case in some other methods for instance).
	 *
	 * @since 4.7
	 *
	 * @param array $data
	 * @param int   $order_id
	 * @param int   $event_id
	 */
	public function update_attendee_data( $data, $order_id, $event_id ) {
		$user_id = get_current_user_id();

		$ticket_orders    = $this->tickets_view->get_post_ticket_attendees( $event_id, $user_id );
		$ticket_order_ids = wp_list_pluck( $ticket_orders, 'order_id' );

		// This makes sure we don't save attendees for orders that are not from this current user and event
		if ( ! in_array( $order_id, $ticket_order_ids ) ) {
			return;
		}

		$attendee = array();

		// Get the Attendee Data, it's important for testing
		foreach ( $ticket_orders as $test_attendee ) {
			if ( $order_id !== $test_attendee['order_id'] ) {
				continue;
			}

			$attendee = $test_attendee;
		}

		$attendee_email        = empty( $data['email'] ) ? null : sanitize_email( $data['email'] );
		$attendee_email        = is_email( $attendee_email ) ? $attendee_email : null;
		$attendee_full_name    = empty( $data['full_name'] ) ? null : sanitize_text_field( $data['full_name'] );
		$attendee_optout       = empty( $data['optout'] ) ? false : (bool) $data['optout'];

		$product_id  = $attendee['product_id'];

		update_post_meta( $order_id, $this->attendee_optout_key, (bool) $attendee_optout );

		if ( ! is_null( $attendee_full_name ) ) {
			update_post_meta( $order_id, $this->full_name, $attendee_full_name );
		}

		if ( ! is_null( $attendee_email ) ) {
			update_post_meta( $order_id, $this->email, $attendee_email );
		}
	}

	/**
	 * Triggers the sending of ticket emails after PayPal Ticket information is updated.
	 *
	 * This is useful if a user initially suggests they will not be attending
	 * an event (in which case we do not send tickets out) but where they
	 * incrementally amend the status of one or more of those tickets to
	 * attending, at which point we should send tickets out for any of those
	 * newly attending persons.
	 *
	 * @since 4.7
	 *
	 * @param $event_id
	 */
	public function maybe_send_tickets_after_status_change( $event_id ) {
		$transaction_ids = array();

		foreach ( $this->get_event_attendees( $event_id ) as $attendee ) {
			$transaction = get_post_meta( $attendee[ 'attendee_id' ], $this->order_key, true );

			if ( ! empty( $transaction ) ) {
				$transaction_ids[ $transaction ] = $transaction;
			}
		}

		foreach ( $transaction_ids as $transaction ) {
			// This method takes care of intelligently sending out emails only when
			// required, for attendees that have not yet received their tickets
			$this->send_tickets_email( $transaction, $event_id );
		}
	}

	/**
	 * Generate and store all the attendees information for a new order.
	 *
	 * @param string $payment_status The tickets payment status, defaults to completed.
	 * @param  bool  $redirect       Whether the client should be redirected or not.
	 *
	 * @since 4.7
	 */
	public function generate_tickets( $payment_status = 'completed', $redirect = true ) {
		/** @var Tribe__Tickets__Commerce__PayPal__Gateway $gateway */
		$gateway          = tribe( 'tickets.commerce.paypal.gateway' );

		$transaction_data = $gateway->get_transaction_data();

		/** @var Tribe__Tickets__Commerce__PayPal__Cart__Interface $cart */
		$cart = tribe( 'tickets.commerce.paypal.cart' );

		/**
		 * The `invoice` variable is a passthrough one; if passed when adding items to the cart
		 * then it should be returned to us from PayPal. If we have it in the transaction data
		 * we can assume the cart associated with the invoice, if any, can be removed.
		 *
		 * @link https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/formbasics/#variations-on-basic-variables
		 */
		if ( ! empty( $transaction_data['custom'] ) ) {
			$decoded_custom = Tribe__Tickets__Commerce__PayPal__Custom_Argument::decode( $transaction_data['custom'], true );
			if ( isset( $decoded_custom['invoice'] ) ) {
				$cart->set_id( $decoded_custom['invoice'] );
				$cart->clear();
			}
		}

		$raw_transaction_data = $gateway->get_raw_transaction_data();

		if ( empty( $transaction_data ) || empty( $transaction_data['items'] ) ) {
			return;
		}

		$has_tickets = $post_id = false;

		/**
		 * PayPal Ticket specific action fired just before a PayPalTicket-driven attendee tickets for an order are generated
		 *
		 * @since 4.7
		 *
		 * @param array $transaction_data PayPal payment data
		 */
		do_action( 'tribe_tickets_tpp_before_order_processing', $transaction_data );

		$order_id = $transaction_data['txn_id'];

		$is_refund = Tribe__Tickets__Commerce__PayPal__Stati::$refunded === $payment_status
		             || 'refund' === Tribe__Utils__Array::get( $transaction_data, 'reason_code', '' );
		if ( $is_refund ) {
			$transaction_data['payment_status'] = $payment_status = Tribe__Tickets__Commerce__PayPal__Stati::$refunded;
			$refund_order_id = $order_id;
			$order_id        = Tribe__Utils__Array::get( $transaction_data, 'parent_txn_id', $order_id );
			$order           = Tribe__Tickets__Commerce__PayPal__Order::from_order_id( $order_id );
			$order->refund_with( $refund_order_id );
			unset( $transaction_data['txn_id'], $transaction_data['parent_txn_id'] );
			$order->hydrate_from_transaction_data( $transaction_data );
		} else {
			$order = Tribe__Tickets__Commerce__PayPal__Order::from_transaction_data( $transaction_data );
		}

		$order->set_meta( 'transaction_data', $raw_transaction_data );

		$custom = Tribe__Tickets__Commerce__PayPal__Custom_Argument::decode( $transaction_data['custom'], true );

		/*
		 * This method might run during a POST (IPN) PayPal request hence the
		 * purchasing user ID, if any, will be stored in a custom PayPal var.
		 * Let's fallback on the current user ID for GET requests (PDT); it will be always `0`
		 * during a PayPal POST (IPN) request.
		 */
		$attendee_user_id = ! isset( $custom['user_id'] ) ? get_current_user_id() : absint( $custom['user_id'] );

		$attendee_full_name = empty( $transaction_data['first_name'] ) && empty( $transaction_data['last_name'] )
			? ''
			: sanitize_text_field( "{$transaction_data['first_name']} {$transaction_data['last_name']}" );

		if ( empty( $attendee_user_id ) ) {
			$attendee_email = empty( $transaction_data['payer_email'] ) ? null : sanitize_email( $transaction_data['payer_email'] );
			$attendee_email = is_email( $attendee_email ) ? $attendee_email : null;
		} else {
			$attendee       = get_user_by( 'ID', $attendee_user_id );
			$attendee_email = $attendee->user_email;
			$user_full_name = trim( "{$attendee->first_name} {$attendee->last_name}" );
			if ( ! empty( $user_full_name ) ) {
				$attendee_full_name = $user_full_name;
			}
		}

		/**
		 * This is an array of tickets IDs for which the user decided to opt-out.
		 *
		 * @see \Tribe__Tickets_Plus__Commerce__PayPal__Attendees::register_optout_choice()
		 */
		$attendee_optouts = Tribe__Utils__Array::list_to_array( Tribe__Utils__Array::get( $custom, 'oo', array() ), ',' );

		if ( ! $attendee_email || ! $attendee_full_name ) {
			$this->redirect_after_error( 101, $redirect, $post_id );
			return;
		}

		// Iterate over each product
		foreach ( (array) $transaction_data['items'] as $item ) {
			$order_attendee_id = 0;

			if ( empty( $item['ticket'] ) ) {
				continue;
			}

			/** @var \Tribe__Tickets__Ticket_Object $ticket_type */
			$ticket_type = $item['ticket'];
			$product_id  = $ticket_type->ID;

			// Get the event this tickets is for
			$post = $ticket_type->get_event();

			if ( empty( $post ) ) {
				continue;
			}

			$post_id = $post->ID;

			// if there were no PayPal tickets for the product added to the cart, continue
			if ( empty( $item['quantity'] ) ) {
				continue;
			}

			// get the PayPal status `decrease_stock_by` value
			$status_stock_size = 1;

			$ticket_qty = (int) $item['quantity'];

			// to avoid tickets from not being created on a status stock size of 0
			// let's take the status stock size into account and create a number of tickets
			// at least equal to the number of tickets the user requested
			$ticket_qty = $status_stock_size < 1 ? $ticket_qty : $status_stock_size * $ticket_qty;

			$qty = max( $ticket_qty, 0 );

			// Throw an error if Qty is bigger then Remaining
			if ( $ticket_type->managing_stock() && $payment_status === Tribe__Tickets__Commerce__PayPal__Stati::$completed ) {
				$this->ignore_pending_stock_logic( true );
				$inventory = (int) $ticket_type->inventory();
				$this->ignore_pending_stock_logic( false );

				$inventory_is_not_unlimited = - 1 !== $inventory;

				if ( $inventory_is_not_unlimited && $qty > $inventory ) {
					if ( ! $order->was_pending() ) {
						$this->redirect_after_error( 102, $redirect, $post_id );
						return;
					}

					/** @var Tribe__Tickets__Commerce__PayPal__Oversell__Policies $oversell_policies */
					$oversell_policies = tribe( 'tickets.commerce.paypal.oversell.policies' );
					$oversell_policy   = $oversell_policies->for_post_ticket_order( $post_id, $ticket_type->ID, $order_id );

					$qty = $oversell_policy->modify_quantity( $qty, $inventory );

					if ( ! $oversell_policy->allows_overselling() ) {
						$oversold_attendees = $this->get_attendees_by_order_id( $order_id );
						$oversell_policy->handle_oversold_attendees( $oversold_attendees );
						$this->redirect_after_error( 102, $redirect, $post_id );
						return;
					}
				}
			}

			if ( $qty === 0 ) {
				$this->redirect_after_error( 103, $redirect, $post_id );
				return;
			}

			$has_tickets = true;

			/**
			 * PayPal specific action fired just before a PayPal-driven attendee ticket for an event is generated
			 *
			 * @since 4.7
			 *
			 * @param int $post_id ID of event
			 * @param string $ticket_type Ticket Type object for the product
			 * @param array $data Parsed PayPal transaction data
			 */
			do_action( 'tribe_tickets_tpp_before_attendee_ticket_creation', $post_id, $ticket_type, $transaction_data );

			$existing_attendees = $this->get_attendees_by_order_id( $order_id );

			$has_generated_new_tickets = false;

			/** @var Tribe__Tickets__Commerce__Currency $currency */
			$currency        = tribe( 'tickets.commerce.currency' );
			$currency_symbol = $currency->get_currency_symbol( $product_id, true );

			// Iterate over all the amount of tickets purchased (for this product)
			for ( $i = 0; $i < $qty; $i ++ ) {
				$attendee_id = null;
				$updating_attendee = false;

				// check if we already have an attendee or not
				$post_title        = $attendee_full_name . ' | ' . ( $i + 1 );
				$criteria          = array( 'post_title' => $post_title, 'product_id' => $product_id, 'event_id' => $post_id );
				$existing_attendee = wp_list_filter( $existing_attendees, $criteria );

				if ( ! empty( $existing_attendee ) ) {
					$existing_attendee = reset( $existing_attendee );
					$updating_attendee = true;
					$attendee_id       = $existing_attendee['attendee_id'];
				} else {
					$attendee = array(
						'post_status' => 'publish',
						'post_title'  => $post_title,
						'post_type'   => $this->attendee_object,
						'ping_status' => 'closed',
					);

					// Insert individual ticket purchased
					$attendee_id = wp_insert_post( $attendee );

					// since we are creating at least one
					$has_generated_new_tickets = true;
				}

				if ( $status_stock_size > 0 ) {
					switch ( $payment_status ) {
						case Tribe__Tickets__Commerce__PayPal__Stati::$completed:
							$this->increase_ticket_sales_by( $product_id, 1 );
							break;
						case Tribe__Tickets__Commerce__PayPal__Stati::$refunded:
							$this->decrease_ticket_sales_by( $product_id, 1 );
							break;
						default:
							break;
					}
				}

				$attendee_order_status = trim( strtolower( $payment_status ) );

				if ( ! $updating_attendee ) {
					update_post_meta( $attendee_id, $this->attendee_product_key, $product_id );
					update_post_meta( $attendee_id, self::ATTENDEE_EVENT_KEY, $post_id );
					update_post_meta( $attendee_id, $this->security_code, $this->generate_security_code( $attendee_id ) );
					update_post_meta( $attendee_id, $this->order_key, $order_id );
					$attendee_optout = Tribe__Utils__Array::get( $attendee_optouts, $product_id, false );
					update_post_meta( $attendee_id, $this->attendee_optout_key, (bool) $attendee_optout );
					update_post_meta( $attendee_id, $this->email, $attendee_email );
					update_post_meta( $attendee_id, $this->full_name, $attendee_full_name );
					update_post_meta( $attendee_id, '_paid_price', get_post_meta( $product_id, '_price', true ) );
					update_post_meta( $attendee_id, '_price_currency_symbol', $currency_symbol );
				}

				update_post_meta( $attendee_id, $this->attendee_tpp_key, $attendee_order_status );

				if ( Tribe__Tickets__Commerce__PayPal__Stati::$refunded === $payment_status ) {
					$refund_order_id = Tribe__Utils__Array::get( $transaction_data, 'txn_id', '' );
					update_post_meta( $attendee_id, $this->refund_order_key, $refund_order_id );
				}

				if ( ! $updating_attendee ) {
					/**
					 * Action fired when an PayPal attendee ticket is created
					 *
					 * @since 4.7
					 *
					 * @param int    $attendee_id           Attendee post ID
					 * @param int    $order_id              PayPal Order ID
					 * @param int    $product_id            PayPal ticket post ID
					 * @param int    $order_attendee_id     Attendee number in submitted order
					 * @param string $attendee_order_status The order status for the attendee.
					 */
					do_action( 'event_tickets_tpp_attendee_created', $attendee_id, $order_id, $product_id, $order_attendee_id, $attendee_order_status );
				}

				/**
				 * Action fired when an PayPal attendee ticket is updated.
				 *
				 * This action will fire both when the attendee is created and
				 * when the attendee is updated.
				 * Hook into the `event_tickets_tpp_attendee_created` action to
				 * only act on the attendee creation.
				 *
				 * @since 4.7
				 *
				 * @param int    $attendee_id           Attendee post ID
				 * @param int    $order_id              PayPal Order ID
				 * @param int    $product_id            PayPal ticket post ID
				 * @param int    $order_attendee_id     Attendee number in submitted order
				 * @param string $attendee_order_status The order status for the attendee.
				 */
				do_action( 'event_tickets_tpp_attendee_updated', $attendee_id, $order_id, $product_id, $order_attendee_id, $attendee_order_status );

				$order->add_attendee( $attendee_id );

				$this->record_attendee_user_id( $attendee_id, $attendee_user_id );
				$order_attendee_id++;

				if ( ! empty( $existing_attendee ) ) {
					$existing_attendees = wp_list_filter( $existing_attendees, array( 'attendee_id' => $existing_attendee['attendee_id'] ), 'NOT' );
				}
			}

			if ( ! ( empty( $existing_attendees ) || empty( $oversell_policy ) ) ) {
				// an oversell policy applied: what to do with existing oversold attendees?
				$oversell_policy->handle_oversold_attendees( $existing_attendees );
			}

			if ( $has_generated_new_tickets ) {
				/**
				 * Action fired when a PayPal has had attendee tickets generated for it.
				 *
				 * @since 4.7
				 *
				 * @param int $product_id PayPal ticket post ID
				 * @param int $order_id   ID of the PayPal order
				 * @param int $qty        Quantity ordered
				 */
				do_action( 'event_tickets_tpp_tickets_generated_for_product', $product_id, $order_id, $qty );
			}

			/**
			 * Action fired when a PayPal has had attendee tickets updated for it.
			 *
			 * This will fire even when tickets are initially craeted; if you need to hook on the
			 * creation process only use the 'event_tickets_tpp_tickets_generated_for_product' action.
			 *
			 * @since 4.7
			 *
			 * @param int $product_id PayPal ticket post ID
			 * @param int $order_id   ID of the PayPal order
			 * @param int $qty        Quantity ordered
			 */
			do_action( 'event_tickets_tpp_tickets_generated_for_product', $product_id, $order_id, $qty );


			// After Adding the Values we Update the Transient
			Tribe__Post_Transient::instance()->delete( $post_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
		}

		$order->update();

		/**
		 * Fires when an PayPal attendee tickets have been generated.
		 *
		 * @since 4.7
		 *
		 * @param int    $order_id              ID of the PayPal order
		 * @param int    $post_id               ID of the post the order was placed for
		 */
		do_action( 'event_tickets_tpp_tickets_generated', $order_id, $post_id );

		/**
		 * Filters whether a confirmation email should be sent or not for PayPal tickets.
		 *
		 * This applies to attendance and non attendance emails.
		 *
		 * @since 4.7
		 *
		 * @param bool $send_mail Defaults to `true`.
		 */
		$send_mail = apply_filters( 'tribe_tickets_tpp_send_mail', true );

		if (
			$send_mail
			&& $has_tickets
			&& $attendee_order_status === Tribe__Tickets__Commerce__PayPal__Stati::$completed
		) {
			$this->send_tickets_email( $order_id, $post_id );
		}

		// Redirect to the same page to prevent double purchase on refresh
		if ( ! empty( $post_id )  ) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Endpoints $endpoints */
			$endpoints = tribe( 'tickets.commerce.paypal.endpoints' );
			$url       = $endpoints->success_url( $order_id, $post_id );
			if ( $redirect ) {
				wp_redirect( esc_url_raw( $url ) );
			}
			tribe_exit();
		}
}

	/**
	 * Sends ticket email
	 *
	 * @since 4.7.6 added $post_id parameter
	 *
	 * @param int $order_id Order post ID
	 * @param int $post_id  Parent post ID (optional)
	 */
	public function send_tickets_email( $order_id, $post_id = null ) {
		$all_attendees = $this->get_attendees_by_id( $order_id );

		$to_send = array();

		if ( empty( $all_attendees ) ) {
			return;
		}

		// Look at each attendee and check if a ticket was sent: in each case where a ticket
		// has not yet been sent we should a) send the ticket out by email and b) record the
		// fact it was sent
		foreach ( $all_attendees as $single_attendee ) {
			// Only add those attendees/tickets that haven't already been sent
			if ( ! empty( $single_attendee[ 'ticket_sent' ] ) ) {
				continue;
			}

			$to_send[] = $single_attendee;
			update_post_meta( $single_attendee[ 'qr_ticket_id' ], $this->attendee_ticket_sent, true );
		}

		/**
		 * Controls the list of tickets which will be emailed out.
		 *
		 * @since 4.7
		 * @since 4.7.6 added new parameter $post_id
		 *
		 * @param array $to_send        list of tickets to be sent out by email
		 * @param array $all_attendees  list of all attendees/tickets, including those already sent out
		 * @param int   $post_id
		 * @param int   $order_id
		 *
		 */
		$to_send = (array) apply_filters( 'tribe_tickets_tpp_tickets_to_send', $to_send, $all_attendees, $post_id, $order_id );

		if ( empty( $to_send ) ) {
			return;
		}

		// For now all ticket holders in an order share the same email
		$to = $all_attendees['0']['holder_email'];

		if ( ! is_email( $to ) ) {
			return;
		}

		/**
		 * Filters the Tribe Commerce tickets email content
		 *
		 * @since 4.7.6 added new parameters $post_id and $order_id
		 *
		 * @param string  email content
		 * @param int     $post_id
		 * @param int     $order_id
		 */
		$content = apply_filters( 'tribe_tpp_email_content', $this->generate_tickets_email_content( $to_send ), $post_id, $order_id );

		/**
		 * Filters the Tribe Commerce tickets email sender name
		 *
		 * @since 4.7.6 added new parameters $post_id and $order_id
		 *
		 * @param string  email sender name
		 * @param int     $post_id
		 * @param int     $order_id
		 */
		$from = apply_filters( 'tribe_tpp_email_from_name', tribe_get_option( 'ticket-paypal-confirmation-email-sender-name', false ), $post_id, $order_id );

		/**
		 * Filters the Tribe Commerce tickets email sender email
		 *
		 * @since 4.7.6 added new parameters $post_id and $order_id
		 *
		 * @param string  email sender email
		 * @param int     $post_id
		 * @param int     $order_id
		 */
		$from_email = apply_filters( 'tribe_tpp_email_from_email', tribe_get_option( 'ticket-paypal-confirmation-email-sender-email', false ), $post_id, $order_id );

		$headers = array( 'Content-type: text/html' );

		if ( ! empty( $from ) && ! empty( $from_email ) ) {
			$headers[] = sprintf( 'From: %s <%s>', filter_var( $from, FILTER_SANITIZE_STRING ), filter_var( $from_email, FILTER_SANITIZE_EMAIL ) );
		}

		/**
		 * Filters the Tribe Commerce tickets email headers
		 *
		 * @since 4.7.6 added new parameters $post_id and $order_id
		 *
		 * @param array  email headers
		 * @param int    $post_id
		 * @param int    $order_id
		 */
		$headers = apply_filters( 'tribe_tpp_email_headers', $headers, $post_id, $order_id );

		/**
		 * Filters the Tribe Commerce tickets email attachments
		 *
		 * @since 4.7.6 added new parameters $post_id and $order_id
		 *
		 * @param array  attachments
		 * @param int    $post_id
		 * @param int    $order_id
		 */
		$attachments = apply_filters( 'tribe_tpp_email_attachments', array(), $post_id, $order_id );

		/**
		 * Filters the Tribe Commerce tickets email recipient
		 *
		 * @since 4.7.6 added new parameters $post_id and $order_id
		 *
		 * @param string  $to
		 * @param int     $event_id
		 * @param int     $order_id
		 */
		$to = apply_filters( 'tribe_tpp_email_recipient', $to, $post_id, $order_id );

		$site_name = stripslashes_deep( html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$default_subject = sprintf( __( 'Your tickets from %s', 'event-tickets' ), $site_name );

		/**
		 * Filters the Tribe Commerce tickets email subject
		 *
		 * @since 4.7.6 added new parameters $post_id and $order_id
		 *
		 * @param string  email subject
		 * @param int     $post_id
		 * @param int     $order_id
		 */
		$subject = apply_filters( 'tribe_tpp_email_subject', tribe_get_option( 'ticket-paypal-confirmation-email-subject', $default_subject ) );

		wp_mail( $to, $subject, $content, $headers, $attachments );
	}

	/**
	 * Saves a ticket
	 *
	 * @since 4.7
	 *
	 * @param int                           $post_id
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 * @param array                         $raw_data
	 *
	 * @return int The updated/created ticket post ID
	 */
	public function save_ticket( $post_id, $ticket, $raw_data = array() ) {
		// assume we are updating until we find out otherwise
		$save_type = 'update';

		if ( empty( $ticket->ID ) ) {
			$save_type = 'create';

			/* Create main product post */
			$args = array(
				'post_status'  => 'publish',
				'post_type'    => $this->ticket_object,
				'post_author'  => get_current_user_id(),
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name,
				'menu_order'   => tribe_get_request_var( 'menu_order', -1 ),
			);

			$ticket->ID = wp_insert_post( $args );

			// Relate event <---> ticket
			add_post_meta( $ticket->ID, $this->event_key, $post_id );

		} else {
			$args = array(
				'ID'           => $ticket->ID,
				'post_excerpt' => $ticket->description,
				'post_title'   => $ticket->name,
			);

			$ticket->ID = wp_update_post( $args );
		}

		if ( ! $ticket->ID ) {
			return false;
		}

		// Updates if we should show Description
		$ticket->show_description = isset( $ticket->show_description ) && tribe_is_truthy( $ticket->show_description ) ? 'yes' : 'no';
		update_post_meta( $ticket->ID, tribe( 'tickets.handler' )->key_show_description, $ticket->show_description );

		// let's make sure float price values are formatted to "0.xyz"
		if ( is_numeric( $ticket->price ) ) {
			$ticket->price = (string) (int) $ticket->price === $ticket->price
				? (int) $ticket->price
				: (float) $ticket->price;
		}

		update_post_meta( $ticket->ID, '_price', $ticket->price );

		$ticket_data = Tribe__Utils__Array::get( $raw_data, 'tribe-ticket', array() );
		$this->update_capacity( $ticket, $ticket_data, $save_type );

		foreach ( array( 'start_date', 'start_time', 'end_date', 'end_time' ) as $time_key ) {
			if ( isset( $ticket->{$time_key} ) ) {
				update_post_meta( $ticket->ID, "_ticket_{$time_key}", $ticket->{$time_key} );
			} else {
				delete_post_meta( $ticket->ID, "_ticket_{$time_key}" );
			}
		}

		/**
		 * Toggle filter to allow skipping the automatic SKU generation.
		 *
		 * @param bool $should_default_ticket_sku
		 */
		$should_default_ticket_sku = apply_filters( 'tribe_tickets_should_default_ticket_sku', true );
		if ( $should_default_ticket_sku ) {
			// make sure the SKU is set to the correct value
			if ( ! empty( $raw_data['ticket_sku'] ) ) {
				$sku = $raw_data['ticket_sku'];
			} else {
				$post_author            = get_post( $ticket->ID )->post_author;
				$str                    = $raw_data['ticket_name'];
				$str                    = mb_strtoupper( $str, mb_detect_encoding( $str ) );
				$sku                    = "{$ticket->ID}-{$post_author}-" . str_replace( ' ', '-', $str );
				$raw_data['ticket_sku'] = $sku;
			}
			update_post_meta( $ticket->ID, '_sku', $sku );
		}

		// Fetches all Ticket Form data
		$data = Tribe__Utils__Array::get( $raw_data, 'tribe-ticket', array() );

		// Fetch the Global stock Instance for this Event
		$event_stock = new Tribe__Tickets__Global_Stock( $post_id );

		// Only need to do this if we haven't already set one - they shouldn't be able to edit it from here otherwise
		if ( ! $event_stock->is_enabled() ) {
			if ( isset( $data['event_capacity'] ) ) {
				$data['event_capacity'] = trim( filter_var( $data['event_capacity'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH ) );

				// If empty we need to modify to -1
				if ( '' === $data['event_capacity'] ) {
					$data['event_capacity'] = -1;
				}

				// Makes sure it's an Int after this point
				$data['event_capacity'] = (int) $data['event_capacity'];

				// We need to update event post meta - if we've set a global stock
				$event_stock->enable();
				$event_stock->set_stock_level( $data['event_capacity'] );

				// Update Event capacity
				update_post_meta( $post_id, tribe( 'tickets.handler' )->key_capacity, $data['event_capacity'] );
			}
		} else {
			// If the Global Stock is configured we pull it from the Event
			$data['event_capacity'] = tribe_tickets_get_capacity( $post_id );
		}

		// Default Capacity will be 0
		$default_capacity = 0;
		$is_capacity_passed = true;

		// If we have Event Global stock we fetch that Stock
		if ( $event_stock->is_enabled() ) {
			$default_capacity = $data['event_capacity'];
		}

		// Fetch capacity field, if we don't have it use default (defined above)
		$data['capacity'] = trim( Tribe__Utils__Array::get( $data, 'capacity', $default_capacity ) );

		// If empty we need to modify to the default
		if ( '' !== $data['capacity'] ) {
			// Makes sure it's an Int after this point
			$data['capacity'] = (int) $data['capacity'];

			// The only available value lower than zero is -1 which is unlimited
			if ( 0 > $data['capacity'] ) {
				$data['capacity'] = -1;
			}

			$default_capacity = $data['capacity'];
		}

		// Fetch the stock if defined, otherwise use Capacity field
		$data['stock'] = trim( Tribe__Utils__Array::get( $data, 'stock', $default_capacity ) );

		// If empty we need to modify to what every capacity was
		if ( '' === $data['stock'] ) {
			$data['stock'] = $default_capacity;
		}

		// Makes sure it's an Int after this point
		$data['stock'] = (int) $data['stock'];

		$mode = isset( $data['mode'] ) ? $data['mode'] : 'own';

		if ( '' !== $mode ) {
			if ( 'update' === $save_type ) {
				$totals = tribe( 'tickets.handler' )->get_ticket_totals( $ticket->ID );
				$data['stock'] -= $totals['pending'] + $totals['sold'];
			}

			// In here is safe to check because we don't have unlimted = -1
			$status = ( 0 < $data['stock'] ) ? 'instock' : 'outofstock';

			update_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, $mode );
			update_post_meta( $ticket->ID, '_stock', $data['stock'] );
			update_post_meta( $ticket->ID, '_stock_status', $status );
			update_post_meta( $ticket->ID, '_backorders', 'no' );
			update_post_meta( $ticket->ID, '_manage_stock', 'yes' );

			// Prevent Ticket Capacity from going higher then Event Capacity
			if (
				$event_stock->is_enabled()
				&& Tribe__Tickets__Global_Stock::OWN_STOCK_MODE !== $mode
				&& '' !== $data['capacity']
				&& $data['capacity'] > $data['event_capacity']
			) {
				$data['capacity'] = $data['event_capacity'];
			}
		} else {
			// Unlimited Tickets
			// Besides setting _manage_stock to "no" we should remove the associated stock fields if set previously
			update_post_meta( $ticket->ID, '_manage_stock', 'no' );
			delete_post_meta( $ticket->ID, '_stock_status' );
			delete_post_meta( $ticket->ID, '_stock' );
			delete_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP );
			delete_post_meta( $ticket->ID, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE );

			// Set Capacity -1 when we don't have a stock mode, which means unlimited
			$data['capacity'] = -1;
		}

		if ( '' !== $data['capacity'] ) {
			// Update Ticket capacity
			update_post_meta( $ticket->ID, tribe( 'tickets.handler' )->key_capacity, $data['capacity'] );
		}

		/**
		 * Generic action fired after saving a ticket (by type)
		 *
		 * @since 4.7
		 *
		 * @param int                           $post_id  Post ID of post the ticket is tied to
		 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket that was just saved
		 * @param array                         $raw_data Ticket data
		 * @param string                        $class    Commerce engine class
		 */
		do_action( 'event_tickets_after_' . $save_type . '_ticket', $post_id, $ticket, $raw_data, __CLASS__ );

		/**
		 * Generic action fired after saving a ticket
		 *
		 * @since 4.7
		 *
		 * @param int                           $post_id  Post ID of post the ticket is tied to
		 * @param Tribe__Tickets__Ticket_Object $ticket   Ticket that was just saved
		 * @param array                         $raw_data Ticket data
		 * @param string                        $class    Commerce engine class
		 */
		do_action( 'event_tickets_after_save_ticket', $post_id, $ticket, $raw_data, __CLASS__ );

		return $ticket->ID;
	}

	/**
	 * Deletes a ticket
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return bool
	 */
	public function delete_ticket( $event_id, $ticket_id ) {
		// Ensure we know the event and product IDs (the event ID may not have been passed in)
		if ( empty( $event_id ) ) {
			$event_id = get_post_meta( $ticket_id, $this->attendee_event_key, true );
		}

		// Additional check (in case we were passed an invalid ticket ID and still can't determine the event)
		if ( empty( $event_id ) ) {
			return false;
		}

		$product_id = get_post_meta( $ticket_id, $this->attendee_product_key, true );

		// @todo: should deleting an attendee replenish a ticket stock?

		// Store name so we can still show it in the attendee list
		$attendees      = $this->get_attendees_by_id( $event_id );
		$post_to_delete = get_post( $ticket_id );

		foreach ( (array) $attendees as $attendee ) {
			if ( $attendee['product_id'] == $ticket_id ) {
				update_post_meta( $attendee['attendee_id'], $this->deleted_product,
					esc_html( $post_to_delete->post_title ) );
			}
		}

		// Try to kill the actual ticket/attendee post
		$delete = wp_delete_post( $ticket_id, true );
		if ( is_wp_error( $delete ) ) {
			return false;
		}

		Tribe__Tickets__Attendance::instance( $event_id )->increment_deleted_attendees_count();
		do_action( 'tickets_tpp_ticket_deleted', $ticket_id, $event_id, $product_id );
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );

		return true;
	}

	/**
	 * Shows the tickets form in the front end
	 *
	 * @since 4.7
	 *
	 * @param $content
	 *
	 * @return void
	 */
	public function front_end_tickets_form( $content ) {
		/** @var Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form $form */
		$form = tribe( 'tickets.commerce.paypal.frontend.tickets-form' );
		$form->render( $content );
	}

	/**
	 * Indicates if we currently require users to be logged in before they can obtain
	 * tickets.
	 *
	 * @since 4.7
	 *
	 * @return bool
	 */
	public function login_required() {
		$requirements = (array) tribe_get_option( 'ticket-authentication-requirements', array() );
		return in_array( 'event-tickets_all', $requirements, true );
	}

	/**
	 * Gets an individual ticket
	 *
	 * @since 4.7
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return null|Tribe__Tickets__Ticket_Object
	 */
	public function get_ticket( $event_id, $ticket_id ) {
		$product = get_post( $ticket_id );

		if ( ! $product ) {
			return null;
		}

		$return = new Tribe__Tickets__Ticket_Object();

		$qty_sold = get_post_meta( $ticket_id, 'total_sales', true );

		$return->description      = $product->post_excerpt;
		$return->ID               = $ticket_id;
		$return->name             = $product->post_title;
		$return->price            = get_post_meta( $ticket_id, '_price', true );
		$return->provider_class   = get_class( $this );
		$return->admin_link       = '';
		$return->show_description = $return->show_description();
		$return->start_date       = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$return->end_date         = get_post_meta( $ticket_id, '_ticket_end_date', true );
		$return->start_time       = get_post_meta( $ticket_id, '_ticket_start_time', true );
		$return->end_time         = get_post_meta( $ticket_id, '_ticket_end_time', true );
		$return->sku              = get_post_meta( $ticket_id, '_sku', true );

		// If the quantity sold wasn't set, default to zero
		$qty_sold = $qty_sold ? $qty_sold : 0;

		// Ticket stock is a simple reflection of remaining inventory for this item...
		$stock = (int) get_post_meta( $ticket_id, '_stock', true );

		// If we don't have a stock value, then stock should be considered 'unlimited'
		if ( null === $stock ) {
			$stock = - 1;
		}

		$return->manage_stock( 'yes' === get_post_meta( $ticket_id, '_manage_stock', true ) );
		$return->stock( $stock );
		$return->global_stock_mode( get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, true ) );
		$capped = get_post_meta( $ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP, true );

		if ( '' !== $capped ) {
			$return->global_stock_cap( $capped );
		}

		$return->qty_sold( $qty_sold );

		$return->qty_cancelled( $this->get_cancelled( $ticket_id ) );

		$pending = $this->get_qty_pending( $ticket_id );

		$return->qty_pending( $pending );

		/**
		 * Use this Filter to change any information you want about this ticket
		 *
		 * @since 4.7
		 *
		 * @param object $ticket
		 * @param int    $post_id
		 * @param int    $ticket_id
		 */
		$ticket = apply_filters( 'tribe_tickets_tpp_get_ticket', $return, $event_id, $ticket_id );

		return $return;
	}

	/**
	 * Get attendees by id and associated post type
	 * or default to using $post_id
	 *
	 * @since 4.7
	 *
	 * @param      $post_id
	 * @param null $post_type
	 *
	 * @return array|mixed
	 */
	public function get_attendees_by_id( $post_id, $post_type = null ) {

		// PayPal Ticket Orders are a unique hash
		if ( ! is_numeric( $post_id ) ) {
			$post_type = 'tpp_order_hash';
		}

		if ( ! $post_type ) {
			$post_type = get_post_type( $post_id );
		}

		switch ( $post_type ) {

			case $this->attendee_object :

				return $this->get_all_attendees_by_attendee_id( $post_id );

				break;

			case 'tpp_order_hash' :

				return $this->get_attendees_by_order_id( $post_id );

				break;
			case $this->ticket_object:

				return $this->get_attendees_by_ticket_id( $post_id );

				break;
			default :

				return $this->get_attendees_by_post_id( $post_id );

				break;
		}

	}

	/**
	 * Get attendees by order id and, optionally, ticket ID.
	 *
	 * @since 4.7
	 *
	 * @param int $order_id An Order PayPal ID (hash)
	 * @param int $product_id A ticket post ID
	 *
	 * @return array
	 */
	public function get_attendees_by_order_id( $order_id, $ticket_id = null ) {
		if ( empty( $order_id ) ) {
			return array();
		}

		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => $this->attendee_object,
			'meta_query'     => array(
				array(

					'key'   => $this->order_key,
					'value' => esc_attr( $order_id ),
				),
			),
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		if ( null !== $ticket_id ) {
			$args['meta_query'][] = array(
				'key'   => $this->attendee_product_key,
				'value' => $ticket_id,
			);
		}

		$attendees_query = new WP_Query( $args );

		if ( ! $attendees_query->have_posts() ) {
			return array();
		}

		return $this->get_attendees( $attendees_query, $order_id );
	}

	/**
	 * Get all the attendees for post type. It returns an array with the
	 * following fields:
	 *
	 *     order_id
	 *     purchaser_name
	 *     purchaser_email
	 *     optout
	 *     ticket
	 *     attendee_id
	 *     security
	 *     product_id
	 *     check_in
	 *     provider
	 *
	 * @since 4.7
	 *
	 * @param $attendees_query
	 * @param $post_id
	 *
	 * @return array
	 */
	protected function get_attendees( $attendees_query, $post_id ) {
		$attendees = array();

		foreach ( $attendees_query->posts as $attendee ) {
			$attendees[] = $this->get_attendee( $attendee );
		}

		return array_filter( $attendees );
	}

	/**
	 * Retrieve only order related information
	 * Important: On PayPal Ticket the order is the Attendee Object
	 *
	 *     order_id
	 *     purchaser_name
	 *     purchaser_email
	 *     provider
	 *     provider_slug
	 *
	 * @since 4.7
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function get_order_data( $order_id ) {
		$name       = get_post_meta( $order_id, $this->full_name, true );
		$email      = get_post_meta( $order_id, $this->email, true );

		$data = array(
			'order_id'        => $order_id,
			'purchaser_name'  => $name,
			'purchaser_email' => $email,
			'provider'        => __CLASS__,
			'provider_slug'   => 'tpp',
			'purchase_time'   => get_post_time( Tribe__Date_Utils::DBDATETIMEFORMAT, false, $order_id ),
		);

		/**
		 * Allow users to filter the Order Data
		 *
		 * @since 4.7
		 *
		 * @param array  $data     An associative array with the Information of the Order
		 * @param string $provider What Provider is been used
		 * @param int    $order_id Order ID
		 *
		 */
		$data = apply_filters( 'tribe_tickets_order_data', $data, 'tpp', $order_id );

		return $data;
	}

	/**
	 * Links to sales report for all tickets for this event.
	 *
	 * @since 4.7
	 *
	 * @param int  $event_id
	 * @param bool $url_only
	 *
	 * @return string
	 */
	public function get_event_reports_link( $event_id, $url_only = false ) {
		$ticket_ids = (array) $this->get_tickets_ids( $event_id );
		if ( empty( $ticket_ids ) ) {
			return '';
		}

		$query = array(
			'page'    => 'tpp-orders',
			'post_id' => $event_id,
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );

		/**
		 * Filter the PayPal Ticket Orders (Sales) Report URL
		 *
		 * @var string $report_url Report URL
		 * @var int    $event_id   The post ID
		 * @var array  $ticket_ids An array of ticket IDs
		 *
		 * @return string
		 */
		$report_url = apply_filters( 'tribe_tickets_paypal_report_url', $report_url, $event_id, $ticket_ids );

		return $url_only
			? $report_url
			: '<small> <a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Sales report', 'event-tickets' ) . '</a> </small>';
	}

	/**
	 * Links to the sales report for this product.
	 *
	 * @since 4.7
	 *
	 * @param $event_id
	 * @param $ticket_id
	 *
	 * @return string
	 */
	public function get_ticket_reports_link( $event_id, $ticket_id ) {
		if ( empty( $ticket_id ) ) {
			return '';
		}

		$query = array(
			'page'        => 'tpp-orders',
			'product_ids' => $ticket_id,
			'post_id'     => $event_id,
		);

		$report_url = add_query_arg( $query, admin_url( 'admin.php' ) );

		return '<span><a href="' . esc_url( $report_url ) . '">' . esc_html__( 'Report', 'event-tickets' ) . '</a></span>';
	}

	/**
	 * Add the sku field in the admin's new/edit ticket metabox
	 *
	 * @since 4.7
	 *
	 * @param $post_id int id of the event post
	 * @param int $ticket_id (null) id of the ticket
	 *
	 * @return void
	 */
	public function do_metabox_sku_options( $post_id, $ticket_id = null ) {
		$sku = '';
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $post_id, $this );

		if ( ! empty( $ticket_id ) ) {
			$ticket = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $ticket_id, $this );

			if ( ! empty( $ticket ) ) {
				$sku = get_post_meta( $ticket_id, '_sku', true );
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		include $this->plugin_path . 'src/admin-views/tpp-metabox-sku.php';
	}

	/**
	 * Renders the advanced fields in the new/edit ticket form.
	 * Using the method, providers can add as many fields as
	 * they want, specific to their implementation.
	 *
	 * @since 4.7
	 *
	 * @param int $post_id
	 * @param int $ticket_id
	 */
	public function do_metabox_advanced_options( $post_id, $ticket_id ) {
		$provider = __CLASS__;

		echo '<div id="' . sanitize_html_class( $provider ) . '_advanced" class="tribe-dependent" data-depends="#' . sanitize_html_class( $provider ) . '_radio" data-condition-is-checked>';

		if ( ! tribe_is_frontend() ) {
			$this->do_metabox_sku_options( $post_id, $ticket_id );
		}

		/**
		 * Allows for the insertion of additional content into the ticket edit form - advanced section
		 *
		 * @since 4.6
		 *
		 * @param int Post ID
		 * @param string the provider class name
		 */
		do_action( 'tribe_events_tickets_metabox_edit_ajax_advanced', $post_id, $provider );

		echo '</div>';
	}

	/**
	 * Gets ticket messages
	 *
	 * @since 4.7
	 *
	 * @return array
	 */
	public function get_messages() {
		return self::$messages;
	}

	/**
	 * Adds a submission message
	 *
	 * @since 4.7
	 *
	 * @param        $message
	 * @param string $type
	 */
	public function add_message( $message, $type = 'update' ) {
		$message = apply_filters( 'tribe_tpp_submission_message', $message, $type );
		self::$messages[] = (object) array( 'message' => $message, 'type' => $type );
	}

	/**
	 * If the post that was moved to the trash was an PayPal Ticket attendee post type, redirect to
	 * the Attendees Report rather than the PayPal Ticket attendees post list (because that's kind of
	 * confusing)
	 *
	 * @since 4.7
	 *
	 * @param int $post_id WP_Post ID
	 */
	public function maybe_redirect_to_attendees_report( $post_id ) {
		$post = get_post( $post_id );

		if ( $this->attendee_object !== $post->post_type ) {
			return;
		}

		$args = array(
			'post_type' => 'tribe_events',
			'page' => Tribe__Tickets__Tickets_Handler::$attendees_slug,
			'event_id' => get_post_meta( $post_id, self::ATTENDEE_EVENT_KEY, true ),
		);

		$url = add_query_arg( $args, admin_url( 'edit.php' ) );
		$url = esc_url_raw( $url );

		wp_redirect( $url );
		tribe_exit();
	}

	/**
	 * Filters the post_updated_messages array for attendees
	 *
	 * @since 4.7
	 *
	 * @param array $messages Array of update messages
	 *
	 * @return array
	 */
	public function updated_messages( $messages ) {
		$ticket_post = get_post();

		if ( ! $ticket_post ) {
			return $messages;
		}

		$post_type = get_post_type( $ticket_post );

		if ( $this->attendee_object !== $post_type ) {
			return $messages;
		}

		$event = $this->get_event_for_ticket( $ticket_post );

		$attendees_report_url = add_query_arg(
			array(
				'post_type' => $event->post_type,
				'page' => Tribe__Tickets__Tickets_Handler::$attendees_slug,
				'event_id' => $event->ID,
			),
			admin_url( 'edit.php' )
		);

		$return_link = sprintf(
			esc_html__( 'Return to the %1$sAttendees Report%2$s.', 'event-tickets' ),
			"<a href='" . esc_url( $attendees_report_url ) . "'>",
			'</a>'
		);

		$messages[ $this->attendee_object ] = $messages['post'];
		$messages[ $this->attendee_object ][1] = sprintf(
			esc_html__( 'Post updated. %1$s', 'event-tickets' ),
			$return_link
		);
		$messages[ $this->attendee_object ][6] = sprintf(
			esc_html__( 'Post published. %1$s', 'event-tickets' ),
			$return_link
		);
		$messages[ $this->attendee_object ][8] = esc_html__( 'Post submitted.', 'event-tickets' );
		$messages[ $this->attendee_object ][9] = esc_html__( 'Post scheduled.', 'event-tickets' );
		$messages[ $this->attendee_object ][10] = esc_html__( 'Post draft updated.', 'event-tickets' );

		return $messages;
	}

	/**
	 * Set the tickets view
	 *
	 * @since 4.7
	 *
	 * @param Tribe__Tickets__Commerce__PayPal__Tickets_View $tickets_view
	 *
	 * @internal Used for dependency injection.
	 */
	public function set_tickets_view( Tribe__Tickets__Commerce__PayPal__Tickets_View $tickets_view ) {
		$this->tickets_view = $tickets_view;
	}

	/**
	 * Get's the product price html
	 *
	 * @since 4.7
	 *
	 * @param int|object $product
	 * @param array $attendee
	 *
	 * @return string
	 */
	public function get_price_html( $product, $attendee = false ) {
		$product_id = $product;

		if ( $product instanceof WP_Post ) {
			$product_id = $product->ID;
		} elseif ( is_numeric( $product_id ) ) {
			$product = get_post( $product_id );
		} else {
			return '';
		}

		$price = get_post_meta( $product_id, '_price', true );
		$price = tribe( 'tickets.commerce.paypal.currency' )->format_currency( $price, $product_id );

		$price_html = '<span class="tribe-tickets-price-amount amount">' . esc_html( $price ) . '</span>';

		/**
		 * Allow filtering of the Price HTML
		 *
		 * @since 4.7
		 *
		 * @param string $price_html
		 * @param mixed  $product
		 * @param mixed  $attendee
		 *
		 */
		return apply_filters( 'tribe_tickets_tpp_ticket_price_html', $price_html, $product, $attendee );
	}

	/**
	 * Filters the array of statuses that will mark an ticket attendee as eligible for check-in.
	 *
	 * @since 4.7
	 *
	 * @param array $statuses An array of statuses that should mark an ticket attendee as
	 *                     available for check-in.
	 *
	 * @return array The original array plus the 'yes' status.
	 */
	public function filter_event_tickets_attendees_tpp_checkin_stati( array $statuses = array() ) {
		$statuses[] = 'completed';

		return array_unique( $statuses );
	}

	/**
	 * Gets the cart URL
	 *
	 * @since 4.7
	 *
	 * @return string
	 */
	public function get_cart_url() {
		return tribe( 'tickets.commerce.paypal.gateway' )->get_cart_url();
	}

	/**
	 * Gets a transaction URL
	 *
	 * @since 4.7
	 *
	 * @param $transaction
	 *
	 * @return string
	 */
	public function get_transaction_url( $transaction ) {
		return tribe( 'tickets.commerce.paypal.gateway' )->get_transaction_url( $transaction );
	}

	/**
	 * Returns the value of a key defined by the class.
	 *
	 * @since 4.7
	 *
	 * @param string $key
	 *
	 * @return string The key value or an empty string if not defined.
	 */
	public static function get_key( $key ) {
		$instance = self::get_instance();
		$key      = strtolower( $key );

		$constant_map = array(
			'attendee_event_key'   => $instance->attendee_event_key,
			'attendee_product_key' => $instance->attendee_product_key,
			'attendee_order_key'   => $instance->attendee_order_key,
			'attendee_optout_key'  => $instance->attendee_optout_key,
			'attendee_tpp_key'     => $instance->attendee_tpp_key,
			'event_key'            => $instance->event_key,
			'checkin_key'          => $instance->checkin_key,
			'order_key'            => $instance->order_key,
		);

		return Tribe__Utils__Array::get( $constant_map, $key, '' );
	}

	/**
	 * Returns the ID of the post associated with a PayPal order if any.
	 *
	 * @since 4.7
	 *
	 * @param string $order The alphanumeric order identification string.
	 *
	 * @return int|false Either the ID of the post associated with the order or `false` on failure.
	 */
	public function get_post_id_from_order( $order ) {
		if ( empty( $order ) ) {
			return false;
		}

		global $wpdb;

		$post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT m2.meta_value
			FROM {$wpdb->postmeta} m1
			JOIN {$wpdb->postmeta} m2
			ON m1.post_id = m2.post_id
			WHERE m1.meta_key = %s
			AND m1.meta_value = %s
			AND m2.meta_key = %s",
			$this->order_key, $order, $this->attendee_event_key )
		);

		return empty( $post_id ) ? false : $post_id;
	}

	/**
	 * Returns a list of attendees for an order.
	 *
	 * @since 4.7
	 *
	 * @param string $order The alphanumeric order identification string.
	 *
	 * @return array An array of WP_Post attendee objects.
	 */
	public function get_attendees_by_order( $order ) {
		if ( empty( $order ) ) {
			return false;
		}

		global $wpdb;

		$attendees = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT( m.post_id )
			FROM {$wpdb->postmeta} m
			WHERE m.meta_key = %s
			AND m.meta_value = %s",
			$this->order_key, $order )
		);

		return empty( $attendees ) ? array() : array_map( 'get_post', $attendees );
	}

	/**
	 * Whether the ticket is a PayPal one or not.
	 *
	 * @since 4.7
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return bool
	 */
	public function is_paypal_ticket( Tribe__Tickets__Ticket_Object $ticket ) {
		return $ticket->provider_class === __CLASS__;
	}

	/**
	 * Returns a list of attendees grouped by order.
	 *
	 * @since 4.7
	 *
	 * @param int   $post_id
	 * @param array $ticket_ids An optional array of ticket IDs to limit the orders by.
	 *
	 * @return array An associative array in the format [ <order_number> => <order_details> ]
	 */
	public function get_orders_by_post_id( $post_id, array $ticket_ids = null, $args = array() ) {
		$find_by_args = wp_parse_args( $args, array(
			'post_id'        => $post_id,
			'ticket_id'      => $ticket_ids,
		) );

		$orders = Tribe__Tickets__Commerce__PayPal__Order::find_by( $find_by_args );

		$found    = array();
		$statuses = $this->get_order_statuses();

		if ( ! empty( $orders ) ) {
			/** @var Tribe__Tickets__Commerce__PayPal__Order $order */
			foreach ( $orders as $order ) {
				$order_id                        = $order->paypal_id();
				$status                          = $order->get_status();
				$attendees                       = $order->get_attendees();
				$refund_order_id = $order->get_refund_order_id();

				$found[ $order_id ] = array(
					'url'             => $this->get_transaction_url( $order_id ),
					'number'          => $order_id,
					'status'          => $status,
					'status_label'    => Tribe__Utils__Array::get( $statuses, $status, Tribe__Tickets__Commerce__PayPal__Stati::$undefined ),
					'purchaser_name'  => $order->get_meta( 'address_name' ),
					'purchaser_email' => $order->get_meta( 'payer_email' ),
					'purchase_time'   => $order->get_meta( 'payment_date' ),
					'attendees'       => $attendees,
					'items'           => $order->get_meta( 'items' ),
					'line_total'      => $order->get_line_total(),
				);

				if ( ! empty( $refund_order_id ) ) {
					$found[ $order_id ]['refund_number'] = $refund_order_id;
					$found[ $order_id ]['refund_url']    = $this->get_transaction_url( $refund_order_id );
				}
			}
		}

		return $found;
	}

	/**
	 * Returns the list of PayPal tickets order stati.
	 *
	 * @since 4.7
	 *
	 * @return array An associative array in the [ <slug> => <label> ] format.
	 */
	public function get_order_statuses() {
		$order_statuses = array(
			Tribe__Tickets__Commerce__PayPal__Stati::$undefined     => _x( 'Undefined', 'a PayPal ticket order status', 'event-tickets' ),
			Tribe__Tickets__Commerce__PayPal__Stati::$completed     => _x( 'Completed', 'a PayPal ticket order status', 'event-tickets' ),
			Tribe__Tickets__Commerce__PayPal__Stati::$refunded      => _x( 'Refunded', 'a PayPal ticket order status', 'event-tickets' ),
			Tribe__Tickets__Commerce__PayPal__Stati::$pending       => _x( 'Pending', 'a PayPal ticket order status', 'event-tickets' ),
			Tribe__Tickets__Commerce__PayPal__Stati::$denied        => _x( 'Denied', 'a PayPal ticket order status', 'event-tickets' ),
			Tribe__Tickets__Commerce__PayPal__Stati::$not_completed => _x( 'Not Completed', 'a PayPal ticket order status', 'event-tickets' ),
		);

		/**
		 * Filters the list of PayPal tickets order stati.
		 *
		 * @since 4.7
		 *
		 * @param array $order_statuses
		 *
		 * @return array An associative array in the [ <slug> => <label> ] format.
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_order_stati', $order_statuses );
	}

	/**
	 * If product cache parameter is found, delete saved products from temporary cart.
	 *
	 * @filter wp_loaded 0
	 *
	 * @since 4.9
	 */
	public function maybe_delete_expired_products() {
		$delete = tribe_get_request_var( 'clear_product_cache', null );
		if ( empty( $delete ) ) {
			return;
		}
		delete_transient( $this->get_current_cart_transient() );

		// Bail if ET+ is not in place
		if ( ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' ) ) {
			return;
		}

		$storage = new Tribe__Tickets_Plus__Meta__Storage();
		$storage->delete_cookie();
	}

	/**
	 * Redirect to attendees meta screen before loading Paypal.
	 *
	 * @filter wp_loaded 1
	 *
	 * @since 4.9
	 *
	 * @param string $redirect
	 */
	public function maybe_redirect_to_attendees_registration_screen( $redirect = null ) {
		if ( ! $this->is_checkout_page() ) {
			return;
		}

		if ( tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return;
		}

 		if ( $_POST ) {
			return;
		}

		$redirect = tribe_get_request_var( 'tribe_tickets_redirect_to', null );
		$redirect = base64_encode( $redirect );

		parent::maybe_redirect_to_attendees_registration_screen( $redirect );
	}

	/**
	 * Returns if it's TPP checkout based on the redirect query var
	 *
	 * @since 4.9
	 *
	 * @return bool
	 */
	public function is_checkout_page() {
		if ( is_admin() ) {
			return false;
		}
 		$redirect = tribe_get_request_var( 'tribe_tickets_redirect_to', null );
 		return ! empty( $redirect );
	}

	/**
	 * Get the tickets currently in the cart.
	 *
	 * @since 4.9
	 *
	 * @param array $tickets
	 *
	 * @return array
	 */
	public function get_tickets_in_cart( $tickets ) {
		$contents  = get_transient( $this->get_current_cart_transient() );
		if ( empty( $contents ) ) {
			return $tickets;
		}
		foreach ( $contents as $id => $quantity ) {
			$event_check = get_post_meta( $id, $this->event_key, true );
			if ( empty( $event_check ) ) {
				continue;
			}
			$tickets[ $id ] = $quantity;
		}
		return $tickets;
	}

 	/**
	 * Get the current cart Transient key.
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	private function get_current_cart_transient() {
		$cart      = tribe( 'tickets.commerce.paypal.cart' );
		$invoice   = Tribe__Utils__Array::get(
			$_COOKIE, Tribe__Tickets__Commerce__PayPal__Gateway::$invoice_cookie_name,
			false
		);

		$cart_class = get_class( $cart );
		return call_user_func( array( $cart_class, 'get_transient_name' ), $invoice );
	}

	/**
	 * Returns all the tickets for an event
	 *
	 * @since 4.7
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_tickets( $post_id ) {
		$ticket_ids = $this->get_tickets_ids( $post_id );

		if ( ! $ticket_ids ) {
			return array();
		}

		$tickets = array();

		foreach ( $ticket_ids as $post ) {
			$tickets[] = $this->get_ticket( $post_id, $post );
		}

		return $tickets;
	}

	/**
	 * Renders the advanced fields in the new/edit ticket form.
	 * Using the method, providers can add as many fields as
	 * they want, specific to their implementation.
	 *
	 * @since 4.7
	 *
	 * @param int $post_id
	 * @param int $ticket_id
	 *
	 * @return mixed
	 */
	public function do_metabox_capacity_options( $post_id, $ticket_id ) {
		$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $post_id, $this );

		$url               = '';
		$stock             = '';
		$global_stock_mode = tribe( 'tickets.handler' )->get_default_capacity_mode();
		$global_stock_cap  = 0;
		$ticket_capacity   = null;
		$post_capacity     = null;

		$stock_object = new Tribe__Tickets__Global_Stock( $post_id );

		if ( $stock_object->is_enabled() ) {
			$post_capacity = tribe_tickets_get_capacity( $post_id );
		}

		if ( ! empty( $ticket_id ) ) {
			$ticket              = $this->get_ticket( $post_id, $ticket_id );
			$is_correct_provider = tribe( 'tickets.handler' )->is_correct_provider( $ticket_id, $this );

			if ( ! empty( $ticket ) ) {
				$stock             = $ticket->managing_stock() ? $ticket->stock() : '';
				$ticket_capacity   = tribe_tickets_get_capacity( $ticket->ID );
				$global_stock_mode = ( method_exists( $ticket, 'global_stock_mode' ) ) ? $ticket->global_stock_mode() : '';
				$global_stock_cap  = ( method_exists( $ticket, 'global_stock_cap' ) ) ? $ticket->global_stock_cap() : 0;
			}
		}

		// Bail when we are not dealing with this provider
		if ( ! $is_correct_provider ) {
			return;
		}

		$file = Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/tpp-metabox-capacity.php';

		/**
		 * Filters the absolute path to the file containing the metabox capacity HTML.
		 *
		 * @since 4.7
		 *
		 * @param string     $file The absolute path to the file containing the metabox capacity HTML
		 * @param int|string $ticket_capacity
		 * @param int|string $post_capacity
		 */
		$file = apply_filters( 'tribe_tickets_tpp_metabox_capacity_file', $file, $ticket_capacity, $post_capacity );

		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	/**
	 * Indicates if global stock support is enabled for this provider.
	 *
	 * @since 4.7
	 *
	 * @return bool
	 */
	public function supports_global_stock() {
		/**
		 * Allows the declaration of global stock support for WooCommerce tickets
		 * to be overridden.
		 *
		 * @param bool $enable_global_stock_support
		 */
		return (bool) apply_filters( 'tribe_tickets_tpp_enable_global_stock', true );
	}

	/**
	 * Gets the product price value
	 *
	 * @since  4.7
	 *
	 * @param  int|WP_Post $product
	 *
	 * @return string
	 */
	public function get_price_value( $product ) {
		if ( ! $product instanceof WP_Post ) {
			$product = get_post( $product );
		}

		if ( ! $product instanceof WP_Post ) {
			return false;
		}

		return get_post_meta( $product->ID, '_price', true );
	}

	/**
	 * Returns the number of pending attendees by ticket.
	 *
	 * @since 4.7
	 *
	 * @param int  $ticket_id The ticket post ID
	 * @param bool $refresh   Whether to try and use the cached value or not.
	 *
	 * @return int
	 */
	public function get_qty_pending( $ticket_id, $refresh = false ) {
		if ( $refresh || empty( $this->pending_attendees_by_ticket[ $ticket_id ] ) ) {
			$pending_query = new WP_Query( array(
				'fields'     => 'ids',
				'per_page'   => 1,
				'post_type'  => self::ATTENDEE_OBJECT,
				'meta_query' => array(
					array(
						'key'   => self::ATTENDEE_PRODUCT_KEY,
						'value' => $ticket_id,
					),
					'relation' => 'AND',
					array(
						'key'   => $this->attendee_tpp_key,
						'value' => Tribe__Tickets__Commerce__PayPal__Stati::$pending,
					),
				),
			) );

			$this->pending_attendees_by_ticket[ $ticket_id ] = $pending_query->found_posts;
		}

		return $this->pending_attendees_by_ticket[ $ticket_id ];
	}

	/**
	 * Returns all the attendees for a ticket.
	 *
	 * @since 4.7
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array An array of attendees for the ticket.
	 */
	public function get_attendees_by_ticket_id( $ticket_id ) {
		$attendees_query = new WP_Query( array(
			'posts_per_page' => - 1,
			'post_type'      => $this->attendee_object,
			'meta_key'       => self::ATTENDEE_PRODUCT_KEY,
			'meta_value'     => $ticket_id,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		if ( ! $attendees_query->have_posts() ) {
			return array();
		}

		return $this->get_attendees( $attendees_query, $ticket_id );
	}

	/**
	 * Whether a specific attendee is valid toward inventory decrease or not.
	 *
	 * By default only attendees generated as part of a Completed order will count toward
	 * an inventory decrease but, if the option to reserve stock for Pending Orders is activated,
	 * then those attendees generated as part of a Pending Order will, for a limited time after the
	 * order creation, cause the inventory to be decreased.
	 *
	 * @since 4.7
	 *
	 * @param array $attendee
	 *
	 * @return bool
	 */
	public function attendee_decreases_inventory( array $attendee ) {
		$order_status = Tribe__Utils__Array::get( $attendee, 'order_status', 'undefined' );
		$order_id = Tribe__Utils__Array::get( $attendee, 'order_id', false );

		/**
		 * Whether the pending Order stock reserve logic should be ignored completely or not.
		 *
		 * If set to `true` then the behaviour chosen in the Settings will apply, if `false`
		 * only Completed tickets will count to decrease the inventory. This is useful when
		 *
		 * @since 4.7
		 *
		 * @param bool  $ignore_pending
		 * @param array $attendee An array of data defining the current Attendee
		 */
		$ignore_pending = apply_filters( 'tribe_tickets_tpp_pending_stock_ignore', $this->ignore_pending_stock_logic );

		if (
			'on-pending' === tribe_get_option( 'ticket-paypal-stock-handling', 'on-complete' )
			&& ! $ignore_pending
			&& Tribe__Tickets__Commerce__PayPal__Stati::$pending === $order_status
			&& false !== $order_id
			&& false !== $order = Tribe__Tickets__Commerce__PayPal__Order::from_attendee_id( $order_id )
		) {
			/** @var \Tribe__Tickets__Commerce__PayPal__Order $order */
			$order_creation_timestamp = Tribe__Date_Utils::wp_strtotime( $order->get_creation_date() );

			/**
			 * Filters the amount of time a part of the stock will be reserved by a pending Order.
			 *
			 * The time applies from the Order creation time.
			 * In the unlikely scenario that an Order goes from Completed to Pending then, if the
			 * reservation time allows it, a part of the stock will be reserved for it.
			 *
			 * @since 4.7
			 *
			 * @param int                                      $pending_stock_reservation_time The amount of seconds, from the Order creation time,
			 *                                                                                 part of the stock will be reserved for the Order;
			 *                                                                                 defaults to 30 minutes.
			 * @param array                                    $attendee                       An array of data defining the current Attendee
			 * @param Tribe__Tickets__Commerce__PayPal__Order $order                          The object representing the Order that generated
			 *                                                                                 the Attendee
			 */
			$pending_stock_reservation_time = (int) apply_filters( 'tribe_tickets_tpp_pending_stock_reserve_time', 30 * 60, $attendee, $order );

			return current_time( 'timestamp' ) <= ( $order_creation_timestamp + $pending_stock_reservation_time );
		}

		return Tribe__Tickets__Commerce__PayPal__Stati::$completed === $order_status;
	}

	/**
	 * Increases the sales for a ticket by an amount.
	 *
	 * @since 4.7
	 *
	 * @param int  $ticket_id The ticket post ID
	 * @param int  $qty
	 *
	 * @return int
	 */
	public function increase_ticket_sales_by( $ticket_id, $qty = 1 ) {
		$sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
		update_post_meta( $ticket_id, 'total_sales', $sales + $qty );

		return $sales;
	}

	/**
	 * Decreases the sales for a ticket by an amount.
	 *
	 * @since 4.7
	 *
	 * @param int $ticket_id The ticket post ID
	 * @param int $qty
	 *
	 * @return int
	 */
	public function decrease_ticket_sales_by( $ticket_id, $qty = 1 ) {
		$sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
		update_post_meta( $ticket_id, 'total_sales', max( $sales - $qty, 0 ) );
	}

	/**
	 * Returns the data for an attendee.
	 *
	 * @since 4.7
	 *
	 * @param int|WP_Post $attendee An attendee post object or post ID.
	 *
	 * @return array|false Either an array of Attendee information or `false`
	 *                     if the attendee could not be found.
	 */
	public function get_attendee( $attendee ) {
		if ( is_numeric( $attendee ) ) {
			$attendee = get_post( $attendee );
		}

		if ( ! $attendee instanceof WP_Post || self::ATTENDEE_OBJECT !== $attendee->post_type ) {
			return false;
		}

		$checkin      = get_post_meta( $attendee->ID, $this->checkin_key, true );
		$security     = get_post_meta( $attendee->ID, $this->security_code, true );
		$product_id   = get_post_meta( $attendee->ID, $this->attendee_product_key, true );
		$optout       = (bool) get_post_meta( $attendee->ID, $this->attendee_optout_key, true );
		$status       = get_post_meta( $attendee->ID, $this->attendee_tpp_key, true );
		$user_id      = get_post_meta( $attendee->ID, $this->attendee_user_id, true );
		$ticket_sent  = (bool) get_post_meta( $attendee->ID, $this->attendee_ticket_sent, true );

		if ( empty( $product_id ) ) {
			return false;
		}

		$product       = get_post( $product_id );
		$product_title = ( ! empty( $product ) ) ? $product->post_title : get_post_meta( $attendee->ID, $this->deleted_product, true ) . ' ' . __( '(deleted)', 'event-tickets' );

		$ticket_unique_id = get_post_meta( $attendee->ID, '_unique_id', true );
		$ticket_unique_id = $ticket_unique_id === '' ? $attendee->ID : $ticket_unique_id;

		$meta = '';
		if ( class_exists( 'Tribe__Tickets_Plus__Meta', false ) ) {
			$meta = get_post_meta( $attendee->ID, Tribe__Tickets_Plus__Meta::META_KEY, true );

			// Process Meta to include value, slug, and label
			if ( ! empty( $meta ) ) {
				$meta = $this->process_attendee_meta( $product_id, $meta );
			}
		}

		$attendee_data = array_merge( $this->get_order_data( $attendee->ID ), array(
			'optout'       => $optout,
			'ticket'       => $product_title,
			'attendee_id'  => $attendee->ID,
			'security'     => $security,
			'product_id'   => $product_id,
			'check_in'     => $checkin,
			'order_status' => $status,
			'user_id'      => $user_id,
			'ticket_sent'  => $ticket_sent,

			// this is used to find existing attendees
			'post_title'   => $attendee->post_title,

			// Fields for Email Tickets
			'event_id'      => get_post_meta( $attendee->ID, $this->attendee_event_key, true ),
			'ticket_name'   => ! empty( $product ) ? $product->post_title : false,
			'holder_name'   => get_post_meta( $attendee->ID, $this->full_name, true ),
			'holder_email'  => get_post_meta( $attendee->ID, $this->email, true ),
			'order_id'      => $attendee->ID,
			'ticket_id'     => $ticket_unique_id,
			'qr_ticket_id'  => $attendee->ID,
			'security_code' => $security,

			// Attendee Meta
			'attendee_meta' => $meta,
		) );

		/**
		 * Allow users to filter the Attendee Data
		 *
		 * @since 4.7
		 *
		 * @param array   $attendee_data An associative array with the Information of the Attendee
		 * @param string  $provider      What Provider is been used
		 * @param WP_Post $attendee      Attendee Object
		 *
		 */
		$attendee_data = apply_filters( 'tribe_tickets_attendee_data', $attendee_data, 'tpp', $attendee );

		return $attendee_data;
	}

	/**
	 * Returns the total number of cancelled tickets.
	 *
	 * @since 4.7
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return int
	 */
	protected function get_cancelled( $ticket_id ) {
		$denied_orders = Tribe__Tickets__Commerce__PayPal__Order::find_by( array(
			'ticket_id'   => $ticket_id,
			'post_status' => Tribe__Tickets__Commerce__PayPal__Stati::$denied,
			'posts_per_page' => -1,
		) );

		$denied = 0;
		foreach ( $denied_orders as $denied_order ) {
			$denied += $denied_order->get_item_quantity( $ticket_id );
		}

		return max( 0, $denied );
	}

	/**
	 * Whether the Pending Order stock reservation logic should be ignored or
	 * not, no matter the Settings.
	 *
	 * This is useful when trying to get the "true" inventory of a ticket.
	 *
	 * @param bool $ignore_pending_stock_logic
	 *
	 * @see Tribe__Tickets__Commerce__PayPal__Main::attendee_decreases_inventory
	 */
	public function ignore_pending_stock_logic( $ignore_pending_stock_logic ) {
		$this->ignore_pending_stock_logic = (bool) $ignore_pending_stock_logic;
	}

	/**
	 * Redirects to the source post after a recoverable (logic) error.
	 *
	 * @since 4.7
	 *
	 * @param int  $error_code The current error code
	 * @param bool $redirect   Whether to really redirect or not.
	 * @param int  $post_id    A post ID
	 *
	 * @return string
	 *
	 * @see Tribe__Tickets__Commerce__PayPal__Errors for error codes translations.
	 */
	protected function redirect_after_error( $error_code, $redirect, $post_id ) {
		$url = add_query_arg( 'tpp_error', $error_code, get_permalink( $post_id ) );
		if ( $redirect ) {
			wp_redirect( esc_url_raw( $url ) );
		}
		tribe_exit();
	}

    /**
     * Generates the validation code that will be printed in the ticket.
     *
     * Its purpose is to be used to validate the ticket at the door of an event.
     *
     * @since 4.7
     *
     * @param int $attendee_id
     *
     * @return string
     */
    public function generate_security_code( $attendee_id ) {
        return substr( md5( rand() . '_' . $attendee_id ), 0, 10 );
    }

	/**
	 * If other modules are active, we should deprioritize this one (we want other commerce
	 * modules to take priority over this one).
	 *
	 * @since 4.7.1
	 *
	 * @param string   $default_module
	 * @param string[] $available_modules
	 *
	 * @return string
	 */
	public function deprioritize_module( $default_module, array $available_modules ) {
		$tribe_commerce_module = get_class( $this );

		// If this isn't the default (or if there isn't a choice), no need to deprioritize
		if (
			$default_module !== $tribe_commerce_module
			|| count( $available_modules ) < 2
			|| reset( $available_modules ) !== $tribe_commerce_module
		) {
			return $default_module;
		}

		return next( $available_modules );
	}
}
