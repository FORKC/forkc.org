<?php

class Tribe__Tickets_Plus__Meta__Field__Text extends Tribe__Tickets_Plus__Meta__Field__Abstract_Field {
	public $type = 'text';

	public function build_extra_field_settings( $meta, $data ) {
		$multiline = isset( $data['extra'] ) && isset( $data['extra']['multiline'] ) ? $data['extra']['multiline'] : '';

		if ( $multiline ) {
			if ( ! isset( $meta['extra'] ) ) {
				$meta['extra'] = array();
			}

			$meta['extra']['multiline'] = $multiline;
		}

		return $meta;
	}

	public function save_value( $attendee_id, $field, $value ) {
	}
}
