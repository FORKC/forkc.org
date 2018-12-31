<?php
/**
 * Block: Tickets
 * Registration Summary Ticket
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/summary/ticket.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__tickets__item">

	<?php $this->template( 'blocks/tickets/registration/summary/ticket-icon', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'blocks/tickets/registration/summary/ticket-quantity', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'blocks/tickets/registration/summary/ticket-title', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php $this->template( 'blocks/tickets/registration/summary/ticket-price', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

</div>