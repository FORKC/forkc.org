<?php
abstract class Tribe__Tickets_Plus__APM__Abstract_Filter {

	/**
	 * @var array
	 */
	protected $active = array();

	/**
	 * @var array
	 */
	protected $query_search_options = array();

	/**
	 * Tribe__Tickets_Plus__APM__Abstract_Filter constructor.
	 */
	public function __construct() {
		$this->set_up_query_search_options();

		$type = $this->type;
		add_filter( 'tribe_custom_row' . $type, array( $this, 'form_row' ), 10, 4 );
		add_filter( 'tribe_maybe_active' . $type, array( $this, 'maybe_set_active' ), 10, 3 );
		add_action( 'tribe_after_parse_query', array( $this, 'parse_query' ), 10, 2 );

		add_action(
			'manage_' . Tribe__Events__Main::POSTTYPE . '_posts_custom_column',
			array( $this, 'post_custom_column' ),
			10,
			2
		);
	}

	/**
	 * Sets up the `$query_search_options` field.
	 */
	abstract protected function set_up_query_search_options();

	/**
	 * Returns the markup to use for the row added to the filters.
	 *
	 * @param string $return The original output for the row.
	 * @param string $key The filter identifying key.
	 * @param arrray|string $value The current filter values.
	 * @param bool $unused_filter Whether the filter is beind used or not.
	 *
	 * @return string
	 */
	public function form_row( $return, $key, $value, $unused_filter ) {
		$value  = (array) $value;
		$value  = wp_parse_args( $value,
			array( 'is' => '', 'value' => '', $this->key() => true ) );
		$return = tribe_select_field( 'is_' . $key, $this->query_search_options, $value['is'] );
		$return .= sprintf( '<input name="%s" value="%s" type="text" />', $key, esc_attr( $value['value'] ) );

		return $return;
	}

	/**
	 * Whether the filter should be set to active or not according to the current request.
	 *
	 * @param array $return The original return value.
	 * @param string $key The filter identifying key.
	 * @param array $filter An array of filter details.
	 *
	 * @return array
	 */
	public function maybe_set_active( $return, $key, $filter ) {
		global $ecp_apm;

		if ( isset( $_POST[ $key ] ) && isset( $_POST[ 'is_' . $key ] ) ) {
			return array(
				'value'      => $_POST[ $key ],
				'is'         => $_POST[ 'is_' . $key ],
				$this->key() => true,
			);
		}

		$active_filters = $ecp_apm->filters->get_active();

		if ( isset( $active_filters[ $key ] ) && isset( $active_filters[ 'is_' . $key ] ) ) {
			return array(
				'value'      => $active_filters[ $key ],
				'is'         => $active_filters[ 'is_' . $key ],
				$this->key() => true,
			);
		}

		return $return;
	}

	/**
	 * Parses the current query and eventually hooks post results manipulation functions if the filter is active and
	 * needs those.
	 *
	 * @param WP_Query $wp_query_current
	 * @param array $active An array of active filters.
	 */
	public function parse_query( $wp_query_current, $active ) {
		if ( empty( $active ) ) {
			return;
		}

		global $wp_query;

		foreach ( $active as $key => $field ) {
			if ( isset( $field[ $this->key() ] ) ) {
				$this->active[ $key ] = $field;
			}
		}

		add_filter( 'posts_results', array( $this, 'filter_posts_results' ), 10, 1 );
	}

	/**
	 * Filters the post results applying the filter criteria.
	 *
	 * @param WP_Post[] $posts
	 *
	 * @return WP_Post[]
	 */
	public function filter_posts_results( array $posts ) {
		global /** @var wpdb $wpdb */
		$ecp_apm, $wpdb;
		// run once
		remove_filter( 'posts_results', array( $this, 'filter_posts_results' ), 10, 1 );

		foreach ( $this->active as $key => $active ) {
			$constraint = $active['is'];
			$value      = $active['value'];

			if ( ! is_numeric( $value ) ) {
				continue;
			}

			$posts = $this->filter_posts_by( $constraint, intval( $value ), $posts );
		}

		return $posts;
	}

	/**
	 * Filters the post according to a constraint.
	 *
	 * @param string $constraint The constraint to apply, e.g. `is not`
	 * @param string|int $value The current constraint value
	 * @param WP_Post[] $posts
	 *
	 * @return WP_Post[] The filtered post list
	 */
	public function filter_posts_by( $constraint, $value, $posts ) {
		/** @var WP_Post $event */
		foreach ( $posts as $index => $event ) {
			$total_value = $this->get_total_value( $event );

			switch ( $constraint ) {
				case 'is':
					if ( $total_value != $value ) {
						unset( $posts[ $index ] );
					}
					break;
				case 'not':
					if ( $total_value == $value ) {
						unset( $posts[ $index ] );
					}
					break;
				case 'gte':
					if ( $total_value < $value ) {
						unset( $posts[ $index ] );
					}
					break;
				case 'lte':
					if ( $total_value > $value ) {
						unset( $posts[ $index ] );
					}
					break;
			}
		}

		return array_values( $posts );
	}

	/**
	 * Returns the value of the filter for a post.
	 *
	 * @param string $column A column identifier.
	 * @param int $post_id
	 *
	 * @return int|void|WP_Error
	 */
	public function post_custom_column( $column, $post_id ) {
		if ( $column !== $this->key() ) {
			return;
		}

		echo esc_html( $this->get_total_value( $post_id ) );
	}

	/**
	 * Returns the filter identifying key.
	 *
	 * Workaround for missing late static binding.
	 *
	 * @return mixed
	 */
	abstract protected function key();

}
