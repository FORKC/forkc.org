<?php
/**
 * Edit Event Tickets
 *
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/tickets/orders.php
 *
 * @package TribeEventsCalendar
 *
 * @since 4.7.4
 * @since 4.10.2 Only show Update button if ticket has meta.
 * @since 4.10.8 Show Update button if current user has either RSVP or Ticket with meta. Do not use the now-deprecated third parameter of `get_description_rsvp_ticket()`.
 * @since 4.10.9 Use function for text.
 *
 * @version 4.11.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Event Tickets Plus would set this from its own injected template to let us know about editable values
global $tribe_my_tickets_have_meta;

$view = Tribe__Tickets__Tickets_View::instance();
$event_id = get_the_ID();
$event = get_post( $event_id );
$post_type = get_post_type_object( $event->post_type );
$user_id = get_current_user_id();
$tribe_my_tickets_have_meta = false;

/**
 * Display a notice if the user doesn't have tickets
 */
if ( ! $view->has_ticket_attendees( $event_id, $user_id ) && ! $view->has_rsvp_attendees( $event_id, $user_id ) ) {
	Tribe__Notices::set_notice( 'ticket-no-results', esc_html( sprintf( _x( "You don't have %s for this event", 'notice if user does not have tickets', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'notice_user_does_not_have_tickets' ) ) ) );
}

$is_event_page = class_exists( 'Tribe__Events__Main' ) && Tribe__Events__Main::POSTTYPE === $event->post_type;
?>

<div id="tribe-events-content" class="tribe-events-single">
	<p class="tribe-back">
		<a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>">
			<?php printf( '&laquo; ' . esc_html__( 'View %s', 'event-tickets' ), $post_type->labels->singular_name ); ?>
		</a>
	</p>

	<?php if ( $is_event_page ): ?>
	<?php the_title( '<h1 class="tribe-events-single-event-title">', '</h1>' ); ?>

	<div class="tribe-events-schedule tribe-clearfix">
		<?php echo tribe_events_event_schedule_details( $event_id, '<h2>', '</h2>' ); ?>
		<?php if ( tribe_get_cost() ) : ?>
			<span class="tribe-events-cost"><?php echo tribe_get_cost( null, true ) ?></span>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Notices -->
	<?php tribe_the_notices() ?>

	<form method="post">

	<?php tribe_tickets_get_template_part( 'tickets/orders-rsvp' ); ?>

	<?php
	if ( ! class_exists( 'Tribe__Tickets_Plus__Commerce__PayPal__Meta' ) ) {
		tribe_tickets_get_template_part( 'tickets/orders-pp-tickets' );
	}
	?>


	<?php
	/**
	 * Fires before the process tickets submission button is rendered
	 */
	do_action( 'tribe_tickets_orders_before_submit' );
	?>

	<?php if (
		// Current user has RSVP (with or without meta) so needs to be able to edit status
		$view->has_rsvp_attendees( $event_id, get_current_user_id() )
		|| (
			// Current user has tickets with meta so needs to be able to edit meta
			$view->has_ticket_attendees( $event_id, get_current_user_id() )
			&& $tribe_my_tickets_have_meta
		)
	) : ?>
		<div class="tribe-submit-tickets-form">
			<button type="submit" name="process-tickets" value="1" class="button alt"><?php echo sprintf( esc_html__( 'Update %s', 'event-tickets' ), $view->get_description_rsvp_ticket( $event_id, get_current_user_id() ) ); ?></button>
		</div>
	<?php endif;
	// unset our global since we don't need it any more
	unset( $tribe_my_tickets_have_meta );
	?>
	</form>
</div>
