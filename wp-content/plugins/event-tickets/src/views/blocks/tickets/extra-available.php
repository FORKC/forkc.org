<?php
/**
 * Block: Tickets
 * Extra column, available
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9.3
 *
 */

$ticket    = $this->get( 'ticket' );
$available = -1 === $ticket->available() ? esc_html__( 'Unlimited', 'event-tickets' ) : $ticket->available();
?>
<div
	class="tribe-block__tickets__item__extra__available"
>
	<?php if ( -1 === $ticket->available() ) : ?>
		<?php $this->template( 'blocks/tickets/extra-available-unlimited', array( 'ticket' => $ticket, 'key' => $key ) ); ?>
	<?php else: ?>
		<?php $this->template( 'blocks/tickets/extra-available-quantity', array( 'ticket' => $ticket, 'key' => $key ) ); ?>
	<?php endif; ?>
</div>