<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class Tribe__Tickets_Plus__Tickets_View {

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return self
	 *
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance instanceof self ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Hook the necessary filters and Actions!
	 *
	 * @static
	 * @return self
	 */
	public static function hook() {
		$myself = self::instance();

		add_action( 'event_tickets_after_attendees_update', array( $myself, 'save_meta' ) );
		add_action( 'event_tickets_orders_attendee_contents', array( $myself, 'output_attendee_meta' ) );
		add_filter( 'tribe_tickets_template_paths', array( $myself, 'add_template_path' ) );
		add_action( 'tribe_tickets_orders_rsvp_item', array( $myself, 'add_meta_to_rsvp' ), 10, 2 );
		add_action( 'tribe_tickets_orders_before_submit', array( $myself, 'output_ticket_order_form' ) );
		add_action( 'event_tickets_user_details_rsvp', array( $myself, 'output_attendee_list_checkbox' ), 10, 2 );
		add_action( 'event_tickets_user_details_tickets', array( $myself, 'output_attendee_list_checkbox' ), 10, 2 );

		return $myself;
	}

	/**
	 * Filter template paths to add the ET+ paths
	 *
	 * @param  array $paths
	 * @return array $paths
	 */
	public function add_template_path( $paths ) {
		$paths['plus'] = Tribe__Tickets_Plus__Main::instance()->plugin_path;
		return $paths;
	}

	/**
	 * Saves the Attendee meta changes
	 *
	 * @param  int $event_id Which event this change applies to
	 * @return void
	 */
	public function save_meta( $event_id ) {
		$user_id   = get_current_user_id();
		$attendees = Tribe__Tickets__Tickets::get_event_attendees( $event_id );

		// this block only runs for Tickets
		if ( isset( $_POST['attendee'] ) && ! empty( $_POST['event_id'] ) ) {
			$event_id = absint( $_POST['event_id'] );

			$attendees_by_order = $this->get_event_attendees_by_order( $event_id, $user_id );

			foreach ( $_POST['attendee'] as $order_id => $order_data ) {
				if ( ! isset( $attendees_by_order[ $order_id ] ) ) {
					continue;
				}

				$first_attendee = reset( $attendees_by_order[ $order_id ] );

				if ( ! isset( $first_attendee['provider'] ) ) {
					continue;
				}

				$optout = empty( $_POST['optout'][ $order_id ] ) ? false : true;

				$provider = call_user_func( array( $first_attendee['provider'], 'get_instance' ) );

				foreach ( $attendees_by_order[ $order_id ] as $attendee ) {
					$attendee_owner = $this->get_attendee_owner( $attendee['attendee_id'] );

					if ( $user_id !== $attendee_owner ) {
						continue;
					}

					$provider_class = $attendee['provider'];
					if ( ! defined( "{$provider_class}::ATTENDEE_OPTOUT_KEY" ) ) {
						$attendee_optout_key = call_user_func( array( $provider_class, 'get_key' ), 'ATTENDEE_OPTOUT_KEY' );
					} else {
						$attendee_optout_key = constant( "{$provider_class}::ATTENDEE_OPTOUT_KEY" );
					}

					update_post_meta( $attendee['attendee_id'], $attendee_optout_key, $optout );
				}
			}
		}

		// If we don't have the Meta we skip the rest
		if ( empty( $_POST['tribe-tickets-meta'] ) ) {
			return;
		}

		$attendees_data = $_POST['tribe-tickets-meta'];

		foreach ( $attendees_data as $attendee_id => $data ) {
			$attendee_owner = $this->get_attendee_owner( $attendee_id );

			// Only saves if this user is the owner
			if ( $user_id != $attendee_owner ) {
				continue;
			}

			/**
			 * Allow developers to prevent users to update specific Attendees or Events
			 * @param boolean $is_meta_update_allowed If is allowed or not
			 * @param int     $event_id               Which event this applies to
			 * @param int     $attendee_id            Which attendee this update will be done to
			 * @param array   $data                   Data that will be saved
			 */
			$is_meta_restricted = apply_filters( 'event_tickets_plus_is_meta_restricted', false, $event_id, $attendee_id, $data );

			// Just skip if this is not allowed
			if ( $is_meta_restricted ) {
				continue;
			}

			// Fetches the Attendee data based on the ID
			foreach ( $attendees as $attendee ) {
				if ( isset( $attendee['attendee_id'] ) && $attendee['attendee_id'] === $attendee_id ) {
					break;
					// When it breaks the foreach it will keep the $attendee variable as the one where it broke off
				}
			}

			$fields = Tribe__Tickets_Plus__Meta::instance()->get_meta_fields_by_ticket( $attendee['product_id'] );

			foreach ( $fields as $field ) {
				// Don't remove the data if not restricted
				if ( ! $field->is_restricted( $attendee_id ) ) {
					continue;
				}

				$name = null;
				if ( 'checkbox' === $field->type ) {
					foreach ( $field->extra['options'] as $label ) {
						$name = $field->slug . '_' . sanitize_title( $label );
						if ( isset( $data[ $name ] ) ) {
							unset( $data[ $name ] );
						}
					}
				} else {
					if ( isset( $data[ $field->slug ] ) ) {
						unset( $data[ $field->slug ] );
					}
				}
			}

			// Updates the meta information associated with individual attendees
			update_post_meta( $attendee_id, Tribe__Tickets_Plus__Meta::META_KEY, $data );
		}
	}

	/**
	 * Add the template for Editing Meta on an RSVP
	 * @param array $attendee Attendee information
	 * @param int $i          Index of the Attendee
	 */
	public function add_meta_to_rsvp( $attendee, $i ) {
		$args = array(
			'order_id' => $attendee['order_id'],
			'order'    => $attendee,
			'attendee' => $attendee,
			'i'        => $i,
		);
		tribe_tickets_get_template_part( 'tickets-plus/orders-edit-meta', null, $args );
	}

	/**
	 * Outputs custom attendee meta for RSVP attendee order records
	 *
	 * @param array $attendee Attendee data
	 */
	public function output_attendee_meta( $attendee ) {
		$args = array(
			'attendee' => $attendee,
		);

		tribe_tickets_get_template_part( 'tickets-plus/orders-edit-meta', null, $args );
	}

	/**
	 * Gets an attendee owner from attendee meta
	 *
	 * @param int $attendee_id The Attendee ID
	 *
	 * @return int
	 */
	public function get_attendee_owner( $attendee_id ) {
		return (int) get_post_meta( $attendee_id, Tribe__Tickets__Tickets::ATTENDEE_USER_ID, true );
	}

	/**
	 * Fetches from the Cached attendees list the ones that are relevant for this user and event
	 * Important to note that this method will bring the attendees organized by order id
	 *
	 * @param  int       $event_id      The Event ID it relates to
	 * @param  int|null  $user_id       An Optional User ID
	 * @param  boolean   $include_rsvp  If this should include RSVP, which by default is false
	 * @return array                    List of Attendees grouped by order id
	 */
	public function get_event_attendees_by_order( $event_id, $user_id = null, $include_rsvp = false ) {
		$attendees = Tribe__Tickets__Tickets::get_event_attendees( $event_id );
		$orders = array();

		foreach ( $attendees as $key => $attendee ) {
			// Ignore RSVP if we don't tell it specifically
			if ( 'rsvp' === $attendee['provider_slug'] && ! $include_rsvp ) {
				continue;
			}

			// If we have a user_id then test it and ignore the ones that don't have it
			if ( ! is_null( $user_id ) ) {
				if ( empty( $attendee['user_id'] ) || $attendee['user_id'] != $user_id ) {
					continue;
				}
			}

			$orders[ (int) $attendee['order_id'] ][] = $attendee;
		}

		return $orders;
	}

	/**
	 * Outputs tickets form
	 *
	 */
	public function output_ticket_order_form() {
		tribe_tickets_get_template_part( 'tickets-plus/orders-tickets' );
	}

	/**
	 * Outputs the attendee list checkbox
	 *
	 */
	public function output_attendee_list_checkbox( $attendee_group, $post_id ) {
		if ( Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $post_id ) ) {
			return;
		}
		$first_attendee = reset( $attendee_group );

		$args = array(
			'attendee_group' => $attendee_group,
			'post_id'        => $post_id,
			'first_attendee' => $first_attendee,
		);

		if ( doing_action( 'event_tickets_user_details_rsvp' ) ) {
			$template_part = 'tickets-plus/attendee-list-checkbox-rsvp';
		} else {
			$template_part = 'tickets-plus/attendee-list-checkbox-tickets';
		}
		tribe_tickets_get_template_part( $template_part, null, $args );
	}
}
