<?php
class EM_Multiple_Bookings_ML{
    
    public static function init(){
        add_filter('em_multiple_booking_email_messages','EM_Multiple_Bookings_ML::em_booking_email_messages',10,2);
    }
    
    /**
     * Near copy of EM_ML_Bookings::em_booking_email_messages() but for an EM_Multiple_Booking object.
     * @param array $msg
     * @param EM_Multiple_Booking $EM_Multiple_Booking
     * @return array
     */
    public static function em_booking_email_messages($msg, $EM_Multiple_Booking){
	    //only proceed if booking was in another language AND we're not in the current language given the option is translated automatically
	    if( !empty($EM_Multiple_Booking->booking_meta['lang']) && EM_ML::$current_language != $EM_Multiple_Booking->booking_meta['lang'] ){
	        $lang = $EM_Multiple_Booking->booking_meta['lang'];
            //below is copied script from EM_Multiple_Booking::email_messages() replacing get_option with EM_ML_Options::get_option() supplying the booking language 
    		switch( $EM_Multiple_Booking->booking_status ){
    			case 0:
    			case 5:
    				$msg['user']['subject'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_pending_subject', $lang);
    				$msg['user']['body'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_pending_body', $lang);
    				//admins should get something (if set to)
    				$msg['admin']['subject'] = EM_ML_Options::get_option('dbem_multiple_bookings_contact_email_subject', $lang);
    				$msg['admin']['body'] = EM_ML_Options::get_option('dbem_multiple_bookings_contact_email_body', $lang);
    				break;
    			case 1:
    				$msg['user']['subject'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_confirmed_subject', $lang);
    				$msg['user']['body'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_confirmed_body', $lang);
    				//admins should get something (if set to)
    				$msg['admin']['subject'] = EM_ML_Options::get_option('dbem_multiple_bookings_contact_email_subject', $lang);
    				$msg['admin']['body'] = EM_ML_Options::get_option('dbem_multiple_bookings_contact_email_body', $lang);
    				break;
    			case 2:
    				$msg['user']['subject'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_rejected_subject', $lang);
    				$msg['user']['body'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_rejected_body', $lang);
    				break;
    			case 3:
    				$msg['user']['subject'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_cancelled_subject', $lang);
    				$msg['user']['body'] = EM_ML_Options::get_option('dbem_multiple_bookings_email_cancelled_body', $lang);
    				//admins should get something (if set to)
    				$msg['admin']['subject'] = EM_ML_Options::get_option('dbem_multiple_bookings_contact_email_cancelled_subject', $lang);
    				$msg['admin']['body'] = EM_ML_Options::get_option('dbem_multiple_bookings_contact_email_cancelled_body', $lang);
    				break;
    		}
    	}
	    return $msg;
	}
}
EM_Multiple_Bookings_ML::init();