<?php
/**
 * Block: RSVP
 * Icon
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/icon.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9.3
 *
 */

?>
<div class="tribe-block__rsvp__icon">
	<?php $this->template( 'blocks/rsvp/icon-svg' ); ?>
	<?php esc_html_e( 'RSVP', 'event-tickets' ) ?>
</div>
