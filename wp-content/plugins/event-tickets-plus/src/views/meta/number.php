<?php
/**
 * Renders number field
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/meta/number.php
 *
 * @version 4.3.5
 *
 */

$option_id = "tribe-tickets-meta_{$this->slug}" . ( $attendee_id ? '_' . $attendee_id : '' );
?>
<div class="tribe-tickets-meta tribe-tickets-meta-number <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<label for="<?php echo esc_attr( $option_id ); ?>"><?php echo wp_kses_post( $field['label'] ); ?></label>
	<input <?php disabled( $this->is_restricted( $attendee_id ) ); ?> type="number" id="<?php echo esc_attr( $option_id ); ?>" class="ticket-meta" name="tribe-tickets-meta[<?php echo $attendee_id ?>][<?php echo esc_attr( $this->slug ); ?>]" value="<?php echo esc_attr( $value ); ?>" <?php echo $required ? 'required' : ''; ?>>
</div>
