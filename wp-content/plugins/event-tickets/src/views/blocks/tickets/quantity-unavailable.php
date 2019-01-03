<?php
/**
 * Block: Tickets
 * Quantity Unavailable
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/quantity-unavailable.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9.3
 *
 */

$ticket = $this->get( 'ticket' );
?>
<div
	class="tribe-block__tickets__item__quantity__unavailable"
>
	<?php esc_html_e( 'Sold Out', 'event-tickets' ); ?>
</div>