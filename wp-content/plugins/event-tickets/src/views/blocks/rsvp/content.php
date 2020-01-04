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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */
$going = ! empty( $_GET[ 'going' ] ) ? sanitize_text_field( $_GET[ 'going' ] ) : '';
?>
<div class="tribe-block__rsvp__content">

	<div class="tribe-block__rsvp__details__status">
		<?php $this->template( 'blocks/rsvp/details', array( 'ticket' => $ticket ) ); ?>
		<?php $this->template( 'blocks/rsvp/status', array( 'ticket' => $ticket, 'going' => $going ) ); ?>
	</div>

	<?php $this->template( 'blocks/rsvp/form', array( 'ticket' => $ticket, 'going' => $going ) ); ?>

</div>
