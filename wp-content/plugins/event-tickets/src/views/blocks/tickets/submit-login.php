<?php
/**
 * Block: Tickets
 * Submit Login
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/submit-login.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9.3
 *
 */

?>
<a href="<?php echo esc_url( Tribe__Tickets__Tickets::get_login_url() ); ?>">
	<?php esc_html_e( 'Log in to purchase', 'event-tickets' ); ?>
</a>