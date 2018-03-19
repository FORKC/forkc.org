<?php
/**
 * Handles MultiLingual stuff for Events Manager Pro
 *
 */
class EMP_ML {
    public static function init(){
        //translatable options
        add_filter('em_ml_translatable_options','EMP_ML::em_ml_translatable_options');
        if( get_option('dbem_multiple_bookings') ){
   	        add_filter('option_dbem_multiple_bookings_checkout_page','EM_ML_Options::get_translated_page');
        }
    }
    
    /**
     * Translate options specific to EM Pro
     * @param array $options
     * @return array:
     */
    public static function em_ml_translatable_options( $options ){
        $options[] = 'dbem_emp_booking_form_error_required';
		//email reminders
		$options[] = 'dbem_emp_emails_reminder_subject';
		$options[] = 'dbem_emp_emails_reminder_body';
		//multiple bookings
        if( get_option('dbem_multiple_bookings') ){
            $options[] = 'dbem_multiple_bookings_feedback_added';
            $options[] = 'dbem_multiple_bookings_feedback_loading_cart';
            $options[] = 'dbem_multiple_bookings_feedback_already_added';
            $options[] = 'dbem_multiple_bookings_feedback_no_bookings';
            $options[] = 'dbem_multiple_bookings_feedback_empty_cart';
            $options[] = 'dbem_multiple_bookings_submit_button';
            //MB Emails
            $options[] = 'dbem_multiple_bookings_contact_email_subject';
            $options[] = 'dbem_multiple_bookings_contact_email_body';
            $options[] = 'dbem_multiple_bookings_contact_email_cancelled_subject';
            $options[] = 'dbem_multiple_bookings_contact_email_cancelled_body';
            $options[] = 'dbem_multiple_bookings_email_confirmed_subject';
            $options[] = 'dbem_multiple_bookings_email_confirmed_body';
            $options[] = 'dbem_multiple_bookings_email_pending_subject';
            $options[] = 'dbem_multiple_bookings_email_pending_body';
            $options[] = 'dbem_multiple_bookings_email_rejected_subject';
            $options[] = 'dbem_multiple_bookings_email_rejected_body';
            $options[] = 'dbem_multiple_bookings_email_cancelled_subject';
            $options[] = 'dbem_multiple_bookings_email_cancelled_body';
        }
		//payment gateway options (pro, move out asap)
		$options[] = 'dbem_gateway_label';
        //gateway translateable options
		foreach ( EM_Gateways::gateways_list() as $gateway => $gateway_name ){
		    $EM_Gateway = EM_Gateways::get_gateway($gateway);
		    $options[] = 'em_'.$gateway.'_option_name';
		    $options[] = 'em_'.$gateway.'_booking_feedback';
		    $options[] = 'em_'.$gateway.'_booking_feedback_free';
		    $options[] = 'em_'.$gateway.'_booking_feedback_completed';
		    $options[] = 'em_'.$gateway.'_form';
		    if( $EM_Gateway->button_enabled ){
		        $options[] = 'em_'.$gateway.'_button';
		    }
		}
        return $options;
    }
}
add_action('em_ml_pre_init', 'EMP_ML::init');