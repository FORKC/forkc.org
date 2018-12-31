<?php
/**
 * Class Tribe__Tickets_Plus__Service_Provider
 *
 * Provides the Events Tickets Plus service.
 *
 * This class should handle implementation binding, builder functions and hooking for any first-level hook and be
 * devoid of business logic.
 *
 * @since 4.6
 */
class Tribe__Tickets_Plus__Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.6
	 */
	public function register() {
		$this->container->singleton( 'tickets-plus.assets', new Tribe__Tickets_Plus__Assets() );
		$this->container->singleton( 'tickets-plus.admin.views', 'Tribe__Tickets_Plus__Admin__Views' );
		$this->container->singleton( 'tickets-plus.editor', 'Tribe__Tickets_Plus__Editor', array( 'hook' ) );

		$this->container->singleton( 'tickets-plus.commerce.warnings', new Tribe__Tickets_Plus__Commerce__Warnings );

		// We use String here to specifically not load it before used
		$this->container->singleton( 'tickets-plus.commerce.woo', 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' );
		$this->container->singleton( 'tickets-plus.commerce.edd', 'Tribe__Tickets_Plus__Commerce__EDD__Main' );

		// Check In Status
		$this->container->singleton( 'tickets-plus.commerce.edd.checkin-stati', 'Tribe__Tickets_Plus__Commerce__EDD__CheckIn_Stati' );
		$this->container->singleton( 'tickets-plus.commerce.woo.checkin-stati', 'Tribe__Tickets_Plus__Commerce__WooCommerce__CheckIn_Stati' );

		// QR code support
		$this->container->singleton( 'tickets-plus.qr.site-settings', 'Tribe__Tickets_Plus__QR__Settings', array( 'hook' ) );

		// Meta
		$this->container->singleton( 'tickets-plus.meta.contents', 'Tribe__Tickets_Plus__Meta__Contents' );

		// additional service providers
		$this->container->register( 'Tribe__Tickets_Plus__Commerce__PayPal__Service_Provider' );

		// EDD specific
		$this->container->singleton( 'tickets-plus.commerce.edd.orders', 'Tribe__Tickets_Plus__Commerce__EDD__Orders_Report' );

		// Repositories, we replace the original bindings to expand the repository with ET+ functions
		$this->container->bind( 'tickets.ticket-repository', 'Tribe__Tickets_Plus__Ticket_Repository' );
		$this->container->bind( 'tickets.attendee-repository', 'Tribe__Tickets_Plus__Attendee_Repository' );

		$this->hook();
	}

	/**
	 * Any hooking for any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 4.6
	 */
	protected function hook() {
		tribe( 'tickets-plus.editor' );
		tribe( 'tickets-plus.qr.site-settings' );

		if ( is_admin() ) {
			tribe( 'tickets-plus.admin.views' );
		}

		tribe( 'tickets-plus.assets' )->enqueue_scripts();
		tribe( 'tickets-plus.assets' )->admin_enqueue_scripts();
		tribe( 'tickets-plus.editor' );

		add_filter( 'event_tickets_attendees_edd_checkin_stati', tribe_callback( 'tickets-plus.commerce.edd.checkin-stati', 'filter_attendee_ticket_checkin_stati' ), 10 );
		add_filter( 'tribe_filter_attendee_order_link', tribe_callback( 'tickets-plus.commerce.edd.orders', 'filter_attendee_order_link' ), 10, 2 );
	}
}
