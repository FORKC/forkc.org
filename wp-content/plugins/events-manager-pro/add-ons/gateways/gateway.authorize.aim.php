<?php

class EM_Gateway_Authorize_AIM extends EM_Gateway {
	//change these properties below if creating a new gateway, not advised to change this for Authorize_AIM
	var $gateway = 'authorize_aim';
	var $title = 'Authorize.net AIM';
	var $status = 4;
	var $status_txt = 'Processing (Authorize.net AIM)';
	var $button_enabled = false; //we can's use a button here
	var $count_pending_spaces = true;
	var $supports_multiple_bookings = true;

	var $registered_timer = 0;
	/**
	 * Sets up gateaway and adds relevant actions/filters 
	 */
	function __construct() {
		parent::__construct();
		if($this->is_active()) {
			//Force SSL for booking submissions, since we have card info
			if(get_option('em_'.$this->gateway.'_mode') == 'live'){ //no need if in sandbox mode
				add_filter('em_wp_localize_script',array(&$this,'em_wp_localize_script'),10,1); //modify booking script, force SSL for all
				add_filter('em_booking_form_action_url',array(&$this,'force_ssl'),10,1); //modify booking script, force SSL for all
			}
			//Gateway-Specific
			add_action('em_handle_payment_return_' . $this->gateway, array(&$this, 'handle_payment_return')); //handle Silent returns
		}
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking Interception - functions that modify booking object behaviour
	 * --------------------------------------------------
	 */
	/**
	 * This function intercepts the previous booking form url from the javascript localized array of EM variables and forces it to be an HTTPS url. 
	 * @param array $localized_array
	 * @return array
	 */
	function em_wp_localize_script($localized_array){
		$localized_array['bookingajaxurl'] = $this->force_ssl($localized_array['bookingajaxurl']);
		return $localized_array;
	}
	
	/**
	 * Turns any url into an HTTPS url.
	 * @param string $url
	 * @return string
	 */
	function force_ssl($url){
		return str_replace('http://','https://', $url);
	}
	
	/**
	 * Triggered by the em_booking_add_yourgateway action, modifies the booking status if the event isn't free and also adds a filter to modify user feedback returned.
	 * @param EM_Event $EM_Event
	 * @param EM_Booking $EM_Booking
	 * @param boolean $post_validation
	 */
	function booking_add($EM_Event,$EM_Booking, $post_validation = false){
		global $wpdb, $wp_rewrite, $EM_Notices;
		$this->registered_timer = current_time('timestamp', 1);
		parent::booking_add($EM_Event, $EM_Booking, $post_validation);
		if( $post_validation && empty($EM_Booking->booking_id) ){
			if( get_option('dbem_multiple_bookings') && get_class($EM_Booking) == 'EM_Multiple_Booking' ){
		    	add_filter('em_multiple_booking_save', array(&$this, 'em_booking_save'),2,2);			    
			}else{
		    	add_filter('em_booking_save', array(&$this, 'em_booking_save'),2,2);
			}		    	
		}
	}
	
	/**
	 * Added to filters once a booking is added. Once booking is saved, we capture payment, and approve the booking (saving a second time). If payment isn't approved, just delete the booking and return false for save. 
	 * @param bool $result
	 * @param EM_Booking $EM_Booking
	 */
	function em_booking_save( $result, $EM_Booking ){
		global $wpdb, $wp_rewrite, $EM_Notices;
		//make sure booking save was successful before we try anything
		if( $result ){
			if( $EM_Booking->get_price() > 0 ){
				//handle results
				$capture = $this->authorize_and_capture($EM_Booking);
				if($capture){
					//Set booking status, but no emails sent
					if( !get_option('em_'.$this->gateway.'_manual_approval', false) || !get_option('dbem_bookings_approval') ){
						$EM_Booking->set_status(1, false); //Approve
					}else{
						$EM_Booking->set_status(0, false); //Set back to normal "pending"
					}
				}else{
					//not good.... error inserted into booking in capture function. Delete this booking from db
					if( !is_user_logged_in() && get_option('dbem_bookings_anonymous') && !$EM_Booking->is_no_user() ){
						//delete the user we just created, only if created after em_booking_add filter is called (which is when a new user for this booking would be created)
						$EM_Person = $EM_Booking->get_person();
						if( strtotime($EM_Person->data->user_registered) >= $this->registered_timer ){
							if( is_multisite() ){
								include_once(ABSPATH.'/wp-admin/includes/ms.php');
								wpmu_delete_user($EM_Person->ID);
							}else{
								include_once(ABSPATH.'/wp-admin/includes/user.php');
								wp_delete_user($EM_Person->ID);
							}
							//remove email confirmation
							global $EM_Notices;
							$EM_Notices->notices['confirms'] = array();
						}
					}
					$EM_Booking->manage_override = true;
					$EM_Booking->delete();
					$EM_Booking->manage_override = false;
					return false;
				}
			}
		}
		return $result;
	}
	
	/**
	 * Intercepts return data after a booking has been made and adds authorize_aim vars, modifies feedback message.
	 * @param array $return
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	function booking_form_feedback( $return, $EM_Booking = false ){
		//Double check $EM_Booking is an EM_Booking object and that we have a booking awaiting payment.
		if( !empty($return['result']) ){
			if( !empty($EM_Booking->booking_meta['gateway']) && $EM_Booking->booking_meta['gateway'] == $this->gateway && $EM_Booking->get_price() > 0 ){
				$return['message'] = get_option('em_authorize_aim_booking_feedback');
			}else{
				//returning a free message
				$return['message'] = get_option('em_authorize_aim_booking_feedback_free');
			}
		}elseif( !empty($EM_Booking->booking_meta['gateway']) && $EM_Booking->booking_meta['gateway'] == $this->gateway && $EM_Booking->get_price() > 0 ){
			//void this last authroization
			$this->void($EM_Booking);
		}
		return $return;
	}
	
	/**
	 * Handles the silent post URL
	 */
	function handle_payment_return(){
		global $wpdb;
		//We do it post-style here, since it's an AIM/SIM mix.		
		/* Uncomment the below to debug locally. Visit the response page with this uncommented to trigger a response. DONT FORGET TO COMMENT BACK!		
		$_POST = array ( 'x_response_code' => '1', 'x_response_reason_code' => '1', 'x_response_reason_text' => 'This transaction has been approved.', 'x_avs_code' => 'P', 'x_auth_code' => '', 'x_trans_id' => '2168914272', 'x_method' => 'CC', 'x_card_type' => 'American Express', 'x_account_number' => 'XXXX0002', 'x_first_name' => '', 'x_last_name' => '', 'x_company' => '', 'x_address' => '', 'x_city' => '', 'x_state' => '', 'x_zip' => '', 'x_country' => '', 'x_phone' => '', 'x_fax' => '', 'x_email' => 'msykes@gmail.com', 'x_invoice_num' => '', 'x_description' => 'Kenny Wayne Shepherd', 'x_type' => 'credit', 'x_cust_id' => '', 'x_ship_to_first_name' => '', 'x_ship_to_last_name' => '', 'x_ship_to_company' => '', 'x_ship_to_address' => '', 'x_ship_to_city' => '', 'x_ship_to_state' => '', 'x_ship_to_zip' => '', 'x_ship_to_country' => '', 'x_amount' => '150.00', 'x_tax' => '0.00', 'x_duty' => '0.00', 'x_freight' => '0.00', 'x_tax_exempt' => 'FALSE', 'x_po_num' => '', 'x_MD5_Hash' => '502A0D462D3A8C3677277111E59EDFC3', 'x_cvv2_resp_code' => '', 'x_cavv_response' => '', 'x_test_request' => 'false', );
		$_POST['x_trans_id'] = '2168915121'; //enter the txn id you want to mess with
		$_POST['x_amount'] = '0.00'; //positive number if credit, 0.00 if void
		$_POST['x_type'] = 'void'; //credit or void
		$_POST['x_invoice_num'] = 10; //booking_id needed if this is a credit
		$_POST['x_MD5_Hash'] = strtoupper(md5(get_option('em_'.$this->gateway.'_md5_hash').get_option('em_'.$this->gateway.'_user_login').$_POST['x_trans_id'].$_POST['x_amount'])); //the hash a.net would send you
		*/
		//Make sure this is Authorize.net
        $amount = (empty($_POST['x_amount']) || (int) $_POST['x_amount'] == 0 ) ? "0.00":$_POST['x_amount'];
        $md5_1 = strtoupper(md5(get_option('em_'.$this->gateway.'_md5_hash').get_option('em_'.$this->gateway.'_user_login').$_POST['x_trans_id'].$amount));
        $md5_2 = strtoupper(md5(get_option('em_'.$this->gateway.'_md5_hash').get_option('em_'.$this->gateway.'_api_user').$_POST['x_trans_id'].$amount));
        $is_authorizenet = $md5_1 == $_POST['x_MD5_Hash'] || $md5_2 == $_POST['x_MD5_Hash'];
        if( !empty($_POST['x_response_code']) && $_POST['x_response_code'] == 1  && $is_authorizenet ){
        	if( $_POST['x_type'] == 'credit' ){
        		//Since credit has another txn id we can find a booking by invoice number / booking id and cancel the booking, record new txn.
        		$EM_Booking = em_get_booking($_POST['x_invoice_num']);
	        	if( !empty($EM_Booking->booking_id) ){
	        		$EM_Booking->cancel();
	        		$amount = $amount * -1;
	        		$this->record_transaction($EM_Booking, $amount, 'USD', current_time('mysql'), $_POST['x_trans_id'], __('Refunded','em-pro'), '');
	        		echo "Transaction Processed";
	        		EM_Pro::log( 'Silent Post - '. $_POST['x_type']." - successfully processed for {$_POST['x_amount']} (TXN ID {$_POST['x_trans_id']}) - Booking ID: {$_POST['x_invoice_num']}", $this->gateway);
	        	}else{
	        		echo "Transaction not found"; //meaningful output
	        		EM_Pro::log( array('Silent Post - Transaction Not Found', '$_POST'=> $_POST), $this->gateway );
	        	}
        	}elseif( $_POST['x_type'] == 'void' ){
	        	//Find the transaction and booking, void the transaction, cancel the booking.
	        	$txn = $wpdb->get_row( $wpdb->prepare( "SELECT transaction_id, transaction_gateway_id, transaction_total_amount, booking_id FROM ".EM_TRANSACTIONS_TABLE." WHERE transaction_gateway_id = %s AND transaction_gateway = %s ORDER BY transaction_total_amount DESC LIMIT 1", $_POST['x_trans_id'], $this->gateway ), ARRAY_A );
	        	if( is_array($txn) && $txn['transaction_gateway_id'] == $_POST['x_trans_id'] && !empty($txn['booking_id']) ){
	        		$EM_Booking = em_get_booking($txn['booking_id']);
	        		$EM_Booking->cancel();
	        		$wpdb->update(EM_TRANSACTIONS_TABLE, array('transaction_status'=>__('Voided','em-pro'),'transaction_timestamp'=>current_time('mysql')), array('transaction_id'=>$txn['transaction_id']));
	        		echo "Transaction Processed";
	        		EM_Pro::log( 'Silent Post - '. $_POST['x_type']." - successfully processed for {$_POST['x_amount']} (TXN ID {$_POST['x_trans_id']}) - Booking ID: {$txn['booking_id']}", $this->gateway);
	        	}else{
	        		echo "Transaction not found"; //meaningful output
	        		EM_Pro::log( array('Silent Post - Transaction Not Found', '$_POST'=> $_POST), $this->gateway );
	        	}
        	}else{
	        	echo "Unprocessed transaction - ".$this->title;
	        	EM_Pro::log( array('Silent Post - Unprocessed transaction', '$_POST'=> $_POST), $this->gateway );
	        }
	    }elseif( !$is_authorizenet ){
	    	echo "MD5 Hash failed.";
	        EM_Pro::log( array('Silent Post - MD5 Hash failed', '$_POST'=> $_POST), $this->gateway );
	    }else{
        	echo "Response not recognized.";
	        EM_Pro::log( array('Silent Post - Response not recognized', '$_POST'=> $_POST), $this->gateway );
	    }
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking UI - modifications to booking pages and tables containing authorize_aim bookings
	 * --------------------------------------------------
	 */

	/**
	 * Outputs custom content and credit card information.
	 */
	function booking_form(){
		echo get_option('em_'.$this->gateway.'_form');
		?>
        <p class="em-bookings-form-gateway-cardno">
          <label><?php  _e('Credit Card Number','em-pro'); ?></label>
          <input type="text" size="15" name="x_card_num" value="" class="input" />
        </p>
        <p class="em-bookings-form-gateway-expiry">
          <label><?php  _e('Expiry Date','em-pro'); ?></label>
          <select name="x_exp_date_month" >
          	<?php 
          		for($i = 1; $i <= 12; $i++){
          			$m = $i > 9 ? $i:"0$i";
          			echo "<option>$m</option>";
          		} 
          	?>
          </select> / 
          <select name="x_exp_date_year" >
          	<?php 
          		$year = date('Y',current_time('timestamp'));
          		for($i = $year; $i <= $year+10; $i++){
		 	      	echo "<option>$i</option>";
          		}
          	?>
          </select>
        </p>
        <p class="em-bookings-form-ccv">
          <label><?php  _e('CCV','em-pro'); ?></label>
          <input type="text" size="4" name="x_card_code" value="" class="input" />
        </p>
		<?php
	}
	
	/*
	 * --------------------------------------------------
	 * Authorize.net AIM Functions - functions specific to authorize_aim payments
	 * --------------------------------------------------
	 */
	
	/**
	 * Get the AuthorizeNetAIM object and set up basic parameters
	 * @return AuthorizeNetAIM
	 */
	function get_api(){
		if( !class_exists('AuthorizeNetAIM') ){
			require_once('anet_php_sdk/AuthorizeNet.php'); 
		}       
        //Basic Credentials
		$sale = new AuthorizeNetAIM(get_option('em_'.$this->gateway.'_api_user'), get_option('em_'.$this->gateway.'_api_key'));
		if(get_option('em_'.$this->gateway.'_mode') == 'live'){
			$sale->setSandbox(false);
		}else{
			$sale->setSandbox(true);
		}
        return $sale;
	}
	
	/**
	 * Retreive the authorize_aim vars needed to send to the gateway to proceed with payment
	 * @param EM_Booking $EM_Booking
	 */
	function authorize_and_capture($EM_Booking){
		global $EM_Notices;
		$sale = $this->get_api();

        //Get transaction ID for authorization/capture
        $sale->amount = $amount = $EM_Booking->get_price(false, false, true);
        $sale->exp_date = $_REQUEST['x_exp_date_month'].'/'.$_REQUEST['x_exp_date_year'];
        $sale->card_num = $_REQUEST['x_card_num'];
        $sale->card_code = $_REQUEST['x_card_code'];

        //Email Info
        $sale->email_customer = get_option('em_'.$this->gateway.'_email_customer',0) ? '1':'0'; //for later
        $sale->header_email_receipt = get_option('em_'.$this->gateway.'_header_email_receipt');
        $sale->footer_email_receipt = get_option('em_'.$this->gateway.'_footer_email_receipt');

        //Order Info
		$sale->invoice_num = $EM_Booking->booking_id;
        $sale->description = preg_replace('/[^a-zA-Z0-9\s]/i', "", $EM_Booking->get_event()->event_name); //clean event name
        
        //Customer Info
        $sale->email = $EM_Booking->get_person()->user_email;
        $sale->customer_ip = $_SERVER['REMOTE_ADDR'];
        $sale->cust_id = $EM_Booking->is_no_user() ? 'booking-'.$EM_Booking->booking_id:'user-'.$EM_Booking->get_person()->ID;
        //Address Info
        $names = explode(' ', $EM_Booking->get_person()->get_name());
        if( !empty($names[0]) ) $sale->first_name = array_shift($names);
        if( implode(' ',$names) != '' ) $sale->last_name = implode(' ',$names);
        //address slightly special address field
        $address = '';
        if( EM_Gateways::get_customer_field('address', $EM_Booking) != '' ) $address = EM_Gateways::get_customer_field('address', $EM_Booking);
        if( EM_Gateways::get_customer_field('address_2', $EM_Booking) != '' ) $address .= ', ' .EM_Gateways::get_customer_field('address_2', $EM_Booking);
        if( !empty($address) ) $sale->address = substr($address, 0, 60); //cut off at 60 characters
        if( EM_Gateways::get_customer_field('city', $EM_Booking) != '' ) $sale->city = EM_Gateways::get_customer_field('city', $EM_Booking);
        if( EM_Gateways::get_customer_field('state', $EM_Booking) != '' ) $sale->state = EM_Gateways::get_customer_field('state', $EM_Booking);
        if( EM_Gateways::get_customer_field('zip', $EM_Booking) != '' ) $sale->zip = EM_Gateways::get_customer_field('zip', $EM_Booking);
        if( EM_Gateways::get_customer_field('country', $EM_Booking) != '' ){
			$countries = em_get_countries();
			$sale->country = $countries[EM_Gateways::get_customer_field('country', $EM_Booking)];
		}
        if( EM_Gateways::get_customer_field('phone', $EM_Booking) != '' ) $sale->phone = EM_Gateways::get_customer_field('phone', $EM_Booking);
        if( EM_Gateways::get_customer_field('fax', $EM_Booking) != '' ) $sale->fax = EM_Gateways::get_customer_field('fax', $EM_Booking);
        if( EM_Gateways::get_customer_field('company', $EM_Booking) != '' ) $sale->company = EM_Gateways::get_customer_field('company', $EM_Booking);
        
        //Itemized Billing
        $tax_enabled = (get_option('dbem_bookings_tax') > 0) ? 'Y':'N';
		foreach( $EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking ){
			$price = round($EM_Ticket_Booking->get_price() / $EM_Ticket_Booking->get_spaces(), 2);
			if( $price > 0 ){
				$ticket_name = substr($EM_Ticket_Booking->get_ticket()->ticket_name, 0, 31);
        		$sale->addLineItem($EM_Ticket_Booking->get_ticket()->ticket_id, $ticket_name, $EM_Ticket_Booking->get_ticket()->ticket_description, $EM_Ticket_Booking->get_spaces(), $price, $tax_enabled);
			}
		}
		if( $tax_enabled == 'Y' ){
			$sale->tax = number_format($EM_Booking->get_price_taxes(), 2);
		}
		//Add discounts to itemized billing, one line each for post and pre taxes
		$discount = $EM_Booking->get_price_adjustments_amount('discounts', 'pre');
		if( $discount > 0 ){
			$sale->addLineItem(0, __('Discount','em-pro'), '', 1, $discount, 'Y');
		}
		$discount = $EM_Booking->get_price_adjustments_amount('discounts', 'post');
		if( $discount > 0 ){
			$sale->addLineItem(0, __('Discount','em-pro'), '', 1, $discount, 'N');
		}

		//Add surcharges to itemized billing, one line each for post and pre taxes
		$surcharge = $EM_Booking->get_price_adjustments_amount('surcharges', 'pre');
		if( $surcharge > 0 ){
			$sale->addLineItem(0, __('Surcharge','em-pro'), '', 1, $surcharge, 'Y');
		}
		$surcharge = $EM_Booking->get_price_adjustments_amount('surcharges', 'post');
		if( $surcharge > 0 ){
			$sale->addLineItem(0, __('Surcharge','em-pro'), '', 1, $surcharge, 'N');
		}
		
        //Get Payment
        /* DEPRECATED - Remenant of a typoe, will one day get deleted, use filter below */ $sale = apply_filters('em_gateawy_authorize_aim_sale_var', $sale, $EM_Booking, $this);
        $sale = apply_filters('em_gateway_authorize_aim_sale_var', $sale, $EM_Booking, $this);
        $response = $sale->authorizeAndCapture();
        
        //Handle result
        $result = $response->approved == true;
        if( $result ){
			$EM_Booking->booking_meta[$this->gateway] = array('txn_id'=>$response->transaction_id, 'amount' => $amount);
	        $this->record_transaction($EM_Booking, $amount, get_option('dbem_bookings_currency', 'USD'), date('Y-m-d H:i:s', current_time('timestamp')), $response->transaction_id, 'Completed', '');
			EM_Pro::log( $_POST['payment_status']." successfully received for {$amount} (TXN ID {$response->transaction_id}) (Booking ID {$EM_Booking->booking_id})", $this->gateway);
        }else{
	        $EM_Booking->add_error($response->response_reason_text);
	        EM_Pro::log( array('Capture transaction failed - '.$response->response_reason_text, '$response'=> $response), $this->gateway );
        }
        //Return transaction_id or false
		return apply_filters('em_gateway_authorize_aim_authorize', $result, $EM_Booking, $this);
	}
	
	function void($EM_Booking){
		if( !empty($EM_Booking->booking_meta[$this->gateway]) ){
	        $capture = $this->get_api();
	        $capture->amount = $EM_Booking->booking_meta[$this->gateway]['amount'];
	        $capture->void();
		}
	}
	
	/*
	 * --------------------------------------------------
	 * Gateway Settings Functions
	 * --------------------------------------------------
	 */
	
	/**
	 * Outputs custom PayPal setting fields in the settings page 
	 */
	function mysettings() {
		global $EM_options;
		?>
		<table class="form-table">
		<tbody>
		  <?php em_options_input_text( esc_html__('Success Message', 'em-pro'), 'em_'. $this->gateway . '_booking_feedback', esc_html__('The message that is shown to a user when a booking is successful and payment has been taken.','em-pro') ); ?>
		  <?php em_options_input_text( esc_html__('Success Free Message', 'em-pro'), 'em_'. $this->gateway . '_booking_feedback_free', esc_html__('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be charged.','em-pro') ); ?>
		</tbody>
		</table>
		<h3><?php echo sprintf(esc_html__emp('%s Options','events-manager'),'Authorize.net')?></h3>
		<p style="font-style:italic;"><?php echo sprintf(__('Please visit the <a href="%s">documentation</a> for further instructions on setting up Authorize.net with Events Manager.','em-pro'), 'http://wp-events-plugin.com/documentation/event-bookings-with-authorize-net-aim/'); ?></p>
		<table class="form-table">
		<tbody>
			 <tr valign="top">
				  <th scope="row"><?php _e('Mode', 'em-pro'); ?></th>
				  <td>
					  <select name="em_<?php echo $this->gateway ?>_mode" id="em_<?php echo $this->gateway ?>_mode">
					  	<?php $selected = get_option('em_'.$this->gateway.'_mode'); ?>
						<option value="sandbox" <?php echo ($selected == 'sandbox') ? 'selected="selected"':''; ?>><?php _e('Sandbox','em-pro'); ?></option>
						<option value="live" <?php echo ($selected == 'live') ? 'selected="selected"':''; ?>><?php _e('Live','em-pro'); ?></option>
					  </select>
					  <br />
					  <em><?php echo sprintf(__('Please visit <a href="%s">Authorize.net</a> to set up a sandbox account if you would like to test this gateway out. Alternatively, you can use your real account in test mode, which you can set on your Authorize.net control panel.','em-pro'),'https://developer.authorize.net/integration/fifteenminutes/');?></em>
					  <?php 
					  /* @todo move SSL check outside of A.net and make it a generic gatweay-wide check. */
					  global $wp_scripts;
					  $ajax_url = str_replace('http://','https://', admin_url('admin-ajax.php'));
					  $verify = @wp_remote_get($ajax_url);
					  if( is_wp_error($verify) ){ /* @var $verify WP_Error */
					  	foreach( $verify->get_error_messages() as $error ){
					  		if( preg_match('/SSL/', $error) ){
					  			echo '<div class="em-gateway-ssl-warning" style="color:red">';
								echo sprintf(esc_html__('A valid SSL certificate is required for live payments using this gateway. We are not able to connect to this URL: %s.','em-pro'), '<a href="'.$ajax_url.'"><code>'.$ajax_url.'</code></a>');
								echo '</div>';
					  		}
					  	}
					  }
					  ?>
					  <script type="text/javascript">
						jQuery(document).ready( function($){
							$(document).on('change','#em_<?php echo $this->gateway ?>_mode', function(){
								if( $(this).find('option:selected').val() == 'live' ){
									$('.em-gateway-ssl-warning').show();
								}else{
									$('.em-gateway-ssl-warning').hide();
								}
							});
							$('#em_<?php echo $this->gateway ?>_mode').trigger('change');
						});
					  </script>
				  </td>
			</tr>
			<tr valign="top">
				  <th scope="row"><?php _e('API Login ID', 'em-pro') ?></th>
				  <td><input type="text" name="em_<?php echo $this->gateway ?>_api_user" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_api_user", "" )); ?>" /></td>
			</tr>
			<tr valign="top">
			 	<th scope="row"><?php _e('Transaction key', 'em-pro') ?></th>
			    <td><input type="text" name="em_<?php echo $this->gateway ?>_api_key" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_api_key", "" )); ?>" /></td>
			</tr>
			<tr><td colspan="2">
				<p><strong><?php echo __( 'Payment Notifications','em-pro'); ?></strong></p>
				<p><?php _e('If you would like to receive notifications from Authorize.net and handle refunds or voided transactions automatically, you need to enable Silent Post URL and provide an MD5 Hash in your merchant interface.','em-pro'); ?></p>
				<p><?php echo sprintf(__('Your return url is %s.','em-pro'),'<code>'.$this->get_payment_return_url().'</code>'); ?> <?php _e('You can use this as your Silent Post URL.', 'em-pro'); ?></p>
			</td></tr>
			<tr>
				<th scope="row"><?php _e('Security: MD5 Hash', 'em-pro') ?></th>
				<td>
					<input type="text" name="em_<?php echo $this->gateway ?>_md5_hash" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_md5_hash" )); ?>" /><br />
					<em><?php _e('This string of text needs to match that in your merchant interface settings.','em-pro')?></em>
				</td>
			</tr>
			<tr valign="top">
				  <th scope="row"><?php _e('User Login ID', 'em-pro') ?></th>
				  <td>
				  	<input type="text" name="em_<?php echo $this->gateway ?>_user_login" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_user_login", "" )); ?>" /><br />
					<em><?php _e('This is the username you use to log into your merchant interface.','em-pro')?></em>
				  </td>
			</tr>
			<tr><td colspan="2"><strong><?php echo sprintf(esc_html__emp( '%s Options', 'events-manager'),esc_html__emp('Advanced','events-manager')); ?></strong></td></tr>
			<tr>
				<th scope="row"><?php _e('Email Customer (on success)', 'em-pro') ?></th>
				<td>
					<select name="em_<?php echo $this->gateway ?>_email_customer">
					  	<?php $selected = get_option('em_'.$this->gateway.'_email_customer'); ?>
						<option value="1" <?php echo ($selected) ? 'selected="selected"':''; ?>><?php esc_html_e_emp('Yes','events-manager'); ?></option>
						<option value="0" <?php echo (!$selected) ? 'selected="selected"':''; ?>><?php esc_html_e_emp('No','events-manager'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Customer Receipt Email Header', 'em-pro') ?></th>
				<td><input type="text" name="em_<?php echo $this->gateway ?>_header_email_receipt" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_header_email_receipt", __("Thanks for your payment!", "em-pro"))); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Customer Receipt Email Footer', 'em-pro') ?></th>
				<td><input type="text" name="em_<?php echo $this->gateway ?>_footer_email_receipt" value="<?php esc_attr_e(get_option( 'em_'. $this->gateway . "_footer_email_receipt", "" )); ?>" /></td>
			</tr>
		  <tr valign="top">
			  <th scope="row"><?php _e('Manually approve completed transactions?', 'em-pro') ?></th>
			  <td>
			  	<input type="checkbox" name="em_<?php echo $this->gateway ?>_manual_approval" value="1" <?php echo (get_option('em_'. $this->gateway . "_manual_approval" )) ? 'checked="checked"':''; ?> /><br />
			  	<em><?php _e('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-pro'); ?></em><br />
			  	<em><?php echo sprintf(__('Approvals must also be required for all bookings in your <a href="%s">settings</a> for this to work properly.','em-pro'),EM_ADMIN_URL.'&amp;page=events-manager-options'); ?></em>
			  </td>
		  </tr>
		</tbody>
		</table>
		<?php
	}

	/* 
	 * Run when saving settings, saves the settings available in EM_Gateway_Authorize_AIM::mysettings()
	 */
	function update() {
	    $gateway_options = $options_wpkses = array();
		$gateway_options[] = 'em_'.$this->gateway . "_mode";
		$gateway_options[] = 'em_'.$this->gateway . "_api_user";
		$gateway_options[] = 'em_'.$this->gateway . "_api_key";
		$gateway_options[] = 'em_'.$this->gateway . "_user_login";
		$gateway_options[] = 'em_'.$this->gateway . "_email_customer";
		$gateway_options[] = 'em_'.$this->gateway . "_md5_hash";
		$gateway_options[] = 'em_'.$this->gateway . "_manual_approval";
		//add wp_kses filters for relevant options and merge in
		$options_wpkses[] = 'em_'. $this->gateway . '_booking_feedback';
		$options_wpkses[] = 'em_'. $this->gateway . '_booking_feedback_free';
		$options_wpkses[] = 'em_'. $this->gateway . "_header_email_receipt";
		$options_wpkses[] = 'em_'. $this->gateway . "_footer_email_receipt";
		foreach( $options_wpkses as $option_wpkses ) add_filter('gateway_update_'.$option_wpkses,'wp_kses_post');
		$gateway_options = array_merge($gateway_options, $options_wpkses);
		//pass options to parent which handles saving
		return parent::update($gateway_options);
	}
}
EM_Gateways::register_gateway('authorize_aim', 'EM_Gateway_Authorize_AIM');
?>