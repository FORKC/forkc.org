<?php
/**
 * This template renders a Single Ticket content
 * composed by Title and Description currently
 *
 * @version 4.9
 *
 */
$field    = $this->get( 'field' );
$required = isset( $field->required ) && 'on' === $field->required ? true : false;
$field    = (array) $field;

$options = null;

if ( isset( $field['extra'] ) && ! empty( $field['extra']['options'] ) ) {
	$options = $field['extra']['options'];
}

if ( ! $options ) {
	return;
}

$attendee_id   = $key;
$is_restricted = false;
$slug          = $field['slug'];
$field_name    = 'tribe-tickets-meta[' . $ticket->ID . '][' . $attendee_id . '][' . esc_attr( $slug ) . ']';
?>
<div class="tribe-field tribe-block__tickets__item__attendee__field__radio <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<header class="tribe-tickets-meta-label">
		<h3><?php echo wp_kses_post( $field['label'] ); ?></h3>
	</header>

	<div class="tribe-options" aria-role="radiogroup">
		<?php
		foreach ( $options as $option ) :
			$option_slug = sanitize_title( $option );
			$option_id   = "tribe-tickets-meta_{$slug}" . ( $attendee_id ? '_' . $attendee_id : '' ) . "_{$option_slug}";
			?>
			<label for="<?php echo esc_attr( $option_id ); ?>" class="tribe-tickets-meta-field-header">
				<input
					type="radio"
					id="<?php echo esc_attr( $option_id ); ?>"
					class="ticket-meta"
					name="<?php echo esc_attr( $field_name ); ?>"
					value="<?php echo esc_attr( $option ); ?>"
					<?php checked( $option, $value ); ?>
					<?php disabled( $is_restricted ); ?>
				/>
				<span class="tribe-tickets-meta-option-label">
					<?php echo wp_kses_post( $option ); ?>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
</div>
