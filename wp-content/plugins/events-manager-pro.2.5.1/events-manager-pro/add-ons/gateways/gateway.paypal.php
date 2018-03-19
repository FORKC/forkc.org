<?php

class EM_Gateway_Paypal extends EM_Gateway {
	//change these properties below if creating a new gateway, not advised to change this for PayPal
	var $gateway = 'paypal';
	var $title = 'PayPal (Payments Standard)';
	var $status = 4;
	var $status_txt = 'Awaiting PayPal Payment';
	var $button_enabled = true;
	var $payment_return = true;
	var $count_pending_spaces = false;
	var $supports_multiple_bookings = true;

	/**
	 * Sets up gateaway and adds relevant actions/filters 
	 */
	function __construct() {
		//Booking Interception
	    if( $this->is_active() && absint(get_option('em_'.$this->gateway.'_booking_timeout')) > 0 ){
	        $this->count_pending_spaces = true;
	    }
		parent::__construct();
		$this->status_txt = __('Awaiting PayPal Payment','em-pro');
		if($this->is_active()) {
			add_action('em_gateway_js', array(&$this,'em_gateway_js'));
			//Gateway-Specific
			add_action('em_template_my_bookings_header',array(&$this,'say_thanks')); //say thanks on my_bookings page
			add_filter('em_bookings_table_booking_actions_4', array(&$this,'bookings_table_actions'),1,2);
			add_filter('em_my_bookings_booking_actions', array(&$this,'em_my_bookings_booking_actions'),1,2);
			//set up cron
			$timestamp = wp_next_scheduled('emp_paypal_cron');
			if( absint(get_option('em_'. $this->gateway . '_booking_timeout')) > 0 && !$timestamp ){
				$result = wp_schedule_event(time(),'em_minute','emp_paypal_cron');
			}elseif( !$timestamp ){
				wp_unschedule_event($timestamp, 'emp_paypal_cron');
			}
		}else{
			//unschedule the cron
			wp_clear_scheduled_hook('emp_paypal_cron');			
		}
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking Interception - functions that modify booking object behaviour
	 * --------------------------------------------------
	 */
	
	/**
	 * Intercepts return data after a booking has been made and adds paypal vars, modifies feedback message.
	 * @param array $return
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	function booking_form_feedback( $return, $EM_Booking = false ){
		//Double check $EM_Booking is an EM_Booking object and that we have a booking awaiting payment.
		if( is_object($EM_Booking) && $this->uses_gateway($EM_Booking) ){
			if( !empty($return['result']) && $EM_Booking->get_price() > 0 && $EM_Booking->booking_status == $this->status ){
				$return['message'] = get_option('em_'. $this->gateway . '_booking_feedback');	
				$paypal_url = $this->get_paypal_url();	
				$paypal_vars = $this->get_paypal_vars($EM_Booking);					
				$paypal_return = array('paypal_url'=>$paypal_url, 'paypal_vars'=>$paypal_vars);
				$return = array_merge($return, $paypal_return);
			}else{
				//returning a free message
				$return['message'] = get_option('em_'. $this->gateway . '_booking_feedback_free');
			}
		}
		return $return;
	}
	
	/**
	 * Called if AJAX isn't being used, i.e. a javascript script failed and forms are being reloaded instead.
	 * @param string $feedback
	 * @return string
	 */
	function booking_form_feedback_fallback( $feedback ){
		global $EM_Booking;
		if( is_object($EM_Booking) ){
			$feedback .= "<br />" . __('To finalize your booking, please click the following button to proceed to PayPal.','em-pro'). $this->em_my_bookings_booking_actions('',$EM_Booking);
		}
		return $feedback;
	}
	
	/**
	 * Triggered by the em_booking_add_yourgateway action, hooked in EM_Gateway. Overrides EM_Gateway to account for non-ajax bookings (i.e. broken JS on site).
	 * @param EM_Event $EM_Event
	 * @param EM_Booking $EM_Booking
	 * @param boolean $post_validation
	 */
	function booking_add($EM_Event, $EM_Booking, $post_validation = false){
		parent::booking_add($EM_Event, $EM_Booking, $post_validation);
		if( !defined('DOING_AJAX') ){ //we aren't doing ajax here, so we should provide a way to edit the $EM_Notices ojbect.
			add_action('option_dbem_booking_feedback', array(&$this, 'booking_form_feedback_fallback'));
		}
		//add an invoice ID prefix for PayPal - taken from wp_generate_uuid4() but copied here to allow WP <4.7 compat
		$EM_Booking->booking_meta['uuid'] = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000, mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	}
	
	/* 
	 * --------------------------------------------------
	 * Booking UI - modifications to booking pages and tables containing paypal bookings
	 * --------------------------------------------------
	 */
	
	/**
	 * Instead of a simple status string, a resume payment button is added to the status message so user can resume booking from their my-bookings page.
	 * @param string $message
	 * @param EM_Booking $EM_Booking
	 * @return string
	 */
	function em_my_bookings_booking_actions( $message, $EM_Booking){
	    global $wpdb;
		if($this->uses_gateway($EM_Booking) && $EM_Booking->booking_status == $this->status){
		    //first make sure there's no pending payments
		    $pending_payments = $wpdb->get_var('SELECT COUNT(*) FROM '.EM_TRANSACTIONS_TABLE. " WHERE booking_id='{$EM_Booking->booking_id}' AND transaction_gateway='{$this->gateway}' AND transaction_status='Pending'");
		    if( $pending_payments == 0 ){
				//user owes money!
				$paypal_vars = $this->get_paypal_vars($EM_Booking);
				$form = '<form action="'.$this->get_paypal_url().'" method="post">';
				foreach($paypal_vars as $key=>$value){
					$form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
				}
				$form .= '<input type="submit" value="'.__('Resume Payment','em-pro').'">';
				$form .= '</form>';
				$message .= $form;
		    }
		}
		return $message;		
	}

	/**
	 * Outputs extra custom content e.g. the PayPal logo by default. 
	 */
	function booking_form(){
		echo get_option('em_'.$this->gateway.'_form');
	}
	
	/**
	 * Outputs some JavaScript during the em_gateway_js action, which is run inside a script html tag, located in gateways/gateway.paypal.js
	 */
	function em_gateway_js(){
		include(dirname(__FILE__).'/gateway.paypal.js');		
	}
	
	/**
	 * Adds relevant actions to booking shown in the bookings table
	 * @param EM_Booking $EM_Booking
	 */
	function bookings_table_actions( $actions, $EM_Booking ){
		return array(
			'approve' => '<a class="em-bookings-approve em-bookings-approve-offline" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Approve','events-manager').'</a>',
			'delete' => '<span class="trash"><a class="em-bookings-delete" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_delete', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Delete','events-manager').'</a></span>',
			'edit' => '<a class="em-bookings-edit" href="'.em_add_get_params($EM_Booking->get_event()->get_bookings_url(), array('booking_id'=>$EM_Booking->booking_id, 'em_ajax'=>null, 'em_obj'=>null)).'">'.esc_html__emp('Edit/View','events-manager').'</a>',
		);
	}
	
	/*
	 * --------------------------------------------------
	 * PayPal Functions - functions specific to paypal payments
	 * --------------------------------------------------
	 */
	
	/**
	 * Retreive the paypal vars needed to send to the gatway to proceed with payment
	 * @param EM_Booking $EM_Booking
	 */
	function get_paypal_vars($EM_Booking){
		global $wp_rewrite, $EM_Notices;
		$notify_url = $this->get_payment_return_url();
		$paypal_vars = array(
			'business' => trim(get_option('em_'. $this->gateway . "_email" )),
			'cmd' => '_cart',
			'upload' => 1,
			'currency_code' => get_option('dbem_bookings_currency', 'USD'),
			'notify_url' =>$notify_url,
			'invoice' => $this->get_invoice_id($EM_Booking),
			'charset' => 'UTF-8',
		    'bn'=>'NetWebLogic_SP'
		);
		if( get_option('em_'. $this->gateway . "_lc" ) ){
		    $paypal_vars['lc'] = get_option('em_'. $this->gateway . "_lc" );
		}
		//address fields`and name/email fields to prefill on checkout page (if available)
		$paypal_vars['email'] = $EM_Booking->get_person()->user_email;
		$paypal_vars['first_name'] = $EM_Booking->get_person()->first_name;
		$paypal_vars['last_name'] = $EM_Booking->get_person()->last_name;
        if( EM_Gateways::get_customer_field('address', $EM_Booking) != '' ) $paypal_vars['address1'] = EM_Gateways::get_customer_field('address', $EM_Booking);
        if( EM_Gateways::get_customer_field('address_2', $EM_Booking) != '' ) $paypal_vars['address2'] = EM_Gateways::get_customer_field('address_2', $EM_Booking);
        if( EM_Gateways::get_customer_field('city', $EM_Booking) != '' ) $paypal_vars['city'] = EM_Gateways::get_customer_field('city', $EM_Booking);
        if( EM_Gateways::get_customer_field('state', $EM_Booking) != '' ) $paypal_vars['state'] = EM_Gateways::get_customer_field('state', $EM_Booking);
        if( EM_Gateways::get_customer_field('zip', $EM_Booking) != '' ) $paypal_vars['zip'] = EM_Gateways::get_customer_field('zip', $EM_Booking);
        if( EM_Gateways::get_customer_field('country', $EM_Booking) != '' ) $paypal_vars['country'] = EM_Gateways::get_customer_field('country', $EM_Booking);
        
		if( get_option('em_'. $this->gateway . "_return" ) != "" ){
			$paypal_vars['return'] = get_option('em_'. $this->gateway . "_return" );
		}
		if( get_option('em_'. $this->gateway . "_cancel_return" ) != "" ){
			$paypal_vars['cancel_return'] = get_option('em_'. $this->gateway . "_cancel_return" );
		}
		if( get_option('em_'. $this->gateway . "_format_logo" ) !== false ){
			$paypal_vars['cpp_logo_image'] = get_option('em_'. $this->gateway . "_format_logo" );
		}
		if( get_option('em_'. $this->gateway . "_border_color" ) !== false ){
			$paypal_vars['cpp_cart_border_color'] = get_option('em_'. $this->gateway . "_format_border" );
		}
		$count = 1;
		//calculate discounts and surcharges if there are any
		$discount = $EM_Booking->get_price_adjustments_amount('discounts', 'pre') + $EM_Booking->get_price_adjustments_amount('discounts', 'post');
		$surcharges = $EM_Booking->get_price_adjustments_amount('surcharges', 'pre') + $EM_Booking->get_price_adjustments_amount('surcharges', 'post');
		/*
		 * IMPORTANT - If there's any adjustments to the price, we need to include one single price.
		 * The reason for this is because PayPal simply can't handle including prices per tickets without tax and provide 100% accuracy, 
		 * and if not excluding tax from item prices, pre tax adjustments aren't possible as separate items in the paypal checkout cart.
		 * Providing one item will avoid any issues, with the trade-off of a less detailed shopping cart checkout.
		 */
		if( $discount > 0 || $surcharges > 0 ){
			$description = $EM_Booking->get_event()->event_name;
			if( $EM_Booking->get_spaces() > 1 ){
				$description = $EM_Booking->get_spaces() . ' x ' . $description;
			}
			$paypal_vars['item_name_1'] = substr($description, 0, 126);
			$paypal_vars['amount_1'] = $EM_Booking->get_price();
		}else{
			if( $EM_Booking->get_price_taxes() > 0 && !get_option('em_'. $this->gateway . "_inc_tax" ) ){
				$paypal_vars['tax_cart'] = round($EM_Booking->get_price_taxes(), 2);
			}
			foreach( $EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking ){ /* @var $EM_Ticket_Booking EM_Ticket_Booking */
			    //divide price by spaces for per-ticket price
			    //we divide this way rather than by $EM_Ticket because that can be changed by user in future, yet $EM_Ticket_Booking will change if booking itself is saved.
			    if( !get_option('em_'. $this->gateway . "_inc_tax" ) ){
			    	$price = $EM_Ticket_Booking->get_price() / $EM_Ticket_Booking->get_spaces();
			    }else{
			    	$price = $EM_Ticket_Booking->get_price_with_taxes() / $EM_Ticket_Booking->get_spaces();
			    }
				if( $price > 0 ){
					$ticket_name = wp_kses_data($EM_Ticket_Booking->get_ticket()->name);
					$paypal_vars['item_name_'.$count] = substr($ticket_name, 0, 126);
					$paypal_vars['quantity_'.$count] = $EM_Ticket_Booking->get_spaces();
					$paypal_vars['amount_'.$count] = round($price,2);
					$count++;
				}
			}
		}
		return apply_filters('em_gateway_paypal_get_paypal_vars', $paypal_vars, $EM_Booking, $this);
	}
	
	/**
	 * gets paypal gateway url (sandbox or live mode)
	 * @returns string 
	 */
	function get_paypal_url(){
		return ( get_option('em_'. $this->gateway . "_status" ) == 'test') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr':'https://www.paypal.com/cgi-bin/webscr';
	}
	
	function get_invoice_id( $EM_Booking ){
	    //added this for new updates in case there's no UIDs assigned for payments pending payment (e.g. the resume payment button for old bookings beofre upgrading EMP versions)
	    if( empty($EM_Booking->booking_meta['uuid']) ){
	        //add an invoice ID prefix for PayPal - taken from wp_generate_uuid4() but copied here to allow WP <4.7 compat
	        $EM_Booking->booking_meta['uuid'] = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000, mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	        $EM_Booking->save(false);
	    }
	    $invoice_id = 'EM-BOOKING-'.$EM_Booking->booking_meta['uuid'].'#'. $EM_Booking->booking_id;
	    return apply_filters('em_gateway_paypal_get_invoice_id', $invoice_id, $EM_Booking, $this);
	}
	
	function say_thanks(){
		if( !empty($_REQUEST['thanks']) ){
			echo "<div class='em-booking-message em-booking-message-success'>".get_option('em_'.$this->gateway.'_booking_feedback_completed').'</div>';
		}
	}

	/**
	 * Runs when PayPal sends IPNs to the return URL provided during bookings and EM setup. Bookings are updated and transactions are recorded accordingly. 
	 */
	function handle_payment_return() {
		// PayPal IPN handling code
		if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {
			
		    //Verify IPN request
			if (get_option( 'em_'. $this->gateway . "_status" ) == 'live') {
				$domain = 'https://www.paypal.com/cgi-bin/webscr';
			} else {
				$domain = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}

			$req = 'cmd=_notify-validate';
			foreach ($_POST as $k => $v) {
				$req .= '&' . $k . '=' . urlencode(stripslashes($v));
			}
			
			@set_time_limit(60);

			//add a CA certificate so that SSL requests always go through
			add_action('http_api_curl','EM_Gateway_Paypal::payment_return_local_ca_curl',10,1);
			//using WP's HTTP class
			$args = apply_filters('em_paypal_ipn_remote_get_args', array('httpversion'=>'1.1','user-agent'=>'EventsManagerPro/'.EMP_VERSION));
			$ipn_verification_result = wp_remote_get($domain.'?'.$req, $args);
			remove_action('http_api_curl','EM_Gateway_Paypal::payment_return_local_ca_curl',10,1);
			
			if ( !is_wp_error($ipn_verification_result) && $ipn_verification_result['body'] == 'VERIFIED' ) {
				//log ipn request if needed, then move on
				$status_log = $_POST['payment_status']." successfully received for {$_POST['mc_gross']} {$_POST['mc_currency']} (TXN ID {$_POST['txn_id']}) - Invoice: {$_POST['invoice']}";
				if( !empty($_POST['custom']) ) $status_log .= " - Custom Info: {$_POST['custom']}";
				EM_Pro::log( $status_log, 'paypal');
			}else{
			    //log error if needed, send error header and exit
				EM_Pro::log( array('IPN Verification Error', 'WP_Error'=> $ipn_verification_result, '$_POST'=> $_POST, '$req'=>$domain.'?'.$req), 'paypal' );
			    header('HTTP/1.0 502 Bad Gateway');
			    exit;
			}
			//if we get past this, then the IPN went ok
			
			// handle cases that the system must ignore
			$new_status = false;
			//Common variables
			$timestamp = date('Y-m-d H:i:s', strtotime($_POST['payment_date']));
			if( !empty($_POST['invoice']) ){
			    $invoice_values = explode('#', $_POST['invoice']);
			    $booking_id = $invoice_values[1];
			    $EM_Booking = em_get_booking($booking_id);
			    $transaction_match = $_POST['invoice'] == $this->get_invoice_id($EM_Booking);
			}else{
			    //legacy checking, newer bookings should have a unique invoice number
    			$custom_values = explode(':',$_POST['custom']);
    			$booking_id = $custom_values[0];
			    $EM_Booking = em_get_booking($booking_id);
			    $transaction_match = count($custom_values) == 2;
			}
			if( !empty($EM_Booking->booking_id) && !empty($transaction_match) ){
				//booking exists
				$EM_Booking->manage_override = true; //since we're overriding the booking ourselves.
				$user_id = $EM_Booking->person_id;
				
				// process PayPal response
				$this->handle_payment_status($EM_Booking, $_POST['mc_gross'], $_POST['payment_status'], $_POST['mc_currency'], $_POST['txn_id'], $timestamp, $_POST);
				EM_Pro::log( array('Valid IPN Request Received & Processed', '$_POST'=> $_POST, '$booking_id' => $booking_id), 'paypal' );
			}else{
				if( is_numeric($booking_id) && ($_POST['payment_status'] == 'Completed' || $_POST['payment_status'] == 'Processed') ){
					$message = apply_filters('em_gateway_paypal_bad_booking_email',"
A Payment has been received by PayPal for a non-existent booking. 

It may be that this user's booking has timed out yet they proceeded with payment at a later stage. 
							
In some cases, it could be that other payments not related to Events Manager are triggering this error. If that's the case, you can prevent this from happening by changing the URL in your IPN settings to:

". get_home_url() ." 

To refund this transaction, you must go to your PayPal account and search for this transaction:

Transaction ID : %transaction_id%
Email : %payer_email%

When viewing the transaction details, you should see an option to issue a refund.

If there is still space available, the user must book again.

Sincerely,
Events Manager
					", $booking_id, 0);
					$message  = str_replace(array('%transaction_id%','%payer_email%'), array($_POST['txn_id'], $_POST['payer_email']), $message);
					wp_mail(get_option('em_'. $this->gateway . "_email" ), __('Unprocessed payment needs refund'), $message);
					EM_Pro::log( array('Payment received for non-existent booking.', '$_POST'=> $_POST, '$booking_id' => $booking_id), 'paypal' );
				}else{
					//header('Status: 404 Not Found');
					echo 'Error: Bad IPN request, custom ID does not correspond with any pending booking.';
					//echo "<pre>"; print_r($_POST); echo "</pre>";
					EM_Pro::log( array('Error: Bad IPN request, custom ID does not correspond with any pending booking.', '$_POST'=> $_POST), 'paypal' );
					exit;
				}
			}
			//fclose($log);
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			//header('Status: 404 Not Found');
			echo 'Error: Missing POST variables. Identification is not possible. If you are not PayPal and are visiting this page directly in your browser, this error does not indicate a problem, but simply means EM is correctly set up and ready to receive IPNs from PayPal only.';
			exit;
		}
	}
	
	/**
	 * Handles a payment status change in PayPal, as in a IPN notification, PDT callback or other lookup.
	 * @param EM_Booking $EM_Booking
	 * @param float $amount
	 * @param string $payment_status
	 * @param string $currency
	 * @param string $txn_id
	 * @param string $timestamp Expects a format of 'Y-m-d H:i:s' for DB storage
	 * @param array $args Associative array of values matching those expected from an IPN notification, in order to have these processed by this function convert accordingly. The current keys referenced are 'ReasonCode' and 'pending_reason' for pending or reversed payments.
	 */
	public function handle_payment_status($EM_Booking, $amount, $payment_status, $currency, $txn_id, $timestamp, $args){
		$filter_args = array( 'amount' => $amount, 'payment_status' => $payment_status, 'payment_currency' => $currency, 'transaction_id' => $txn_id, 'timestamp' => $timestamp, 'args' => $args );
		switch ($payment_status) {
			case 'Completed':
			case 'Processed': // case: successful payment
				$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $txn_id, $payment_status, '');
		
				if( $amount >= $EM_Booking->get_price() && (!get_option('em_'.$this->gateway.'_manual_approval', false) || !get_option('dbem_bookings_approval')) ){
					$EM_Booking->approve(true, true); //approve and ignore spaces
				}else{
					//TODO do something if pp payment not enough
					$EM_Booking->set_status(0); //Set back to normal "pending"
				}
				do_action('em_payment_processed', $EM_Booking, $this, $filter_args);
				break;
		
			case 'Reversed':
			case 'Voided' :
				// case: charge back
				$note = 'Last transaction has been reversed. Reason: Payment has been reversed (charge back)';
				$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $txn_id, $payment_status, $note);
		
				//We need to cancel their booking.
				$EM_Booking->cancel();
				do_action('em_payment_reversed', $EM_Booking, $this, $filter_args);
		
				break;
		
			case 'Refunded':
				// case: refund
				$note = 'Payment has been refunded';
				$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $txn_id, $payment_status, $note);
				$amount = $amount < 0 ? $amount * -1 : $amount; //we need to compare two positive numbers for refunds
				if( $amount >= $EM_Booking->get_price() ){
					$EM_Booking->cancel();
				}else{
					$EM_Booking->set_status(0); //Set back to normal "pending"
				}
				do_action('em_payment_refunded', $EM_Booking, $this, $filter_args);
				break;
		
			case 'Denied':
				// case: denied
				$note = 'Last transaction has been reversed. Reason: Payment Denied';
				$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $txn_id, $payment_status, $note);
		
				$EM_Booking->cancel();
				do_action('em_payment_denied', $EM_Booking, $this, $filter_args);
				break;
		
			case 'In-Progress':
			case 'Pending':
				// case: payment is pending
				$pending_str = array(
					'address' => 'Customer did not include a confirmed shipping address',
					'authorization' => 'Funds not captured yet',
					'echeck' => 'eCheck that has not cleared yet',
					'intl' => 'Payment waiting for aproval by service provider',
					'multi-currency' => 'Payment waiting for service provider to handle multi-currency process',
					'unilateral' => 'Customer did not register or confirm his/her email yet',
					'upgrade' => 'Waiting for service provider to upgrade the PayPal account',
					'verify' => 'Waiting for service provider to verify his/her PayPal account',
					'paymentreview' => 'Paypal is currently reviewing the payment and will approve or reject within 24 hours',
					'*' => ''
				);
				$reason = @$args['pending_reason'];
				$note = 'Last transaction is pending. Reason: ' . (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);

				$this->record_transaction($EM_Booking, $amount, $currency, $timestamp, $txn_id, $payment_status, $note);

				do_action('em_payment_pending', $EM_Booking, $this, $filter_args);
				break;
			case 'Canceled_Reversal':
				//do nothing, just update the transaction
				break;
			default:
				// case: various error cases
		}
	}
	
	/**
	 * Fixes SSL issues with wamp and outdated server installations combined with curl requests by forcing a custom pem file, generated from - http://curl.haxx.se/docs/caextract.html
	 * @param resource $handle
	 */
	public static function payment_return_local_ca_curl( $handle ){
	    curl_setopt($handle, CURLOPT_CAINFO, dirname(__FILE__).DIRECTORY_SEPARATOR.'gateway.paypal.pem');
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
		//get creds and check they exist before even trying this
		$api_user = get_option('em_'. $this->gateway . '_api_username');
		$api_pass = get_option('em_'. $this->gateway . '_api_password');
		$api_sig = get_option('em_'. $this->gateway . '_api_signature');
		$api_help_tip = sprintf(esc_html__('API credentials are now required to auto-delete unpaid bookings after a certain period of time, to avoid any potential accidental deletions. For more information please see our %s page.', 'em-pro'), '<a href="http://wp-events-plugin.com/documentation/events-with-paypal/#api-keys" target="_blank">'.esc_html__('documentation','events-manager').'</a>');
		if( empty($api_user) || empty($api_pass) || empty($api_sig) ) {
			echo '<div class="error"><p>'.$api_help_tip.'</p></div>';
		}
		?>
		<table class="form-table">
		<tbody>
		  <?php em_options_input_text( esc_html__('Success Message', 'em-pro'), 'em_'. $this->gateway . '_booking_feedback', esc_html__('The message that is shown to a user when a booking is successful whilst being redirected to PayPal for payment.','em-pro') ); ?>
		  <?php em_options_input_text( esc_html__('Success Free Message', 'em-pro'), 'em_'. $this->gateway . '_booking_feedback_free', esc_html__('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be redirected to PayPal.','em-pro') ); ?>
		  <?php em_options_input_text( esc_html__('Thank You Message', 'em-pro'), 'em_'. $this->gateway . '_booking_feedback_completed', esc_html__('If you choose to return users to the default Events Manager thank you page after a user has paid on PayPal, you can customize the thank you message here.','em-pro') ); ?>
		</tbody>
		</table>
		<h3><?php echo sprintf(__('%s Credentials','em-pro'),'PayPal'); ?></h3>
		<p><strong><?php _e('Important:','em-pro'); ?></strong> <?php echo __('In order to connect PayPal with your site, you need to enable IPN on your account.'); echo " ". sprintf(__('Your return url is %s','em-pro'),'<code>'.$this->get_payment_return_url().'</code>'); ?></p> 
		<p><?php echo sprintf(__('Please visit the <a href="%s">documentation</a> for further instructions.','em-pro'), 'http://wp-events-plugin.com/documentation/events-with-paypal/'); ?></p>
		<table class="form-table">
			<tbody>
		  		<?php 
		  		em_options_input_text( esc_html__('PayPal Email', 'em-pro'), 'em_'. $this->gateway . '_email' );
		  		$status_modes = array('live' => __('Live Site', 'em-pro'), 'test' => __('Test Mode (Sandbox)', 'em-pro') );
		  		em_options_select(esc_html__('PayPal Mode', 'em-pro'), 'em_'. $this->gateway . "_status", $status_modes);
		  		?>
				<tr>
					<td colspan="2">
						<p><?php esc_html_e('The following API credentials are optional, but recommended. They will allow us to check whether payments have gone through in the event that IPN notifications fail to reach us.', 'em-pro'); ?>
						<?php esc_html_e('These credentials are required if you enable deleting bookings that remain unpaid after x minutes.', 'em-pro'); ?></p>
						<?php 
						if ( !is_ssl() ){
							echo '<p style="color:red;">';
							echo sprintf( esc_html__('Your site is not using SSL! Whilst not a requirement, if you\'re going to submit API information for a live PayPal account, we recommend you do so over a secure connection. If this is not possible, consider an alternative option of submitting your API information as covered in our %s.', 'em-pro'),
										'<a href="http://wp-events-plugin.com/documentation/events-with-paypal/safe-encryption-api-keys/">'.esc_html__('documentation','events-manager').'</a>');
							echo '</p>';
							if( (!defined('EMP_PAYPAL_SSL_OVERRIDE') || !EMP_PAYPAL_SSL_OVERRIDE) && (get_option('em_'. $this->gateway . "_status") == 'test' && empty($_REQUEST['show_keys'])) ){
								echo '<p>'.esc_html__('If you are only using testing credentials, you can display and save them safely.', 'em-pro');
								echo ' <a href="'. esc_url(add_query_arg('show_keys', wp_create_nonce('show_paypal_creds'))) .'" class="button-secondary">'. esc_html__('Show API Keys', 'em-pro') .'</a>';
								echo '</p>';
							}
						}
						?>
					</td>
				</tr>
				<?php $api_options = get_option('em_'. $this->gateway . '_api', array('username'=>'', 'password'=>'', 'signature'=>'')); ?>
				<?php if( is_ssl() || (defined('EMP_PAYPAL_SSL_OVERRIDE') && EMP_PAYPAL_SSL_OVERRIDE) || (get_option('em_'. $this->gateway . "_status") == 'test' && !empty($_REQUEST['show_keys']) && wp_verify_nonce($_REQUEST['show_keys'], 'show_paypal_creds')) ): ?>
					<tr valign="top" id='<?php echo esc_attr('em_'. $this->gateway . '_api_username'); ?>_row'>
						<th scope="row"><?php esc_html_e('API Username', 'em-pro'); ?></th>
					    <td>
							<input value="<?php echo esc_attr($api_options['username']); ?>" name="<?php echo esc_attr('em_'. $this->gateway . '_api_username') ?>" type="text" id="<?php echo esc_attr('em_'. $this->gateway . '_api_username') ?>" style="width: 95%" size="45" />
						</td>
					</tr>
					<tr valign="top" id='<?php echo esc_attr('em_'. $this->gateway . '_api_password'); ?>_row'>
						<th scope="row"><?php esc_html_e('API Password', 'em-pro'); ?></th>
					    <td>
							<input value="<?php echo esc_attr($api_options['password']); ?>" name="<?php echo esc_attr('em_'. $this->gateway . '_api_password') ?>" type="text" id="<?php echo esc_attr('em_'. $this->gateway . '_api_password') ?>" style="width: 95%" size="45" />
						</td>
					</tr>
					<tr valign="top" id='<?php echo esc_attr('em_'. $this->gateway . '_api_username'); ?>_row'>
						<th scope="row"><?php esc_html_e('API Signature', 'em-pro'); ?></th>
					    <td>
							<input value="<?php echo esc_attr($api_options['signature']); ?>" name="<?php echo esc_attr('em_'. $this->gateway . '_api_signature') ?>" type="text" id="<?php echo esc_attr('em_'. $this->gateway . '_api_signature') ?>" style="width: 95%" size="45" />
						</td>
					</tr>
		  		<?php else: ?>
		  			<?php foreach( array('username' => 'API Username', 'password' => 'API Password', 'signature'=> 'API Signature') as $opt => $label ): ?>
				  		<tr valign="top">
							<th scope="row"><?php echo esc_html(emp__($label)); ?></th>
			    			<td>
			    				<?php
			    				$option = $api_options[$opt];
			    				$chars = '';
			    				for( $i = 0; $i < strlen($option); $i++ ) $chars = $chars . '*';
			    				echo esc_html(str_replace( substr($option, 1, -1), $chars, $option) );
			    				?>
			    			</td>
			    		</tr>
			    	<?php endforeach; ?>
		  		<?php endif; ?>
			</tbody>
		</table>
		
		<h3><?php echo sprintf(__('%s Options','em-pro'),'PayPal'); ?></h3>
		<table class="form-table">
		<tbody>		  
			<?php em_options_radio_binary(__('Include Taxes In Itemized Prices', 'em-pro'), 'em_'. $this->gateway .'_inc_tax', __('If set to yes, taxes are not included in individual item prices and total tax is shown at the bottom. If set to no, taxes are included within the individual prices.','em-pro'). ' '. __('We strongly recommend setting this to No.','em-pro') .' <a href="http://wp-events-plugin.com/documentation/events-with-paypal/paypal-displaying-taxes/">'. __('Click here for more information.','em-pro')) .'</a>'; ?>
			<tr valign="top">
				<th scope="row"><?php _e('Paypal Currency', 'em-pro') ?></th>
				<td><?php echo esc_html(get_option('dbem_bookings_currency','USD')); ?><br /><i><?php echo sprintf(__('Set your currency in the <a href="%s">settings</a> page.','em-pro'),EM_ADMIN_URL.'&amp;page=events-manager-options#bookings'); ?></i></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('PayPal Language', 'em-pro') ?></th>
				<td>
					<select name="em_paypal_lc">
						<option value=""><?php _e('Default','em-pro'); ?></option>
					<?php
						$ccodes = em_get_countries();
						$paypal_lc = get_option('em_'.$this->gateway.'_lc', 'US');
						foreach($ccodes as $key => $value){
							if( $paypal_lc == $key ){
								echo '<option value="'.$key.'" selected="selected">'.$value.'</option>';
							}else{
								echo '<option value="'.$key.'">'.$value.'</option>';
							}
						}
					?>
					
					</select>
					<br />
					<i><?php _e('PayPal allows you to select a default language users will see. This is also determined by PayPal which detects the locale of the users browser. The default would be US.','em-pro') ?></i>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Return URL', 'em-pro') ?></th>
				<td>
					<input type="text" name="em_paypal_return" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_return" )); ?>" style='width: 40em;' /><br />
					<em><?php _e('Once a payment is completed, users will be offered a link to this URL which confirms to the user that a payment is made. If you would to customize the thank you page, create a new page and add the link here. For automatic redirect, you need to turn auto-return on in your PayPal settings.', 'em-pro'); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Cancel URL', 'em-pro') ?></th>
				<td>
					<input type="text" name="em_paypal_cancel_return" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_cancel_return" )); ?>" style='width: 40em;' /><br />
					<em><?php _e('Whilst paying on PayPal, if a user cancels, they will be redirected to this page.', 'em-pro'); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('PayPal Page Logo', 'em-pro') ?></th>
				<td>
					<input type="text" name="em_paypal_format_logo" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_format_logo" )); ?>" style='width: 40em;' /><br />
					<em><?php _e('Add your logo to the PayPal payment page. It\'s highly recommended you link to a https:// address.', 'em-pro'); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Border Color', 'em-pro') ?></th>
				<td>
					<input type="text" name="em_paypal_format_border" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_format_border" )); ?>" style='width: 40em;' /><br />
					<em><?php _e('Provide a hex value color to change the color from the default blue to another color (e.g. #CCAAAA).','em-pro'); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Delete Bookings Pending Payment', 'em-pro') ?></th>
				<td>
					<input type="text" name="em_paypal_booking_timeout" style="width:50px;" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_timeout" )); ?>" style='width: 40em;' /> <?php _e('minutes','em-pro'); ?><br />
					<em><?php echo sprintf( esc_html__('Once a booking is started and the user is taken to PayPal, Events Manager stores a booking record in the database to identify the incoming payment. These spaces may be considered reserved if you enable %s in your Events &gt; Settings page. If you would like these bookings to expire after x minutes, please enter a value above (note that bookings will be deleted, and any late payments will need to be refunded manually via PayPal).','em-pro'), '<b>'.esc_html__('Reserved unconfirmed spaces?', 'events-manager-pro').'</b>'); ?></em>
					<br/><br/>
					<em><?php echo $api_help_tip; ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Manually approve completed transactions?', 'em-pro') ?></th>
				<td>
					<input type="checkbox" name="em_paypal_manual_approval" value="1" <?php echo (get_option('em_'. $this->gateway . "_manual_approval" )) ? 'checked="checked"':''; ?> /><br />
					<em><?php _e('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-pro'); ?></em><br />
					<em><?php echo sprintf(__('Approvals must also be required for all bookings in your <a href="%s">settings</a> for this to work properly.','em-pro'),EM_ADMIN_URL.'&amp;page=events-manager-options'); ?></em>
				</td>
			</tr>
		</tbody>
		</table>
		<?php
	}

	/* 
	 * Run when saving PayPal settings, saves the settings available in EM_Gateway_Paypal::mysettings()
	 */
	function update() {
		$gateway_options = $options_wpkses = array();
		$gateway_options[] = 'em_'. $this->gateway . '_email';
		if( is_ssl() || (defined('EMP_PAYPAL_SSL_OVERRIDE') && EMP_PAYPAL_SSL_OVERRIDE) || (get_option('em_'. $this->gateway . "_status") == 'test' && !empty($_REQUEST['show_keys']) && wp_verify_nonce($_REQUEST['show_keys'], 'show_paypal_creds')) ){
			$gateway_options['em_'. $this->gateway . '_api'] = array('username', 'password', 'signature');
		}
		$gateway_options[] = 'em_'. $this->gateway . '_site';
		$gateway_options[] = 'em_'. $this->gateway . '_currency';
		$gateway_options[] = 'em_'. $this->gateway . '_inc_tax';
		$gateway_options[] = 'em_'. $this->gateway . '_lc';
		$gateway_options[] = 'em_'. $this->gateway . '_status';
		$gateway_options[] = 'em_'. $this->gateway . '_format_logo';
		$gateway_options[] = 'em_'. $this->gateway . '_format_border';
		$gateway_options[] = 'em_'. $this->gateway . '_manual_approval';
		$gateway_options[] = 'em_'. $this->gateway . '_booking_timeout';
		$gateway_options[] = 'em_'. $this->gateway . '_return';
		$gateway_options[] = 'em_'. $this->gateway . '_cancel_return';
		$gateway_options[] = 'em_'. $this->gateway . '_booking_feedback';
		$gateway_options[] = 'em_'. $this->gateway . '_booking_feedback_free';
		$gateway_options[] = 'em_'. $this->gateway . '_booking_feedback_completed';
		//add wp_kses sanitization filters for relevant options
		add_filter('gateway_update_'.'em_'. $this->gateway . '_email', 'trim');
		add_filter('gateway_update_'.'em_'. $this->gateway . '_booking_feedback', 'wp_kses_post');
		add_filter('gateway_update_'.'em_'. $this->gateway . '_booking_feedback_free','wp_kses_post');
		add_filter('gateway_update_'.'em_'. $this->gateway . '_booking_feedback_completed','wp_kses_post');
		//pass options to parent which handles saving
		return parent::update($gateway_options);
	}
}
EM_Gateways::register_gateway('paypal', 'EM_Gateway_Paypal');

/**
 * Deletes bookings pending payment that are more than x minutes old, defined by paypal options. 
 */
function em_gateway_paypal_booking_timeout(){
	global $wpdb;
	//get creds and check they exist before even trying this
	$api_options = get_option('em_paypal_api');
	$api_user = $api_options['username'];
	$api_pass = $api_options['password'];
	$api_sig = $api_options['signature'];
	if( empty($api_user) || empty($api_pass) || empty($api_sig) ) return false;
	//Get a time from when to delete
	$minutes_to_subtract = absint(get_option('em_paypal_booking_timeout'));
	$EM_Gateway_Paypal = EM_Gateways::get_gateway('paypal'); /* @var $EM_Gateway_Paypal EM_Gateway_Paypal */
	if( $minutes_to_subtract > 0 ){
		//get booking IDs without pending transactions
		$cut_off_time = date('Y-m-d H:i:s', current_time('timestamp') - ($minutes_to_subtract * 60));
		$sql = 'SELECT b.booking_id FROM '.EM_BOOKINGS_TABLE.' b LEFT JOIN '.EM_TRANSACTIONS_TABLE." t ON t.booking_id=b.booking_id  WHERE booking_date < '{$cut_off_time}' AND booking_status=4 AND transaction_id IS NULL AND booking_meta LIKE '%s:7:\"gateway\";s:6:\"paypal\";%'";
		if( get_option('dbem_multiple_bookings') ){ //multiple bookings mode
			//If we're in MB mode, check that this isn't the main booking, if it isn't then skip it.
			$sql .= ' AND b.booking_id NOT IN (SELECT booking_id FROM '. EM_BOOKINGS_RELATIONSHIPS_TABLE.')';
		}
		$booking_ids = $wpdb->get_col( $sql );
		if( count($booking_ids) > 0 ){
			//go through each booking and check if there's a matching payment on paypal already, in case there's problems with IPN callbacks
			foreach( $booking_ids as $booking_id ){
			    $EM_Booking = em_get_booking($booking_id);
			    if( empty($EM_Booking->booking_meta['uuid']) ) continue; //now, only items with a UUID will be checked, as we know we have a valid invoice ID passed for this booking
			    //Verify if Payment has been made by searching for the Invoice ID, which would be EM-BOOKING#x were x is the booking id
			    $domain = get_option( 'em_paypal_status' ) == 'live' ? 'https://api-3t.paypal.com/nvp' : $domain = 'https://api-3t.sandbox.paypal.com/nvp';
			    $post_vars = array(
			    	'USER' => $api_user, //CHANGE
			    	'PWD' => $api_pass, //CHANGE
			    	'SIGNATURE' => $api_sig,
			    	'METHOD' => 'TransactionSearch',
			    	'VERSION' => '204',
			    	'STARTDATE' => date('Y-m-d', strtotime('-1 Month')).'T00:00:00Z', //1 month back just to be sure
			    	'INVNUM' => $EM_Gateway_Paypal->get_invoice_id($EM_Booking)
			    );
			    $nvp_vars = "";
		    	foreach($post_vars as $k => $v ){
		    		$nvp_vars .= ($nvp_vars ? "&" : "").$k.'='.urlencode($v);
		    	} unset($k, $v);
			    @set_time_limit(60);
			    //set request values
			    $args = apply_filters('em_paypal_txn_search_remote_args', array(
			    	'httpversion'=>'1.1',
			    	'user-agent'=>'EventsManagerPro/'.EMP_VERSION, 
			    	'method'=>'POST', 
			    	'body'=>$nvp_vars
			    ));
			    //add a CA certificate so that SSL requests always go through
			    add_action('http_api_curl','EM_Gateway_Paypal::payment_return_local_ca_curl',10,1);
			    //using WP's HTTP class
			    $nvp_response = wp_remote_request($domain, $args);
			    remove_action('http_api_curl','EM_Gateway_Paypal::payment_return_local_ca_curl',10,1);

			    if ( !is_wp_error($nvp_response) ) {
			    	//we expect a single result from this search, since searching for a invoice ID should be unique
			    	$nvp_result_raw = explode('&', $nvp_response['body']);
			    	$nvp_results = array();
			    	foreach($nvp_result_raw as $v ){
			    		$nvp_result_raw = explode('=', $v);
			    		$nvp_results[$nvp_result_raw[0]] = urldecode($nvp_result_raw[1]);
			    	}
			    	if( !empty($nvp_results['ACK']) && $nvp_results['ACK'] == 'Success' ){
			    		//check response and see whether we have an actual pending booking
			    		$delete_booking = false; //conservatively decide not to delete a booking by default
			    		if( !empty($nvp_results['L_STATUS0']) ){
			    			//we received a result, so we shouldn't delete this payment and act as if we received an IPN
			    			$args = array('pending_reason' => $nvp_results['L_STATUS0']);
			    			$timestamp = strtotime('Y-m-d H:i:s', strtotime($nvp_results['L_TIMESTAMP0']));
			    			$EM_Gateway_Paypal->handle_payment_status($EM_Booking, $nvp_results['L_AMT0'], $nvp_results['L_STATUS0'], $nvp_results['L_CURRENCYCODE0'], $nvp_results['L_TRANSACTIONID0'], $timestamp, $args);
			    			EM_Pro::log( array('Payment located via NVP for booking awaiting payment and status handled.', '$nvp_results' => $nvp_results), 'paypal');
			    		}else{
			    			//search produced no results, so we assume there's no payment made and just delete the booking
			    			$delete_booking = true;
			    		}
			    		//only if a payment hasn't been made do we delete the booking
			    		if( $delete_booking ){
			    		    EM_Pro::log( array('Booking set to be deleted due to awaiting payment time out.', 'Booking Info' => $EM_Booking->to_array()), 'paypal');
			    			$EM_Booking->manage_override = true;
			    			$EM_Booking->delete();
			    		}
			    	}else{
			    		//some sort of error, log if needed but we won't delete anything
			    		EM_Pro::log( array('TransactionSearchError', 'WP_Error'=> $nvp_response, '$_POST'=> $_POST, '$url'=>$domain, 'Booking ID'=> $EM_Booking->booking_id), 'paypal-timeout-delete' );
			    	}
			    }else{
			    	//log error if needed, send error header and exit
			    	EM_Pro::log( array('TransactionSearchError', 'WP_Error'=> $nvp_response, '$_POST'=> $_POST, '$url'=>$domain, 'Booking ID'=> $EM_Booking->booking_id), 'paypal-timeout-delete' );
			    }
			}
		}
	}
}
add_action('emp_paypal_cron', 'em_gateway_paypal_booking_timeout');
?>