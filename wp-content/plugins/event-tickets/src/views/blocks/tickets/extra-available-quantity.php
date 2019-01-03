<?php
/**
 * Block: Tickets
 * Extra column, available Quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available-quantity.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9.3
 *
 */


$ticket    = $this->get( 'ticket' );
?>
<span class="tribe-block__tickets__item__extra__available_quantity"><?php echo esc_html( $ticket->available() ); ?></span>
<?php esc_html_e( 'available', 'event-tickets' );