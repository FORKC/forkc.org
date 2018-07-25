<?php
$post_id = get_the_ID();

// Go ahead and get the necessary values
// Set up default provider
$modules        = Tribe__Tickets__Tickets::modules();
$default_module = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );

// We don't need this one here - RSVP and tickets are different now.
unset( $modules['Tribe__Tickets__RSVP'] );

$multiple_modules = 1 < count( $modules );
// we use 'screen-reader-text' to hide it if there really aren't any choices
$fieldset_class   = $multiple_modules ? 'input_block' : 'screen-reader-text';
?>

<?php if ( tribe_is_truthy( tribe_get_request_var( 'is_admin', true ) ) ) : ?>
	<fieldset class="<?php echo esc_attr( $fieldset_class ); ?>">
		<?php if ( ! $multiple_modules ) : ?>
			<?php foreach ( $modules as $class => $module ) : ?>
				<input
					type="radio"
					class="tribe-ticket-editor-field-default_provider settings_field"
					name="tribe-tickets[settings][default_provider]"
					id="provider_<?php echo esc_attr( $class . '_radio' ); ?>"
					value="<?php echo esc_attr( $class ); ?>"
					checked
				>
			<?php endforeach; ?>
		<?php else : ?>
			<section style="margin-bottom: 0;">
				<legend id="default_ticket_provider_legend" class="ticket_form_left"><?php esc_html_e( 'Sell tickets using:', 'event-tickets' ); ?></legend>
				<p class="ticket_form_right"><?php esc_attr_e( 'It looks like you have multiple ecommerce plugins active. We recommend running only one at a time. However, if you need to run multiple, please select which one to use to sell tickets for this event.' ); ?> <em><?php esc_attr_e( 'Note: adjusting this setting will only impact new tickets. Existing tickets will not change. We highly recommend that all tickets for one event use the same ecommerce plugin.', 'event-tickets' ); ?></em></p>
				<?php foreach ( $modules as $class => $module ) : ?>
					<label class="ticket_form_right" for="provider_<?php echo esc_attr( $class . '_radio' ); ?>">
						<input
							<?php checked( $default_module, $class ); ?>
							type="radio"
							name="tribe-tickets[settings][default_provider]"
							id="provider_<?php echo esc_attr( $class . '_radio' ); ?>"
							value="<?php echo esc_attr( $class ); ?>"
							class="tribe-ticket-editor-field-default_provider settings_field ticket_field"
							aria-labelledby="default_ticket_provider_legend"
						>
						<?php
						/**
						 * Allows for editing the module name before display
						 *
						 * @since 4.6
						 *
						 * @param string $module - the name of the module
						 */
						echo apply_filters( 'tribe_events_tickets_module_name', esc_html( $module ) );
						?>
					</label>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>
	</fieldset>
<?php else: ?>
	<input
		type="hidden"
		name="tribe-tickets[settings][default_provider]"
		value="<?php echo $default_module ?>"
		class="tribe-ticket-editor-field-default_provider settings_field ticket_field"
	>
<?php endif;
