<?php

class Tribe__Tickets_Plus__Meta__Contents {
	public function get_ticket_stored_meta( $tickets = array() ) {
		$stored_data = array();
		$storage     = new Tribe__Tickets_Plus__Meta__Storage;

		foreach ( $tickets as $ticket_id => $quantity ) {
			$stored_data[ $ticket_id ] = $storage->get_meta_data_for( $ticket_id );
		}

		return $stored_data;
	}

	/**
	 * Determines if the provided ticket/quantity array of tickets has all of the stored meta up to date
	 *
	 * Up to date means: Do all tickets have an entry in the storage transient and are all required fields populated?
	 *
	 * @since 4.9
	 *
	 * @param array $quantity_by_ticket_id Array indexed by ticket id with ticket quantities as the values
	 * @return boolean
	 */
	public function is_stored_meta_up_to_date( $quantity_by_ticket_id = array() ) {
		// if there aren't any tickets, consider them up to date
		if ( empty( $quantity_by_ticket_id ) ) {
			return true;
		}

		$stored_data = $this->get_ticket_stored_meta( $quantity_by_ticket_id );
		$meta        = Tribe__Tickets_Plus__Main::instance()->meta();
		$up_to_date  = true;

		foreach ( $quantity_by_ticket_id as $ticket_id => $quantity ) {
			$should_have_meta = tribe_is_truthy( get_post_meta( $ticket_id, Tribe__Tickets_Plus__Meta::ENABLE_META_KEY, true ) );
			$data             = empty( $stored_data[ $ticket_id ] ) ? array() : $stored_data[ $ticket_id ];
			$ticket_meta      = $meta->get_meta_fields_by_ticket( $ticket_id );

			// Continue if the ticket doesn't have any meta
			if ( empty( $ticket_meta ) && ! $should_have_meta ) {
				continue;
			}

			// If the data for this ticket is empty, we return false
			// That way we ensure that the users get to see the
			// registration page even if they have non-mandatory fields
			if ( empty( $data[ $ticket_id ] ) ) {
				return false;
			}

			// Bail if the number of items stored for that ticket is lower
			// than the $quantity in the cart
			if ( count( $data[ $ticket_id ] ) < $quantity ) {
				return false;
			}

			// Going through the stored data, to see if there's a required field missing
			foreach ( $ticket_meta as $meta_field ) {
				$meta_slug = $meta_field->slug;

				if ( ! $meta->meta_is_required( $ticket_id, $meta_slug ) ) {
					continue;
				}

				foreach ( $data as $the_ticket => $the_meta ) {
					if ( empty( $the_meta ) ) {
						return false;
					}

					foreach ( $the_meta as $attendee_number => $meta_item ) {

						// Give special treatment to checkboxes as they store differently
						// from the rest of the fields.
						if ( 'checkbox' === $meta_field->type ) {

							// If it's an array and it's not empty
							// Means that the checkbox has values
							// continue to the next element
							if (
								is_array( $meta_item )
								&& ! empty( $meta_item )
							) {
								continue;
							}

							return false;
						}

						if (
							! isset( $meta_item[ $meta_slug ] )
							|| '' === $meta_item[ $meta_slug ]
						) {
							return false;
						}
					}
				}
			}
		}

		return true;
	}
}