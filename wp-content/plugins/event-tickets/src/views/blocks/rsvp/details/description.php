<?php
/**
 * Block: RSVP
 * Details Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/details/description.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

if ( ! $ticket->show_description() ) {
	return;
}
?>
<div class="tribe-block__rsvp__description">
	<?php echo wpautop( esc_html( $ticket->description ) ); ?>
</div>