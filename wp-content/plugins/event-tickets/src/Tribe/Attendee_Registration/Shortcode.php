<?php
/**
* Provides shortcodes for the attendee registration templatee.
* @since 4.10.2
*/
class Tribe__Tickets__Attendee_Registration__Shortcode {
	protected $shortcode_name = 'tribe_attendee_registration';
	protected $params = array();

	public function hook() {
		// block editor has a fit if we don't bail on the admin...don't really need them in other places?
		if ( is_admin() || wp_doing_ajax() || tribe( 'context' )->doing_cron() ) {
			return;
		}

		add_shortcode( $this->shortcode_name, [ $this, 'render' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_scripts'] );
	}

	public function maybe_enqueue_scripts() {
		if (
			is_archive()
			|| is_admin()
		) {
			return;
		}

		$shortcode_page = (int) tribe_get_option( 'ticket-attendee-page-id', 0 );

		// Option is not set, don't enqueue scripts.
		if ( ! $shortcode_page ) {
			return;
		}

		$page = get_queried_object();

		// Not on a shortcode page, don't enqueue scripts.
		if ( ! $page || ! $page instanceof WP_Post || $shortcode_page !== $page->ID ) {
			return;
		}

		// enqueue styles and scripts for this page
		tribe_asset_enqueue( 'event-tickets-registration-page-styles' );
		tribe_asset_enqueue( 'event-tickets-registration-page-scripts' );
	}


	/**
	 * Renders the shortcode AR page.
	 *
	 * @since 4.10.2
	 *
	 * @return string
	 */
	public function render() {
		ob_start();

		// things we need added, arrays so we can filter more if necessary
		$additional_allowed = [
			// forms
			'form' => [
				'class'  => [],
				'id'     => [],
				'name'   => [],
				'value'  => [],
				'type'   => [],
				'action' => [],
				'method' => [],
			],
			// form fields - input
			'input' => [
				'class'   => [],
				'id'      => [],
				'name'    => [],
				'value'   => [],
				'type'    => [],
				'checked' => [],
			],
			// select
			'select' => [
				'class'  => [],
				'id'     => [],
				'name'   => [],
				'value'  => [],
				'type'   => [],
			],
			// select options
			'option' => [
				'selected' => [],
			],
		];

		$allowed_tags = array_merge( wp_kses_allowed_html( 'post' ), $additional_allowed );

		echo wp_kses( tribe( 'tickets.attendee_registration.view' )->display_attendee_registration_page( null, 'shortcode' ), $allowed_tags );

		return ob_get_clean();
	}
}
