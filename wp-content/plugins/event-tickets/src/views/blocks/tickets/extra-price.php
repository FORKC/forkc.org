<?php
/**
 * Block: Tickets
 * Extra column, price
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-price.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.11.0
 *
 */

$ticket = $this->get( 'ticket' );
$provider = $this->get( 'provider' );
?>
<div
	class="tribe-common-b2 tribe-common-b1--min-medium tribe-tickets__item__extra__price"
>
	<?php echo tribe( 'tickets.commerce.currency' )->get_formatted_currency_with_symbol( $ticket->price, $post_id, $provider->class_name ) ?>
</div>
