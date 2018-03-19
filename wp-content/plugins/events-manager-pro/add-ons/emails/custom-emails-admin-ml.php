<?php
class EM_Custom_Emails_Admin_ML{
    public static function init(){
        add_action('after_gatweay_custom_emails','EM_Custom_Emails_Admin_ML::after_gatweay_custom_emails',10,5);
        add_action('em_custom_emails_admin_gateway_update','EM_Custom_Emails_Admin_ML::em_custom_emails_admin_gateway_update',10,4);
    }
    
    public static function after_gatweay_custom_emails( $EM_Gateway, $original_email_values, $original_default_emails, $default_email_values, $original_admin_emails ){
	    //we can go through each language and display another language set of email forms
	    $gateway = $EM_Gateway->gateway;
        //get default email values for languages
	    $gateway_email_values_ml = maybe_unserialize($EM_Gateway->get_option('emails_ml'));
	    foreach( EM_ML::$langs as $lang => $language ){
	        if( $lang != EM_ML::$wplang ){
	            $default_emails = $original_default_emails;
    		    $gateway_email_values = !empty($gateway_email_values_ml[$lang]) ? $gateway_email_values_ml[$lang] : array();
    		    $email_values = EM_Custom_Emails_Admin::merge_gateway_default_values($gateway, $default_email_values, $gateway_email_values);
	            //get admin emails for this language
        		$admin_emails = false;
        		if( get_option('dbem_custom_emails_gateways_admins') ){
    			    $possible_email_values = maybe_unserialize($EM_Gateway->get_option('emails_admins_ml'));
    			    $admin_emails = empty($possible_email_values[$lang]) ? array():$possible_email_values[$lang];
        		}
        		//mod some texts so it's language-specific
		        $default_emails[$gateway]['title'] = "[$language] ". $original_default_emails[$gateway]['title'];
		        $default_emails[$gateway]['text'] = '<p>'. sprintf(__('These settings are applied when making a booking on %s languge pages. The email template settings here will override those for the default language.','em-pro'),$language).'</p>';
		        //modify MB titles too
		        if( get_option('dbem_multiple_bookings') ){
        			//duplicate default emails array and give them different keys
        			$default_emails = EM_Custom_Emails_Admin::add_gateway_mb_default_emails($default_emails, $EM_Gateway);
		            $default_emails[$gateway.'-mb']['title'] = "[$language] ". $original_default_emails[$gateway.'-mb']['title'];
        			//get default mb values and merge them into email values
        			$mb_default_email_values = EM_Custom_Emails_Admin::get_gateway_mb_default_values($EM_Gateway);
            		//get custom values if applicable
            		$mb_email_values = EM_Custom_Emails_Admin::merge_gateway_default_values($gateway, $mb_default_email_values, $gateway_email_values);
        			//merge them all together
            		$email_values = array_merge($email_values, $mb_email_values); 
		        }
		        //output an editor form for this language
		        EM_Custom_Emails_Admin::emails_editor($email_values, $default_emails, $admin_emails, 'em_custom_email_'.$lang);
	        }
	    }
    }
    
    public static function em_custom_emails_admin_gateway_update( $EM_Gateway, $default_emails, $custom_booking_emails, $custom_admin_emails ){
	    //update multilingual templates (not original language), save them into one serialized array
	    $custom_booking_emails_ml = array();
	    foreach( EM_ML::$langs as $lang => $language ){
	        if( EM_ML::$wplang != $lang ){
        		if( get_option('dbem_multiple_bookings') ){
        		    $default_emails = EM_Custom_Emails_Admin::add_gateway_mb_default_emails($default_emails, $EM_Gateway);
        		}
	            $custom_booking_emails_ml[$lang] = EM_Custom_Emails_Admin::editor_get_post( $default_emails, 'em_custom_email_'.$lang);
	        }
	    }
    	$EM_Gateway->update_option('emails_ml', serialize($custom_booking_emails_ml));
    	//update admin emails
		if( get_option('dbem_custom_emails_gateways_admins') ){
	        foreach( EM_ML::$langs as $lang => $language ){
	            if( $lang == EM_ML::$wplang ) continue;
		        $custom_admin_emails = EM_Custom_Emails_Admin::update_gateway_admin_emails($EM_Gateway, $default_emails, 'em_custom_email_'.$lang.'_admins');
		        if( $custom_admin_emails === false ){
        			global $EM_Notices;
        			$EM_Notices->add_error("[$language] ".__('An invalid admin email was supplied for your custom emails and was not saved in your settings.','em-pro'),true);
        		}else{
        			$custom_admin_emails_ml[$lang] = $custom_admin_emails;
        		}
	        }
	        if( !empty($custom_admin_emails_ml) ){
	            $EM_Gateway->update_option('emails_admins_ml', serialize($custom_admin_emails_ml));
	        }
		}
    }
}
EM_Custom_Emails_Admin_ML::init();
