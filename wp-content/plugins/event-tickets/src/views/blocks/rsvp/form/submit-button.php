<?php
/**
 * Block: RSVP
 * Form Submit Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/submit-button.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9.3
 *
 */

?>
<button
	type="submit"
	name="tickets_process"
	value="1"
	class="tribe-block__rsvp__submit-button"
>
	<?php esc_html_e( 'Submit RSVP', 'event-tickets' ); ?>
</button>
