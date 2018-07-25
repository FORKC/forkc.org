<?php

class tc_event_location_element extends TC_Ticket_Template_Elements {

	var $element_name		 = 'tc_event_location_element';
	var $element_title		 = 'Event Location';
	var $font_awesome_icon	 = '<i class="fa fa-map-marker"></i>';

	function on_creation() {
		$this->element_title = apply_filters( 'tc_event_location_element_title', __( 'Event Location', 'tc' ) );
	}
        
        function advanced_admin_element_settings() {
            echo $this->get_att_fonts();
            echo $this->get_font_colors();
            echo $this->get_font_sizes();
            echo $this->get_font_style();
            echo $this->get_default_text_value(__( 'Grosvenor Square, Mayfair, London', 'tc' ));
        }

	function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {
		if ( $ticket_instance_id ) {
			$ticket_instance = new TC_Ticket( (int) $ticket_instance_id );
			$ticket			 = new TC_Ticket();
			$event_id		 = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );
			return apply_filters( 'tc_event_location_element', get_post_meta( $event_id, 'event_location', true ) );
		} else {
			if ( $ticket_type_id ) {
				$ticket_type = new TC_Ticket( (int) $ticket_type_id );
				$event_id	 = $ticket_type->get_ticket_event( $ticket_type_id );
				$event		 = new TC_Event( $event_id );
				return apply_filters( 'tc_event_location_element', $event->details->event_location );
			} else {
				return apply_filters( 'tc_event_location_element_default', __( 'Grosvenor Square, Mayfair, London', 'tc' ) );
			}
		}
	}

}

tc_register_template_element( 'tc_event_location_element', __( 'Event Location', 'tc' ) );
