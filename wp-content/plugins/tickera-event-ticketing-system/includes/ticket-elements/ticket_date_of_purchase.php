<?php

class tc_ticket_date_purchase_element extends TC_Ticket_Template_Elements {

	var $element_name		 = 'tc_ticket_date_purchase_element';
	var $element_title		 = 'Date of Purchase';
	var $font_awesome_icon	 = '<i class="fa fa-calendar"></i>';

	function on_creation() {
		$this->element_title = apply_filters( 'tc_ticket_date_purchase_element_title', __( 'Date of Purchase', 'tc' ) );
	}

	function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {
		
		 if ( $ticket_instance_id ) {
			$ticket_instance 	= new TC_Ticket( (int) $ticket_instance_id );
			$ticket			 	= new TC_Ticket();
			$post_id		 	= $ticket_instance->details->post_parent;
			$post 			 	= get_post($post_id);
			$post_type		 	= $post->post_type;
			$post_purchase_date	= $post->post_date;
			$purchase_date	 	= date_i18n( get_option( 'date_format' ), strtotime( $post_purchase_date ) );
			$purchase_time	 	= date_i18n( get_option( 'time_format' ), strtotime( $post_purchase_date ) );
			$date_purchase		=  $purchase_date . ' ' . $purchase_time;
			
			return apply_filters( 'tc_ticket_date_purchase_element',$date_purchase);
		}else {

			if ( $ticket_type_id ) {
				$ticket_type 		 = new TC_Ticket( (int) $ticket_type_id );
				$post_id		 	 = $ticket_type->id;
				$post 				 = get_post($post_id);
				$post_purchase_date	 = $post->post_date;
				$purchase_date	 	 = date_i18n( get_option( 'date_format' ), strtotime( $post_purchase_date ) );
				$purchase_time	 	 = date_i18n( get_option( 'time_format' ), strtotime( $post_purchase_date ) );
				$date_purchase		 =  $purchase_date . ' ' . $purchase_time ;
				
				return apply_filters( 'tc_ticket_date_purchase_element',$date_purchase);
			}else {

				return apply_filters( 'tc_ticket_date_purchase_element', date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), time(), false ) );

			}
		}
	}

}

tc_register_template_element( 'tc_ticket_date_purchase_element', __( 'Date of Purchase', 'tc' ) );
