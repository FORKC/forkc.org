<?php
/**
 * @var Tribe__Tickets__Commerce__PayPal__Main $this
 * @var string|int                             $ticket_capacity
 * @var string|int                             $post_capacity
 * @var string                                 $global_stock_mode
 * @var int                                    $global_stock_cap
 */
$provider = get_class( $this );
$has_post_capacity = ! empty( $post_capacity );

if ( $this->supports_global_stock() ) {
	$capped = Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $global_stock_mode;
	$value_global_mode = $capped ? Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE : Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE;
	?>
	<fieldset
		id="<?php echo $provider; ?>_ticket_global_stock"
		class="input_block tribe-dependent"
		data-depends="#Tribe__Tickets__RSVP_radio"
		data-condition-is-not-checked
	>
		<legend id="tpp_ticket_form_cap_mode" class="ticket_form_label ticket_form_left"><?php esc_html_e( 'Capacity:', 'event-tickets-plus' ); ?></legend>

		<div class="input_block ticket_form_right">
			<label for="<?php echo $provider; ?>_global" class="ticket_field">
				<input
					type="radio"
					id="<?php echo $provider; ?>_global"
					aria-labelledby="tpp_ticket_form_cap_mode"
					class="ticket_field tribe-ticket-field-mode"
					name="tribe-ticket[mode]"
					value="<?php echo esc_attr( $value_global_mode ); ?>"
					<?php checked( $global_stock_mode, $value_global_mode ); ?>
				>
				<?php esc_html_e( 'Share capacity with other tickets', 'event-tickets-plus' ); ?>
				<span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Shared capacity ticket types share a common pool of tickets for all attendees', 'event-tickets-plus' ); ?>"></span>
			</label>
			<div
				id="<?php echo $provider; ?>_global_stock_block"
				class="tribe-dependent tribe_stock_block"
				data-depends="#<?php echo $provider; ?>_global"
				data-condition-is-checked
			>
				<?php if ( ! $has_post_capacity ) : ?>
					<div class="global_capacity-wrapper">
						<label for="<?php echo $provider; ?>_global_capacity" class="ticket_form_label"><?php esc_html_e( 'Set shared capacity:', 'event-tickets-plus' ); ?></label>
						<input
							type="number"
							name="tribe-ticket[event_capacity]"
							id="<?php echo $provider; ?>_global_capacity"
							aria-labelledby="tpp_ticket_form_cap_mode"
							class="ticket_field tribe-ticket-field-event-capacity small-text"
							value="<?php echo esc_attr( $post_capacity ); ?>"
							data-validation-is-required
							data-validation-error="<?php esc_attr_e( 'Please set the Shared Capacity.', 'event-tickets-plus' ); ?>"
						>
						<span class="tribe-tickets-global-sales"></span>
					</div>
				<?php else : ?>
					<input
						type="hidden"
						name="tribe-ticket[event_capacity]"
						id="<?php echo $provider; ?>_global_capacity"
						class="ticket_field tribe-ticket-field-event-capacity small-text"
						value="<?php echo esc_attr( $post_capacity ); ?>"
					>
				<?php endif; ?>

				<div>
					<label for="<?php echo $provider; ?>_global_stock_cap"><?php esc_html_e( 'Sell up to:', 'event-tickets-plus' ); ?></label>
					<input
						type="number"
						id="<?php echo $provider; ?>_global_stock_cap"
						name="tribe-ticket[capacity]"
						class="ticket_field tribe-ticket-field-capacity small-text"
						size="7"
						value="<?php echo esc_attr( $global_stock_mode === $value_global_mode || $ticket_capacity >= 0 ? $ticket_capacity : null ); ?>"
						data-validation-is-less-or-equal-to=".tribe-ticket-field-event-capacity"
						data-validation-error="<?php esc_attr_e( 'Shared capacity ticket types share a common pool of tickets for all attendees', 'event-tickets-plus' ); ?>"
						<?php echo ! is_null( $post_capacity ) ? 'max="' . esc_attr( $post_capacity ) . '"' : '' ?>
						<?php echo ! is_null( $post_capacity ) ? 'placeholder="' . esc_attr( $post_capacity ) . '"' : '' ?>
					/>
					<p class="tribe-description-small">
						<?php esc_html_e( 'Optional: limit sales to portion of the shared capacity', 'event-tickets-plus' ) ?>
						<span class="tribe-ticket-capacity-max">
							<?php echo sprintf( __( '(max %s)', 'event-tickets-plus' ), '<span class="tribe-ticket-capacity-value">' . $post_capacity . '</span>' ); ?>
						</span>
					</p>
				</div>
			</div>
		</div>

		<div class="input_block">
			<label for="<?php echo $provider; ?>_own" class="ticket_field">
				<input
					type="radio"
					id="<?php echo $provider; ?>_own"
					aria-labelledby="tpp_ticket_form_cap_mode"
					class="ticket_field tribe-ticket-field-mode"
					name="tribe-ticket[mode]"
					value="own"
					<?php checked( $global_stock_mode, 'own' ); ?>
				>
				<?php esc_html_e( 'Set capacity for this ticket only', 'event-tickets-plus' ); ?>
				<span class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Ticket capacity will only be used by attendees buying this ticket type', 'event-tickets-plus' ); ?>"></span>
			</label>
			<div
				id="<?php echo $provider; ?>_own_stock_block"
				class="tribe-dependent tribe_stock_block"
				data-depends="#<?php echo $provider; ?>_own"
				data-condition-is-checked
			>
				<div>
					<label for="<?php echo $provider; ?>_capacity"><?php esc_html_e( 'Capacity:', 'event-tickets-plus' ); ?></label>
					<input
						type="text"
						id="<?php echo $provider; ?>_capacity"
						name="tribe-ticket[capacity]"
						class="ticket_field ticket_stock"
						size="7"
						value="<?php echo esc_attr( $ticket_capacity >= 0 ? $ticket_capacity : null ); ?>"
						data-validation-is-required
						data-validation-error="<?php esc_attr_e( 'Please set the Capacity for this ticket.', 'event-tickets-plus' ); ?>"
					/>
				</div>
			</div>
		</div>

		<div class="input_block">
			<label for="<?php echo $provider; ?>_unlimited" class="ticket_field">
				<input
					type="radio"
					id="<?php echo $provider; ?>_unlimited"
					aria-labelledby="tpp_ticket_form_cap_mode"
					class="ticket_field tribe-ticket-field-mode"
					name="tribe-ticket[mode]"
					value=""
					<?php checked( $global_stock_mode, '' ); ?>
				/>
				<?php esc_html_e( 'Unlimited capacity', 'event-tickets-plus' ); ?>
				<span
					class="tribe-dependent"
					data-depends="#<?php echo $provider; ?>_unlimited"
					data-condition-is-checked
				></span>
			</label>
		</div>
	</fieldset>
	<?php
}
