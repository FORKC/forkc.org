<?php
/**
 * Block: Tickets
 * Submit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */


$must_login = ! is_user_logged_in() && $ticket->get_provider()->login_required();
?>
<?php if ( $must_login ) : ?>
	<?php $this->template( 'blocks/tickets/submit-login' ); ?>
<?php else : ?>
	<?php $this->template( 'blocks/tickets/submit-button' ); ?>
<?php endif; ?>