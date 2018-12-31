<?php
/**
 * Block: Tickets
 * Registration Summary Ticket Price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/summary/ticket-icon.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */
?>

<div class="tribe-block__tickets__registration__tickets__item__price">
	<?php echo $ticket->get_provider()->get_price_html( $ticket->ID ); ?>
</div>