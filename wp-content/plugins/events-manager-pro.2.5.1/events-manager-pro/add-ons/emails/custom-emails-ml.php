<?php
class EM_Custom_Emails_ML{
	/**
	 * Adds some ML hooks
	 */
	public static function init(){
		//hooks for event-specific emails
	    add_filter('em_custom_emails_event_messages', 'EM_Custom_Emails_ML::em_custom_emails_event_messages',111,2);
		add_filter('em_custom_emails_event_admin','EM_Custom_Emails_ML::em_custom_emails_event_admin', 101, 2);
		//hooks for gateway-specific emails
		add_filter('em_custom_emails_gateway_messages', 'EM_Custom_Emails_ML::em_custom_emails_gateway_messages',101,3);
		add_filter('em_custom_emails_gateway_admin', 'EM_Custom_Emails_ML::em_custom_emails_gateway_admin',101,3);
	}

    
	/**
	 * Checks if the event linked to EM_Booking is in the language we're currently requesting, 
	 * otherwise call the same function EM_Custom_Emails will have passed again with the overriding EM_Event object.
	 * @param array $custom_emails
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	public static function em_custom_emails_event_messages( $custom_emails, $EM_Booking ){
	    if( !empty($EM_Booking->booking_meta['lang']) ){
	        $custom_emails = array();
		    //get the translated event
	        $EM_Event = EM_ML::get_translation($EM_Booking->get_event(), $EM_Booking->booking_meta['lang']);
	        //check that we're not already dealing with the translated event
	        if( $EM_Event->post_id != $EM_Booking->get_event()->post_id ){
	            //get custom emails belonging to this event translation
	            $custom_emails_ml = EM_Custom_Emails::get_event_emails($EM_Event);
	            //merge the emails belonging to this event translation with the original event, overriding original event if anything is set
        	    $custom_emails = EM_Custom_Emails::merge_emails_array($custom_emails, $custom_emails_ml);
        	}
	    }
	    return $custom_emails;
	}
	
    /**
	 * Checks if the event linked to EM_Booking is in the language we're currently requesting, 
	 * otherwise call the same function EM_Custom_Emails will have passed again with the overriding EM_Event object.
	 * @param array $emails
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	public static function em_custom_emails_event_admin( $admin_emails, $EM_Booking ){
	    if( !empty($EM_Booking->booking_meta['lang']) ){
		    //get the translated event
	        $EM_Event = EM_ML::get_translation($EM_Booking->get_event(), $EM_Booking->booking_meta['lang']);
	        //return custom email and override the emails created in event_admin_emails if we aren't already dealing with the right event translation
    	    if( $EM_Event->post_id != $EM_Booking->get_event()->post_id ){
    	        //merge these emails, they'll overwrite the original language custom admin emails, the original event owner will receive all emails
    	        $admin_emails = array_merge( $admin_emails, EM_Custom_Emails::get_event_admin_emails($EM_Event) );
    	    }
	    }
	    return $admin_emails;
	}
    
	/**
	 * Checks if the event linked to EM_Booking is in the language we're currently requesting, 
	 * otherwise call the same function EM_Custom_Emails will have passed again with the overriding EM_Event object.
	 * @param array $gateway_emails
	 * @param EM_Booking $EM_Booking
	 * @param EM_Gateway $EM_Gateway
	 * @return array
	 */
	public static function em_custom_emails_gateway_messages( $gateway_emails, $EM_Booking, $EM_Gateway ){
	    //if booking language is set, get the translation
		if( !empty($EM_Booking->booking_meta['lang']) ){
		    $lang = $EM_Booking->booking_meta['lang']; //easy reference
            //unserlialize saved translations for gateway emails and check whether we have 
            $gateway_emails_ml = maybe_unserialize($EM_Gateway->get_option('emails_ml'));
		    if( !empty($gateway_emails_ml[$lang]) ){
		        //translations exist, so merge them into gateway emails for filter return
		        $gateway_emails =  EM_Custom_Emails::merge_emails_array($gateway_emails, $gateway_emails_ml[$lang]);
		    }
		}
	    return $gateway_emails;
	}
	
    /**
     * Merges translations of gateway emails into default gateway emails if booking is not the default language.
	 * @param array $admin_emails
	 * @param EM_Booking $EM_Booking
	 * @param EM_Gateway $EM_Gateway
	 * @return array
	 */
	public static function em_custom_emails_gateway_admin( $admin_emails, $EM_Booking, $EM_Gateway ){
	    if( !empty($EM_Booking->booking_meta['lang']) ){
    		$lang = $EM_Booking->booking_meta['lang'];
    		//get admin emails for this language
		    $possible_email_values_ml = maybe_unserialize($EM_Gateway->get_option('emails_admins_ml'));
			$admin_emails_ml = empty($possible_email_values_ml[$lang]) ? array():$possible_email_values_ml[$lang];
    		//convert all comma-delimited values into an array for merging
    		foreach( $admin_emails_ml as $k => $v ) $admin_emails_ml[$k] = !empty($v) ? explode(',', $v) : array();
    		//merge new emails, they will overwrite original language emails for this gateway
    		$admin_emails = array_merge($admin_emails, $admin_emails_ml);
	    }
	    return $admin_emails;
	}
}
EM_Custom_Emails_ML::init();