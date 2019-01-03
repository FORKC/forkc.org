<?php
/**
 * Add WooCommerce settings to the tickets settings, and automatically pull some settings from Woo.
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Settings {
	public function __construct() {
		add_filter( 'tribe_tickets_settings_tab_fields', array( $this, 'add_settings' ) );

		// Use Woo's decimal separator in the Add Ticket Cost field.
		add_filter( 'tribe_event_ticket_decimal_point', 'wc_get_price_decimal_separator' );
	}

	/**
	 * Append WooCommerce-specific settings section to tickets settings tab
	 * @param array $settings_fields
	 *
	 * @since 4.7
	 *
	 * @return array
	 */
	public function add_settings( array $settings_fields ) {
		$extra_settings = $this->additional_settings();
		return Tribe__Main::array_insert_before_key( 'tribe-form-content-end', $settings_fields, $extra_settings );
	}

	protected function additional_settings() {
		$dispatch_options = $generation_options = $this->get_trigger_statuses();

		$section_label      = esc_html__( 'Event Tickets uses WooCommerce order statuses to control when attendee records should be generated and when tickets are sent to customers. The first enabled status reached by an order will trigger the action.', 'event-tickets-plus' );
		$dispatch_label     = esc_html__( 'When should tickets be emailed to customers?', 'event-tickets-plus' );
		$dispatch_tooltip   = esc_html__( 'If no status is selected, no ticket emails will be sent.', 'event-tickets-plus' );
		$generation_label   = esc_html__( 'When should attendee records be generated?', 'event-tickets-plus' );
		$generation_tooltip = esc_html__( 'Please select at least one status.', 'event-tickets-plus' );

		$dispatch_defaults = $this->get_default_ticket_dispatch_statuses();
		$generation_defaults = $this->get_default_ticket_generation_statuses();

		return array(
			'tickets-woo-options-title' => array(
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'WooCommerce Support', 'event-tickets-plus' ) . '</h3>',
			),
			'tickets-woo-options-intro' => array(
				'type' => 'html',
				'html' => "<p> $section_label </p>",
			),
			'tickets-woo-generation-status' => array(
				'type'            => 'checkbox_list',
				'validation_type' => 'options_multi',
				'label'           => $generation_label,
				'tooltip'         => $generation_tooltip,
				'options'         => $generation_options,
				'default'         => $generation_defaults,
				'can_be_empty'    => true,
			),
			'tickets-woo-dispatch-status' => array(
				'type'            => 'checkbox_list',
				'validation_type' => 'options_multi',
				'label'           => $dispatch_label,
				'tooltip'         => $dispatch_tooltip,
				'options'         => $dispatch_options,
				'default'         => $dispatch_defaults,
				'can_be_empty'    => true,
			),
		);
	}

	/**
	 * Returns a map of order statuses (and labels).
	 *
	 * @return string[string]
	 */
	protected function get_trigger_statuses() {
		$statuses = array( 'immediate' => __( 'As soon as an order is created', 'event-tickets-plus' ) )
			+ (array) wc_get_order_statuses();

		// In most cases cancelled, refunded and failed statuses can be removed from the set of options
		// ...but they can be restored via the following filter in any edge cases that require them
		unset(
			$statuses['wc-cancelled'],
			$statuses['wc-refunded'],
			$statuses['wc-failed']
		);

		/**
		 * Lists the possible options for generating and dispatching tickets.
		 *
		 * This is typically a map of all the WooCommerce order statuses, plus an additional
		 * option to generate them immediately an order is created.
		 *
		 * @param array $dispatch_options
		 */
		return (array) apply_filters( 'tribe_tickets_plus_woo_trigger_statuses', $statuses );
	}

	/**
	 * @return array
	 */
	public function get_default_ticket_dispatch_statuses() {
		return array(
			'wc-completed',
			'wc-on-hold',
			'wc-processing',
		);
	}

	/**
	 * @return array
	 */
	public function get_default_ticket_generation_statuses() {
		return array(
			'wc-pending',
			'wc-completed',
			'wc-on-hold',
			'wc-processing',
		);
	}
}
