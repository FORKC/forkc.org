<?php

class Tribe__Tickets__Commerce__Currency {

	/**
	 * @var string
	 */
	public $currency_code;

	/* Currency mapping code to symbol and position */
	public $currency_code_options_map = array();

	/**
	 * Class constructor
	 *
	 * @since 4.7
	 */
	public function __construct() {
		$this->currency_code = tribe_get_option( 'ticket-commerce-currency-code', 'USD' );
		$this->generate_default_currency_map();
	}

	/**
	 * Hooks the actions and filters required by the class.
	 *
	 * @since 4.7
	 */
	public function hook(  ) {
		add_filter( 'tribe_currency_symbol', array( $this, 'filter_currency_symbol' ), 10, 2 );
		add_filter( 'tribe_currency_cost', array( $this, 'filter_currency_cost' ), 10, 2 );
		add_filter( 'tribe_reverse_currency_position', array( $this, 'reverse_currency_symbol_position' ), 10, 2 );
		add_filter( 'get_post_metadata', array( $this, 'filter_cost_meta' ), 10, 4 );
	}

	/**
	 * Get and allow filtering of the currency symbol.
	 *
	 * @since 4.7
	 *
	 * @param int|null $post_id
	 * @param bool $decode Whether to HTML decode the currency symbol before
	 *                     returning or not.
	 *
	 * @return string
	 */
	public function get_currency_symbol( $post_id = null, $decode = false ) {
		$symbol = $this->currency_code_options_map[ $this->currency_code ]['symbol'];
		$symbol = apply_filters( 'tribe_commerce_currency_symbol', $symbol, $post_id );

		return $decode ? html_entity_decode( $symbol ) : $symbol;
	}

	/**
	 * Get and allow filtering of the currency symbol
	 * @param int|null $post_id
	 *
	 * @return string
	 */
	public function filter_currency_symbol( $unused_currency_symbol, $post_id = null ) {
		$default_provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );

		return $this->get_provider_symbol( $default_provider, $post_id );
	}

	/**
	 * Filter the cost of the ticket.
	 *
	 * @param string $cost
	 * @param int $post_id
	 *
	 * @return string
	 */
	public function filter_currency_cost( $cost = '', $post_id = 0 ) {
		$default_provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );

		return $this->get_provider_cost( $default_provider, $cost );
	}

	/**
	 * Get and allow filtering of the currency symbol position
	 *
	 * @param int|null $post_id
	 *
	 * @return string
	 */
	public function get_currency_symbol_position( $post_id = null ) {
		if ( ! isset( $this->currency_code_options_map[ $this->currency_code ]['position'] ) ) {
			$currency_position = 'prefix';
		} else {
			$currency_position = $this->currency_code_options_map[ $this->currency_code ]['position'];
		}

		return apply_filters( 'tribe_commerce_currency_symbol_position', $currency_position, $post_id );
	}

	/**
	 * Filters of the currency symbol position on event displays
	 * @param int|null $post_id
	 *
	 * @return string
	 */
	public function reverse_currency_symbol_position( $unused_reverse_position, $post_id = null ) {

		return $this->get_currency_symbol_position( $post_id ) !== 'prefix';
	}

	/**
	 * Format the currency using the currency_code_options_map
	 * @param      $cost
	 * @param null $post_id
	 *
	 * @return string
	 */
	public function format_currency( $cost, $post_id = null ) {
		$post_id = Tribe__Main::post_id_helper( $post_id );
		$currency_symbol   = $this->get_currency_symbol( $post_id );
		$currency_position = $this->get_currency_symbol_position( $post_id );

		$use_currency_locale = tribe_get_option( 'ticket-commerce-use-currency-locale', false );

		/**
		 * Whether the currency's own locale should be used to format the price or not.
		 *
		 * @since 4.7
		 *
		 * @param bool             $use_currency_locale If `true` then the currency own locale will override the site one.
		 * @param string|int|float $cost                The cost to format without the symbol.
		 * @param int              $post_id             The current post ID if any.
		 */
		$use_currency_locale = apply_filters( 'tribe_tickets_commerce_price_format_use_currency_locale', $use_currency_locale, $cost, $post_id );

		if ( ! $use_currency_locale ) {
			$cost = number_format_i18n( $cost, 2 );
		} else {
			$cost = number_format(
				$cost,
				2,
				$this->get_currency_locale( 'decimal_point' ),
				$this->get_currency_locale( 'thousands_sep' )
			);
		}

		$cost = $currency_position === 'prefix'
			? $currency_symbol . $cost
			: $cost . $currency_symbol;

		return $cost;
	}

	/**
	 * Generates the default map and allows for filtering
	 *
	 * @since 4.7
	 *
	 * @see https://en.wikipedia.org/wiki/Decimal_separator for separators informmation
	 */
	public function generate_default_currency_map() {
		$default_map = array(
			'AUD' => array(
				'name'          => __( 'Australian Dollar (AUD)', 'event-tickets' ),
				'symbol'        => '&#x41;&#x24;',
				'thousands_sep' => ',',
				'decimal_point' => '.',
			),
			'BRL' => array(
				'name'   => __( 'Brazilian Real  (BRL)', 'event-tickets' ),
				'symbol' => '&#82;&#x24;',
				'thousands_sep' => '.',
				'decimal_point' => ',',
			),
			'CAD' => array(
				'name'   => __( 'Canadian Dollar (CAD)', 'event-tickets' ),
				'symbol' => '&#x24;',
				'thousands_sep' => ',',
				'decimal_point' => '.',
			),
			'CHF' => array(
				'name'   => __( 'Swiss Franc (CHF)', 'event-tickets' ),
				'symbol' => '&#x43;&#x48;&#x46;',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'CZK' => array(
				'name'     => __( 'Czech Koruna (CZK)', 'event-tickets' ),
				'symbol'   => '&#x4b;&#x10d;',
				'position' => 'postfix',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'DKK' => array(
				'name'   => __( 'Danish Krone (DKK)', 'event-tickets' ),
				'symbol' => '&#107;&#114;',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'EUR' => array(
				'name'   => __( 'Euro (EUR)', 'event-tickets' ),
				'symbol' => '&#8364;',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'GBP' => array(
				'name'   => __( 'Pound Sterling (GBP)', 'event-tickets' ),
				'symbol' => '&#163;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'HKD' => array(
				'name'   => __( 'Hong Kong Dollar (HKD)', 'event-tickets' ),
				'symbol' => '&#x24;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'HUF' => array(
				'name'   => __( 'Hungarian Forint (HUF)', 'event-tickets' ),
				'symbol' => '&#x46;&#x74;',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'ILS' => array(
				'name'   => __( 'Israeli New Sheqel (ILS)', 'event-tickets' ),
				'symbol' => '&#x20aa;',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'JPY' => array(
				'name'   => __( 'Japanese Yen (JPY)', 'event-tickets' ),
				'symbol' => '&#165;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'MYR' => array(
				'name'   => __( 'Malaysian Ringgit (MYR)', 'event-tickets' ),
				'symbol' => '&#82;&#77;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'MXN' => array(
				'name'   => __( 'Mexican Peso (MXN)', 'event-tickets' ),
				'symbol' => '&#x24;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'NOK' => array(
				'name'   => __( 'Norwegian Krone (NOK)', 'event-tickets' ),
				'symbol' => '',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'NZD' => array(
				'name'   => __( 'New Zealand Dollar (NZD)', 'event-tickets' ),
				'symbol' => '&#x24;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'PHP' => array(
				'name'   => __( 'Philippine Peso (PHP)', 'event-tickets' ),
				'symbol' => '&#x20b1;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'PLN' => array(
				'name'   => __( 'Polish Zloty (PLN)', 'event-tickets' ),
				'symbol' => '&#x7a;&#x142;',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'SEK' => array(
				'name'   => __( 'Swedish Krona (SEK)', 'event-tickets' ),
				'symbol' => '&#x6b;&#x72;',
				'decimal_point' => ',',
				'thousands_sep' => '.',
			),
			'SGD' => array(
				'name'   => __( 'Singapore Dollar (SGD)', 'event-tickets' ),
				'symbol' => '&#x53;&#x24;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'THB' => array(
				'name'   => __( 'Thai Baht (THB)', 'event-tickets' ),
				'symbol' => '&#x0e3f;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'TWD' => array(
				'name'   => __( 'Taiwan New Dollar (TWD)', 'event-tickets' ),
				'symbol' => '&#x4e;&#x54;&#x24;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
			'USD' => array(
				'name'   => __( 'U.S. Dollar (USD)', 'event-tickets' ),
				'symbol' => '&#x24;',
				'decimal_point' => '.',
				'thousands_sep' => ',',
			),
		);

		/**
		 * Filters the currency code options map.
		 *
		 * @since 4.7
		 *
		 * @param array $default_map An associative array mapping currency codes
		 *                           to their respective name and symbol.
		 */
		$this->currency_code_options_map = apply_filters( 'tribe_tickets_commerce_currency_code_options_map', $default_map );
	}

	/**
	 * Creates the array for a currency drop-down using only code & name
	 *
	 * @since 4.7
	 *
	 * @return array
	 */
	public function generate_currency_code_options() {
		$options = array_combine(
			array_keys( $this->currency_code_options_map ),
			wp_list_pluck( $this->currency_code_options_map, 'name' )
		);

		/**
		 * Filters the currency code options shown to the user in the settings.
		 *
		 * @since 4.7
		 *
		 * @param array $options
		 */
		return apply_filters( 'tribe_tickets_commerce_currency_code_options', $options );
	}

	/**
	 * Filters the symbol and symbol position meta to use the Tickets currency code when an event
	 * has tickets.
	 *
	 * @param null|mixed $meta      The original meta value.
	 * @param int        $object_id The current post ID
	 * @param string     $meta_key  The requested meta key.
	 * @param bool       $single    Whether this is a request to fetch only one meta value or all.
	 *
	 * @return string
	 */
	public function filter_cost_meta( $meta, $object_id, $meta_key, $single ) {
		if ( ! in_array( $meta_key, array( '_EventCurrencySymbol', '_EventCurrencyPosition' ), true ) ) {
			return $meta;
		}

		if ( ! tribe_events_has_tickets( $object_id ) ) {
			return $meta;
		}

		if ( $single ) {
			$default_provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $object_id );

			switch ( $meta_key ) {
				case '_EventCurrencySymbol':
					return $this->get_provider_symbol( $default_provider, $object_id );
				case '_EventCurrencyPosition':
					return $this->get_provider_symbol_position( $default_provider, $object_id );
			}
		}

		return $meta;
	}

	/**
	 * Returns the currency symbol depending on the provider.
	 *
	 * @since 4.7
	 *
	 * @param string $provider  The ticket provider class name
	 * @param int    $object_id The post ID
	 *
	 * @return string
	 */
	public function get_provider_symbol( $provider, $object_id ) {
		if ( ! class_exists( $provider ) ) {
			return $this->get_currency_symbol( $object_id );
		}

		if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === $provider && function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return get_woocommerce_currency_symbol();
		}

		if ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' === $provider && function_exists( 'edd_currency_symbol' ) ) {
			return edd_currency_symbol();
		}

		if ( tribe( 'tickets.commerce.paypal' )->is_active() ) {
			return $this->get_currency_symbol( $object_id );
		}

		return tribe_get_option( 'defaultCurrencySymbol', '$' );

	}

	/**
	 * Returns the cost applying separators from Woo or EDD depends on the provider.
	 *
	 * @since 4.7
	 *
	 * @param string $provider
	 * @param string $cost
	 *
	 * @return string
	 */
	protected function get_provider_cost( $provider = '', $cost = '' ) {
		if ( ! class_exists( $provider ) ) {
			return $cost;
		}

		if (
			'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === $provider
			&& function_exists( 'wc_format_localized_price' )
		) {
			return wc_format_localized_price( $cost );
		}

		if (
			'Tribe__Tickets_Plus__Commerce__EDD__Main' === $provider
			&& function_exists( 'edd_format_amount' )
		) {
			return edd_format_amount( $cost );
		}

		return $cost;
	}

	/**
	 * Returns the currency symbol position depending on the provider.
	 *
	 * @since 4.7
	 *
	 * @param string $provider  The ticket provider class name
	 * @param int    $object_id The post ID
	 *
	 * @return string
	 */
	public function get_provider_symbol_position( $provider, $object_id ) {
		if ( ! class_exists( $provider ) ) {
			return $this->get_currency_symbol_position( $object_id );
		}

		if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === $provider ) {
			$position = get_option( 'woocommerce_currency_pos' );

			return in_array( $position, array( 'left', 'left_space' ) )
				? 'prefix'
				: 'postfix';
		}

		if ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' === $provider && function_exists( 'edd_get_option' ) ) {
			$position = edd_get_option( 'currency_position', 'before' );

			return 'before' === $position ? 'prefix' : 'postfix';
		}

		return $this->get_currency_symbol_position( $object_id );
	}

	/**
	 * Returns a locale information associated with the current currency or the specified one.
	 *
	 * @since 4.7
	 *
	 * @param string $key
	 * @param string $currency_code
	 *
	 * @return string
	 */
	public function get_currency_locale( $key, $currency_code = null ) {
		$currency_code = null === $currency_code ? $this->currency_code : strtoupper( $currency_code );

		$default       = reset( $this->currency_code_options_map );
		$currency_data = Tribe__Utils__Array::get( $this->currency_code_options_map, $currency_code, $default );

		return Tribe__Utils__Array::get( $currency_data, $key, '' );
	}

	/**
	 * Returns the currency 3-letter codes for a symbol.
	 *
	 * @since 4.8
	 *
	 * @param string $symbol A currency symbol in escaped or unescaped form.
	 *
	 * @return array|string All the currency codes matching a currency symbol.
	 */
	public function get_symbol_codes( $symbol ) {
		// if its already a 3-letter code return it immediately
		if ( array_key_exists( strtoupper( $symbol ), $this->currency_code_options_map ) ) {
			return strtoupper( $symbol );
		}

		$encoded = 0 === strpos( $symbol, '&#' )
			? $symbol
			: htmlentities( $symbol, ENT_COMPAT );

		$matches = wp_list_filter( $this->currency_code_options_map, array( 'symbol' => $encoded ) );

		return count( $matches ) > 0 ? array_keys( $matches ) : array();
	}

	/**
	 * Returns a map of 3-letter currency codes and their unescaped symbol.
	 *
	 * @since 4.8
	 *
	 * @param array|string $codes A currency 3-letter code or a list of them.
	 *
	 * @return array A map of currency 3-letter codes to their symbols; shape
	 *               [ <code> => <symbol> ], e.g. [ USD => '&#x24;' ]
	 */
	public function get_symbols_for_codes( $codes ) {
		$codes = (array) $codes;

		$symbols = array();
		foreach ( $this->currency_code_options_map as $code => $data ) {
			if ( ! in_array( $code, $codes, true ) ) {
				continue;
			}
			$symbols[ $code ] = $data['symbol'];
		}

		return $symbols;
	}

	/**
	 * Returns the current Tribe Commerce currency code.
	 *
	 * @since 4.8
	 *
	 * @return string The current Tribe Commerce 3-letter currency code,
	 *                e.g. "USD".
	 */
	public function get_currency_code() {
		return $this->currency_code;
	}
}
