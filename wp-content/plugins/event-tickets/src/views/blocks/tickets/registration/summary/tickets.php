<?php
/**
 * Block: Tickets
 * Registration Summary Tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/summary/tickets.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

?>
<div class="tribe-block__tickets__registration__tickets">

	<?php foreach ( $tickets as $key => $ticket ) : ?>

		<?php $this->template( 'blocks/tickets/registration/summary/ticket', array( 'ticket' => $ticket, 'key' => $key ) ); ?>

	<?php endforeach; ?>

</div>