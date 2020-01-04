<?php

class tc_event_categories_element extends TC_Ticket_Template_Elements {

	var $element_name		 = 'tc_event_categories_element';
	var $element_title		 = 'Event Category';
	var $font_awesome_icon	 = '<i class="fa fa-list"></i>';

	function on_creation() {
		$this->element_title = apply_filters( 'tc_event_categories_element_title', __( 'Event Category', 'tc' ) );
	}

	function get_event_categories( $event_id ) {
				$terms = get_the_terms( $event_id , 'event_category' );
				$count_cate = count($terms);

				$cate_name = '';
				$cate_name = $terms[0]->name;
				
				
				if($cate_name != ''){
					if($count_cate == 1){
						$cats_name = ucfirst($cate_name);
					}else{
						foreach ($terms as $cate ) {
							$cats_name .= ucfirst($cate->name).', ';
						}
						$cats_name = rtrim($cats_name,', ');
					}
					
				}
				
				//$cats_name = ucfirst($terms[0]->name);

		return $cats_name;
	}

	function ticket_content( $ticket_instance_id = false, $ticket_type_id = false ) {
		
		 if ( $ticket_instance_id ) {
			$ticket_instance = new TC_Ticket( (int) $ticket_instance_id );
			$ticket			 = new TC_Ticket();
			$event_id		 = $ticket->get_ticket_event( apply_filters( 'tc_ticket_type_id', $ticket_instance->details->ticket_type_id ) );

			$event_category = $this->get_event_categories( $event_id );

			return apply_filters( 'tc_event_categories_element',$event_category);
		}else {
			if ( $ticket_type_id ) {
				$ticket_type = new TC_Ticket( (int) $ticket_type_id );
				$event_id	 = $ticket_type->get_ticket_event( $ticket_type_id );
				
				$event_category = $this->get_event_categories( $event_id );

				return apply_filters( 'tc_event_categories_element',$event_category);
			} else {
				return apply_filters( 'tc_event_categories_element',__('Category','tc'));
			}
		}
	}

}

tc_register_template_element( 'tc_event_categories_element', __( 'Event Category', 'tc' ) );
