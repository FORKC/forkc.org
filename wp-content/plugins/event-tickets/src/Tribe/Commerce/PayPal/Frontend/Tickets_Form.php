<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form
 *
 * @since 4.7
 */
class Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form {

	/**
	 * Whether the form has rendered already or not
	 *
	 * @var bool
	 */
	protected $has_rendered = false;

	/**
	 * @var Tribe__Tickets__Commerce__PayPal__Main
	 */
	protected $main;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Frontend__Tickets_Form constructor.
	 *
	 * @since 4.7
	 *
	 * @param \Tribe__Tickets__Commerce__PayPal__Main $main
	 */
	public function __construct( Tribe__Tickets__Commerce__PayPal__Main $main ) {
		$this->main = $main;
	}

	/**
	 * Modifies the passed content to inject the front-end tickets form.
	 *
	 * @since 4.7
	 *
	 * @return void The method will echo in the context of a buffered output.
	 *
	 * @see   Tribe__Tickets__Tickets::front_end_tickets_form_in_content
	 */
	public function render() {
		if ( $this->has_rendered || ! $this->main->is_active() ) {
			return;
		}

		$post = get_post();

		if ( empty( $post ) ) {
			return;
		}

		// For recurring events (child instances only), default to loading tickets for the parent event
		if ( ! empty( $post->post_parent ) && function_exists( 'tribe_is_recurring_event' ) && tribe_is_recurring_event( $post->ID ) ) {
			$post = get_post( $post->post_parent );
		}

		$tickets = $this->main->get_tickets( $post->ID );

		if ( empty( $tickets ) ) {
			return;
		}

		Tribe__Tickets__Tickets::add_frontend_stock_data( $tickets );

		$ticket_sent = empty( $_GET['tpp_sent'] ) ? false : true;

		if ( $ticket_sent ) {
			$this->main->add_message( __( 'Your PayPal Ticket has been received! Check your email for your PayPal Ticket confirmation.', 'event-tickets' ), 'success' );
		}

		$ticket_error = empty( $_GET['tpp_error'] ) ? false : (int) $_GET['tpp_error'];

		if ( $ticket_error ) {
			$this->main->add_message( Tribe__Tickets__Commerce__PayPal__Errors::error_code_to_message( $ticket_error ), 'error' );
		}

		$ticket_message = empty( $_GET['tpp_message'] ) ? false : (int) $_GET['tpp_message'];

		if ( $ticket_message ) {
			$this->main->add_message( Tribe__Tickets__Commerce__PayPal__Errors::error_code_to_message( $ticket_message ), 'update' );
		}

		$must_login = ! is_user_logged_in() && $this->main->login_required();
		$can_login  = true;

		$form = '';

		$currently_available_tickets = array_filter( $tickets, array( $this, 'is_currently_available' ) );

		if ( count( $currently_available_tickets ) > 0 ) {
			ob_start();
			include $this->main->getTemplateHierarchy( 'tickets/tpp' );
			$form = ob_get_clean();

			// If we have rendered tickets there is generally no need to display a 'tickets unavailable' message
			// for this post
			$this->main->do_not_show_tickets_unavailable_message();
		} else {
			// Indicate that we did not render any tickets, so a 'tickets unavailable' message may be
			// appropriate (depending on whether other ticket providers are active and have a similar
			// result)
			$this->main->maybe_show_tickets_unavailable_message( $tickets );
		}

		// It's only done when it's included
		$this->has_rendered = true;

		echo $form;
	}

	/**
	 * Sets whether the form rendered already or not.
	 *
	 * @since 4.7
	 *
	 * @param bool $has_rendered
	 */
	public function has_rendered( $has_rendered ) {
		$this->has_rendered = (bool) $has_rendered;
	}

	/**
	 * A utility method to filter the list of tickets by their currently available status.
	 *
	 * @since 4.7
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket
	 *
	 * @return bool
	 */
	protected function is_currently_available( Tribe__Tickets__Ticket_Object $ticket ) {
		return $ticket->date_in_range();
	}
}