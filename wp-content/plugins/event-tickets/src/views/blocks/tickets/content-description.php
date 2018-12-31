<?php
/**
 * Block: Tickets
 * Content Description
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/content-description.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$ticket = $this->get( 'ticket' );

if ( ! $ticket->show_description() ) {
	return false;
}
?>
<div
	class="tribe-block__tickets__item__content__description"
>
	<?php echo $ticket->description; ?>
</div>