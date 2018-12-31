<?php
/**
 * Block: RSVP
 * Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/content.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

?>
<div class="tribe-block__rsvp__content">

	<div class="tribe-block__rsvp__details__status">
		<?php $this->template( 'blocks/rsvp/details', array( 'ticket' => $ticket ) ); ?>
		<?php $this->template( 'blocks/rsvp/status', array( 'ticket' => $ticket ) ); ?>
	</div>

	<?php $this->template( 'blocks/rsvp/form', array( 'ticket' => $ticket ) ); ?>

</div>