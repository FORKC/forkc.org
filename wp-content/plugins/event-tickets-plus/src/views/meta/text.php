<?php
/**
 * Renders text field
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/meta/text.php
 *
 * @version 4.3.5
 *
 */
$multiline = isset( $field['extra'] ) && isset( $field['extra']['multiline'] ) ? $field['extra']['multiline'] : '';
$option_id = "tribe-tickets-meta_{$this->slug}" . ( $attendee_id ? '_' . $attendee_id : '' );
?>
<div class="tribe-tickets-meta tribe-tickets-meta-text <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<label for="<?php echo esc_attr( $option_id ); ?>"><?php echo wp_kses_post( $field['label'] ); ?></label>
	<?php
	if ( $multiline ) {
		?>
		<textarea
			id="<?php echo esc_attr( $option_id ); ?>"
			class="ticket-meta"
			name="tribe-tickets-meta[<?php echo $attendee_id ?>][<?php echo esc_attr( $this->slug ); ?>]"
			<?php echo $required ? 'required' : ''; ?>
			<?php disabled( $this->is_restricted( $attendee_id ) ); ?>
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php
	} else {
		?>
		<input
			type="text"
			id="<?php echo esc_attr( $option_id ); ?>"
			class="ticket-meta"
			name="tribe-tickets-meta[<?php echo $attendee_id ?>][<?php echo esc_attr( $this->slug ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			<?php echo $required ? 'required' : ''; ?>
			<?php disabled( $this->is_restricted( $attendee_id ) ); ?>
		>
		<?php
	}
	?>
</div>
