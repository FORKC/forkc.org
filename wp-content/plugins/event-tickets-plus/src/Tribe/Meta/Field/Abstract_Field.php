<?php

abstract class Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	const META_PREFIX = '_tribe_tickets_meta_';
	public $id;
	public $label;
	public $slug;
	public $required;
	public $ticket_id;
	public $type;
	public $extra = array();
	public $field_type_name = array();

	abstract public function save_value( $attendee_id, $field, $value );

	/**
	 * Constructor
	 */
	public function __construct( $ticket_id, $data = array() ) {
		$this->ticket_id = $ticket_id;
		$this->post      = tribe_events_get_ticket_event( $this->ticket_id );

		$this->field_type_name = array(
			'checkbox' => __( 'Checkbox', 'event-tickets-plus' ),
			'radio'    => __( 'Radio', 'event-tickets-plus' ),
			'select'   => __( 'Dropdown', 'event-tickets-plus' ),
			'text'     => __( 'Text', 'event-tickets-plus' ),
		);

		$this->initialize_data( $data );
	}

	/**
	 * Given a data set, populate the relevant field object properties
	 *
	 * @since 4.1
	 *
	 * @param array $data Field data
	 */
	public function initialize_data( $data ) {
		if ( ! $data ) {
			return;
		}

		$this->id       = isset( $data['id'] ) ? $data['id'] : null;
		$this->label    = isset( $data['label'] ) ? $data['label'] : null;
		$this->required = isset( $data['required'] ) ? $data['required'] : null;
		$this->extra    = isset( $data['extra'] ) ? $data['extra'] : null;

		if ( $this->label ) {
			$this->slug = sanitize_title( $this->label );
		}
	}

	/**
	 * Applies a filter to check if this field is restricted
	 *
	 * @param  int  $attendee_id Which attendee are we dealing with
	 * @return boolean
	 */
	public function is_restricted( $attendee_id = null ) {
		/**
		 * Allow developers to prevent users to update a specific field
		 * @param boolean $is_meta_field_restricted If is allowed or not
		 * @param int     $attendee_id              Which attendee this update will be done to
		 * @param self    $this                     This Field instance
		 */
		$is_meta_field_restricted = (bool) apply_filters( 'event_tickets_plus_is_meta_field_restricted', false, $attendee_id, $this );

		return $is_meta_field_restricted;
	}

	/**
	 * Renders the field on the front end
	 *
	 * @since 4.1
	 *
	 * @param int $attendee_id ID number of the attendee post
	 *
	 * @return string
	 */
	public function render( $attendee_id = null ) {
		$field = $this->get_field_settings();
		$field = $this->sanitize_field_options_for_render( $field );
		$value = $this->get_field_value( $attendee_id );

		return $this->render_field( $field, $value, $attendee_id );
	}

	/**
	 * Constructs a field meta data array for the meta field
	 *
	 * @since 4.1
	 *
	 * @param array $data Field data
	 *
	 * @return array
	 */
	public function build_field_settings( $data ) {
		$type     = $data['type'];
		$required = isset( $data['required'] ) ? $data['required'] : '';
		$label    = isset( $data['label'] ) ? $data['label'] : "Field {$data_id}";

		$meta = array(
			'type'     => $type,
			'required' => $required,
			'label'    => $label,
			'slug'     => sanitize_title( $label ),
			'extra'    => array(),
		);

		$meta = $this->build_extra_field_settings( $meta, $data );

		return $meta;
	}

	public function build_extra_field_settings( $meta, $data ) {
		return $meta;
	}

	/**
	 * Retrieves the field's settings from post meta
	 *
	 * @since 4.1
	 *
	 * @return array
	 */
	public function get_field_settings() {
		$meta_object    = Tribe__Tickets_Plus__Main::instance()->meta();
		$meta_settings  = (array) $meta_object->get_meta_fields_by_ticket( $this->ticket_id );
		$field_settings = array();

		// loop over the meta field settings attached to the ticket until we find the settings that
		// go with $this specific field
		foreach ( $meta_settings as $setting ) {
			// if the setting label doesn't match $this label, it is a different field. Skip to the next
			// element in the settings array
			if ( $this->label !== $setting->label ) {
				continue;
			}

			// the label matches. Set the field settings that we'll return to the settings from the
			// meta settings stored in the ticket meta
			$field_settings = $setting;
			break;
		}

		/**
		 * Filters the field settings for the instantiated field object
		 *
		 * @var array of field settings
		 * @var Tribe__Tickets_Plus__Meta__Field__Abstract_Field instance
		 */
		$field_settings = apply_filters( 'event_tickets_plus_field_settings', $field_settings, $this );

		return $field_settings;
	}

	/**
	 * Retrieves the value set on the given attendee ticket for the field
	 *
	 * @since 4.1
	 *
	 * @param int $attendee_id ID number of attendee post
	 *
	 * @return array
	 */
	public function get_field_value( $attendee_id ) {
		if ( ! $attendee_id ) {
			return null;
		}

		$value  = null;
		$values = get_post_meta( $attendee_id, Tribe__Tickets_Plus__Meta::META_KEY, true );

		if ( 'checkbox' === $this->type ) {
			foreach ( $this->extra['options'] as $label )  {
				$slug = $this->slug . '_' . sanitize_title( $label );
				if ( ! isset( $values[ $slug ] ) ) {
					continue;
				}

				// Save the Slug for checking which were saved
				$value[] = $slug;
			}

			if ( ! is_array( $value ) ) {
				$value = array();
			}
		} else {
			if ( isset( $values[ $this->slug ] ) ) {
				$value = $values[ $this->slug ];
			}
		}

		return $value;
	}

	/**
	 * Returns a version of checkbox, radio, and dropdown fields with any blank/empty options
	 * removed. All other fields are ignored/unaltered by this.
	 *
	 * @since 4.5.5
	 *
	 * @param object $field The meta field being rendered.
	 *
	 * @return object The same meta field with its "options" cleaned of any empty values.
	 */
	public function sanitize_field_options_for_render( $field ) {
		if ( ! isset( $field->extra['options'] ) || ! is_array( $field->extra['options'] ) ) {
			return $field;
		}

		$field->extra['options'] = array_filter( $field->extra['options'] );
		$field->extra['options'] = array_values( $field->extra['options'] );

		return $field;
	}

	/**
	 * Renders the field as it would be displayed on the front end
	 *
	 * @since 4.1
	 *
	 * @param array $field Field settings
	 * @param string|int|array $value Value of the field
	 *
	 * @return string
	 */
	public function render_field( $field, $value = null, $attendee_id = null ) {
		ob_start();

		$template = sanitize_file_name( "{$field->type}.php" );
		$required = isset( $field->required ) && 'on' === $field->required ? true : false;

		$field = (array) $field;

		include Tribe__Tickets_Plus__Main::instance()->get_template_hierarchy( "meta/{$template}" );

		return ob_get_clean();
	}

	/**
	 * Renders the field settings in the dashboard
	 *
	 * @since 4.1
	 *
	 * @return string
	 */
	public function render_admin_field() {
		$tickets_plus = Tribe__Tickets_Plus__Main::instance();

		$name    = $tickets_plus->plugin_path . 'src/admin-views/meta-fields/' . sanitize_file_name( $this->type ) . '.php';
		$wrapper = $tickets_plus->plugin_path . 'src/admin-views/meta-fields/_field.php';

		if ( ! file_exists( $name ) ) {
			return '';
		}

		$data                     = (array) $this;
		$ticket_specific_settings = $this->get_field_settings();
		$ticket_specific_settings = $this->sanitize_field_options_for_render( $ticket_specific_settings  );
		$data                     = array_merge( $data, (array) $ticket_specific_settings );

		$field_id = rand();
		$type     = $this->type;
		$label    = ! empty( $data['label'] ) ? $data['label'] : '';
		$required = ! empty( $data['required'] ) ? $data['required'] : '';
		$slug     = ! empty( $data['slug'] ) ? $data['slug'] : sanitize_title( $label );
		$extra    = ! empty( $data['extra'] ) ? $data['extra'] : '';

		ob_start();
		if ( ! empty( $this->field_type_name[ $this->type ] ) ) {
			$type_name = $this->field_type_name[ $this->type ];
		} else {
			$type_name = ucwords( $this->type );
		}
		include $wrapper;
		$field = ob_get_clean();

		ob_start();
		include $name;
		$response = str_replace( '##FIELD_EXTRA_DATA##', ob_get_clean(), $field );

		return $response;
	}
}
