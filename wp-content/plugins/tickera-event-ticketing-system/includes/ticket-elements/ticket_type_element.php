<?php

class tc_ticket_type_element extends TC_Ticket_Template_Elements {

	var $element_name		 = 'tc_ticket_type_element';
	var $element_title		 = 'Ticket Type';
	var $font_awesome_icon	 = '<i class="fa fa-ticket"></i>';

	function on_creation() {
		$this->element_title = apply_filters( 'tc_ticket_type_element_title', __( 'Ticket Type', 'tc' ) );
	}

	function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {
		if ( $ticket_instance_id ) {
			$ticket_instance = new TC_Ticket( (int) $ticket_instance_id );
			$ticket			 = new TC_Ticket( $ticket_instance->details->ticket_type_id );
			return htmlspecialchars(apply_filters( 'tc_ticket_type_element', apply_filters( 'tc_checkout_owner_info_ticket_title', $ticket->details->post_title, $ticket_instance->details->ticket_type_id, array(), $ticket_instance_id ) ));
		} else {
			if ( $ticket_type_id ) {
				$ticket_type = new TC_Ticket( (int) $ticket_type_id );
				return htmlspecialchars(apply_filters( 'tc_ticket_type_element', $ticket_type->details->post_title ));
			} else {
				return apply_filters( 'tc_ticket_type_element_default', __( 'VIP Ticket', 'tc' ) );
			}
		}
	}

}

tc_register_template_element( 'tc_ticket_type_element', __( 'Ticket Type', 'tc' ) );
