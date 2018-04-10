<?php
//TODO make coupons stackable
//TODO add logging of coupon useage in seperate log table
include('coupon.php');
class EM_Coupons extends EM_Object {
    
    static public $can_manage = 'manage_others_bookings';
    
	public static function init(){
	    if( is_admin() ){
	        include('coupons-admin.php');
	    }
		//add field to booking form and ajax
		if( get_option('dbem_multiple_bookings') ){ //multiple bookings mode
		    //add coupon field to checkout page booking form
			add_action('em_cart_footer', 'EM_Coupons::em_cart_footer');
			//hook into booking submission to add discount and coupon info
			add_filter('em_multiple_booking_validate', array('EM_Coupons', 'em_booking_validate'), 10, 2);
			add_filter('em_multiple_booking_save', array('EM_Coupons', 'em_booking_save'), 10, 2);
			//ajax to apply coupon
			add_action('wp_ajax_em_coupon_apply',array('EM_Coupons', 'cart_coupon_apply_ajax'));
			add_action('wp_ajax_nopriv_em_coupon_apply',array('EM_Coupons', 'cart_coupon_apply_ajax'));
			//AJAX fallback to apply coupon
			if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'em_coupon_apply' && !defined('DOING_AJAX') ){
				add_action('init', array('EM_Coupons', 'cart_coupon_apply_fallback'), 100);
			}
		}else{ //normal mode
		    //add to any booking form
			add_action('em_booking_form_footer', array('EM_Coupons', 'em_booking_form_footer'),1,2);
			//meta box hook for adding coupons to booking info
			add_filter('em_event_get_post_meta',array('EM_Coupons', 'em_event_get_post_meta'),10,2);
			add_filter('em_event_save_meta',array('EM_Coupons', 'em_event_save_meta'),10,2);
			add_filter('em_event_save_events',array('EM_Coupons', 'em_event_save_events'),10,3);
			add_filter('em_event_delete_meta',array('EM_Coupons', 'em_event_delete_meta'),10,2);
			//hook into booking submission to add discount and coupon info
			add_filter('em_booking_get_post', array('EM_Coupons', 'em_booking_get_post'), 10, 2);
			add_filter('em_booking_validate', array('EM_Coupons', 'em_booking_validate'), 10, 2);
			add_filter('em_booking_save', array('EM_Coupons', 'em_booking_save'), 10, 2);
			//add ajax response for coupon code queries
			add_action('wp_ajax_em_coupon_check',array('EM_Coupons', 'coupon_check_ajax'));
			add_action('wp_ajax_nopriv_em_coupon_check',array('EM_Coupons', 'coupon_check_ajax'));
		}
		//deal with bookings that have coupons when they get deleted or cancelled
		add_filter('em_booking_delete', array('EM_Coupons','em_booking_delete'), 10, 2);
		add_filter('em_booking_set_status', array('EM_Coupons','em_booking_set_status'), 10, 2);
		add_filter('em_bookings_delete', array('EM_Coupons','em_bookings_delete'), 10, 3);
        //show available coupons on event booking admin area
		add_action('em_events_admin_bookings_footer',array('EM_Coupons', 'admin_meta_box'),20,1);
		//placeholders
		add_filter('em_booking_output_placeholder',array('EM_Coupons','placeholders'),1,3); //for email
		//hook into price calculations
		add_filter('em_booking_get_price_adjustments', array('EM_Coupons', 'em_booking_get_price_adjustments'), 10, 3);
		//add coupon info to CSV
		add_action('em_bookings_table_cols_template', array('EM_Coupons', 'em_bookings_table_cols_template'),10,1);
		add_filter('em_bookings_table_rows_col_coupon', array('EM_Coupons', 'em_bookings_table_rows_col_coupon'), 10, 3);
		//add css for coupon field
		add_action('wp_head',array('EM_Coupons', 'wp_head'));
		add_action('admin_head',array('EM_Coupons', 'wp_head'));
	}
	
	public static function em_booking_get_price_adjustments( $adjustments, $type, $EM_Booking ){
		if( $type == 'discounts' ){
		    $coupons = self::booking_get_coupons($EM_Booking);
		    if( is_array($coupons) && count($coupons) > 0 ){
		        //merge coupons into discounts array in new discounts format
				foreach($coupons as $EM_Coupon){ /* @var $EM_Coupon EM_Coupon */
				    $adjustments[] = array(
				    	'name' => $EM_Coupon->coupon_code . ' - '. $EM_Coupon->get_discount_text(),
				    	'type'=> $EM_Coupon->coupon_type,
				    	'amount'=> $EM_Coupon->coupon_discount,
				    	'desc' => $EM_Coupon->coupon_name,
				    	'tax'=> $EM_Coupon->coupon_tax
				    );
				}
		    }
		}
	    return $adjustments;
	}
	
	/* Event Helpers */

	/**
	 * Depricated, renamed to event_get_coupon
	 */
	public static function get_coupon($code, $EM_Event){ self::event_get_coupon($code, $EM_Event); }
	/**
	 * @param int $code
	 * @param EM_Event $EM_Event
	 * @return EM_Coupon|boolean
	 */
	public static function event_get_coupon($code, $EM_Event){
	    global $wpdb;
		//get coupons that are event and sitewide
		if( !empty($EM_Event->event_id) ){
		    $coupons = EM_Coupons::get(array('code'=>$code,'event'=>$EM_Event->event_id));
		    if( count($coupons) > 0 ){
	            foreach($coupons as $EM_Coupon){ /* @var $EM_Coupon EM_Coupon */
		            if( !empty($EM_Coupon->coupon_code) && $EM_Coupon->coupon_code == $code ){
		            	return $EM_Coupon;
		            }
	            }
		    }
		}
		return false;
	}
	
	/**
	 * Gets all coupons available to an event
	 * @param EM_Event $EM_Event
	 * @return array
	 */
	public static function event_get_coupons($EM_Event){
	    if( empty($EM_Event->coupons) ){
	    	if( !empty($EM_Event->event_id) ){
	    		$EM_Event->coupons = EM_Coupons::get(array('event'=>$EM_Event->event_id));
	    	}else{
	    		$EM_Event->coupons = array();
	    	}
	    }
	    return $EM_Event->coupons;
	}
	
	/**
	 * Gets all coupon ids available to an event
	 * @param EM_Event $EM_Event
	 * @return array
	 */
	public static function event_get_coupon_ids($EM_Event){
	    if( empty($EM_Event->coupon_ids) ){
	    	if( !empty($EM_Event->event_id) ){
	    		$EM_Event->coupon_ids = EM_Coupons::get(array('event'=>$EM_Event->event_id, 'ids'=>true));
	    	}else{
	    		$EM_Event->coupon_ids = array();
	    	}
	    }
	    return $EM_Event->coupon_ids;
	}
	
	/**
	 * @param EM_Event $EM_Event
	 * @return boolean
	 */
	public static function event_has_coupons($EM_Event){
	    if( !isset($EM_Event->coupon_count) ){
	    	if( !empty($EM_Event->event_id) ){
	    		$EM_Event->coupons_count = EM_Coupons::count(array('event'=>$EM_Event->event_id));
	    	}else{
	    		$EM_Event->coupons_count = array();
	    	}
	    }
	    return $EM_Event->coupons_count > 0;
	}
	
	/* Booking Helpers */
	public static function booking_has_coupons($EM_Booking){
	    return !empty($EM_Booking->booking_meta['coupon']) || !empty($EM_Booking->booking_meta['coupons']);
	}
	
	/**
	 * 
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	public static function booking_get_coupons($EM_Booking){
	    $coupons = array();
	    if( !empty($EM_Booking->booking_meta['coupon']) ){
	        $EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
	        $coupons[$EM_Coupon->coupon_id] = $EM_Coupon;
	    }
	    /* Use this when stacking coupons
	    if( !empty($EM_Booking->booking_meta['coupons']) && is_array($EM_Booking->booking_meta['coupons']) ){
	        foreach($EM_Booking->booking_meta['coupons'] as $coupon){
	        	$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta[$coupon]);
				$coupons[$EM_Coupon->coupon_id] = $EM_Coupon;
	        }
	    }
	    */
	    return $coupons;
	}
	
	/* Multiple Booking Functions */
	
	public static function cart_coupon_apply( $coupon_code ){
		$EM_Multiple_Booking = EM_Multiple_Bookings::get_multiple_booking();
		if(!empty($_REQUEST['coupon_code'])){
			$EM_Coupon = new EM_Coupon($_REQUEST['coupon_code'], 'code');
			if( !empty($EM_Coupon->coupon_id) ){
				if( $EM_Coupon->is_valid() ){
					$EM_Multiple_Booking->booking_meta['coupon'] = $EM_Coupon->to_array(); //we add an clean a coupon array here for the first time
					$EM_Multiple_Booking->calculate_price(); //refresh price
					return true;
				}
			}
		}
		return false;
	}
	
	public static function cart_coupon_apply_fallback(){
		global $EM_Notices;
		if(!empty($_REQUEST['coupon_code'])){
			if( !self::cart_coupon_apply($_REQUEST['coupon_code']) ){
				$EM_Notices->add_error(__('Coupon Invalid','em-pro'));
			}
		}else{
			$EM_Notices->add_error(__('Coupon Not Found', 'em-pro'));
		}
	}
	
	public static function cart_coupon_apply_ajax(){
	    $response = array('result'=>false, 'message'=> __('Coupon Not Found', 'em-pro'));
		if(!empty($_REQUEST['coupon_code'])){
			if( self::cart_coupon_apply($_REQUEST['coupon_code']) ){
				$response['result'] = true;
				$response['message'] = '';
			}else{
				$response['message'] = __('Coupon Invalid','em-pro');
			}
		}
        header('Content-Type: text/javascript; charset=utf-8'); //to prevent MIME type errors in MultiSite environments
		echo EM_Object::json_encode($response);
		exit();
	}
	
	/*
	 * MODIFYING BOOKING Functions 
	 */	
	
	/**
	 * @param boolean $result
	 * @param EM_Booking $EM_Booking
	 * @return boolean
	 */
	public static function em_booking_get_post( $result, $EM_Booking){ 
		if( !empty($_REQUEST['coupon_code']) ){
			$EM_Coupon = EM_Coupons::event_get_coupon($_REQUEST['coupon_code'], $EM_Booking->get_event());
			if( $EM_Coupon === false && !empty($EM_Booking->booking_id) ){ //if a previously saved booking, account for the fact it may not work
				$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
			}
			if( $EM_Coupon !== false ){
				$EM_Booking->booking_meta['coupon'] = $EM_Coupon->to_array(); //we add an clean a coupon array here for the first time
				$EM_Booking->calculate_price(); //refresh price
			}else{
				$EM_Booking->booking_meta['coupon'] = array('coupon_code'=>$_REQUEST['coupon_code']); //will not validate later
			}
		}
		return apply_filters('em_coupons_em_booking_get_post', $result, $EM_Booking);
	}
	
	public static function em_booking_validate($result, $EM_Booking){
		if( !empty($EM_Booking->booking_meta['coupon']) ){
			$EM_Coupon = self::event_get_coupon($EM_Booking->booking_meta['coupon']['coupon_code'], $EM_Booking->get_event());
			if( $EM_Coupon === false && !empty($EM_Booking->booking_id) ){ //if a previously saved booking, account for the fact it may not work
				$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
			}elseif( $EM_Coupon === false && get_class($EM_Booking) == 'EM_Multiple_Booking'){ //multiple bookings have no event-specific coupons, so use this 
				$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']['coupon_id']);
				if( $EM_Coupon->coupon_code != $EM_Booking->booking_meta['coupon']['coupon_code'] ){
					$EM_Coupon = false;
				}
			}
			if( $EM_Coupon === false || !$EM_Coupon->is_valid() ){
				$EM_Booking->add_error(__('Invalid coupon code provided','em-pro'));
				unset($EM_Booking->booking_meta['coupon']);
				return false;
			}
		}
		return apply_filters('em_coupons_em_booking_validate', $result, $EM_Booking);
	}
	
	public static function em_booking_save($result, $EM_Booking){
		if( $result ){
			self::refresh_counts($EM_Booking);
		}
		return apply_filters('em_coupons_em_booking_save', $result, $EM_Booking);
	}

	/**
	 * @param string $replace
	 * @param EM_Booking $EM_Booking
	 * @param string $full_result
	 * @return string
	 */
	public static function placeholders($replace, $EM_Booking, $full_result){
		if( empty($replace) || $replace == $full_result ){
			if( $full_result == '#_BOOKINGCOUPON' ){
				$replace = '';
				if( !empty($EM_Booking->booking_meta['coupon']) ){
					$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
					$replace = $EM_Coupon->coupon_code.' - '.$EM_Coupon->get_discount_text();					
				}
			}elseif( $full_result == '#_BOOKINGCOUPONCODE' ){
				$replace = '';
				if( !empty($EM_Booking->booking_meta['coupon']) ){
					$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
					$replace = $EM_Coupon->coupon_code;					
				}
			}elseif( $full_result == '#_BOOKINGCOUPONDISCOUNT' ){
				$replace = '';
				if( !empty($EM_Booking->booking_meta['coupon']) ){
					$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
					$replace = $EM_Coupon->get_discount_text();					
				}
			}elseif( $full_result == '#_BOOKINGCOUPONNAME' ){
				$replace = '';
				if( !empty($EM_Booking->booking_meta['coupon']) ){
					$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
					$replace = $EM_Coupon->coupon_name;					
				}
			}elseif( $full_result == '#_BOOKINGCOUPONDESCRIPTION' ){
				$replace = '';
				if( !empty($EM_Booking->booking_meta['coupon']) ){
					$EM_Coupon = new EM_Coupon($EM_Booking->booking_meta['coupon']);
					$replace = $EM_Coupon->coupon_description;					
				}
			}
		}
		return $replace; //no need for a filter, use the em_booking_email_placeholders filter
	}
	
	public static function em_event_get_post_meta($result, $EM_Event){
		$EM_Event->coupons = array();
		if(!empty($_REQUEST['em_coupons']) && is_array($_REQUEST['em_coupons'])){
		 	$EM_Event->coupons = EM_Coupons::get($_REQUEST['em_coupons']);
		}
		return $result;
	}
	
	public static function em_event_save_meta($result, $EM_Event){
		global $wpdb;
		if( $result && !empty($EM_Event->event_id) ){
			$wpdb->query("DELETE FROM ".EM_META_TABLE." WHERE meta_key='event-coupon' AND object_id=".$EM_Event->event_id);
			$inserts = array();
			foreach(self::event_get_coupons($EM_Event) as $EM_Coupon){
				//save record of coupons
				if( !$EM_Coupon->coupon_sitewide && !$EM_Coupon->coupon_eventwide ){
					$inserts[] = $wpdb->prepare("( %d, 'event-coupon', %d )", array($EM_Event->event_id, $EM_Coupon->coupon_id));
				}
			}
			if( count($inserts) > 0 ) $wpdb->query('INSERT INTO '.EM_META_TABLE." (object_id, meta_key, meta_value) VALUES ".implode(',', $inserts));
		}
		return $result;
	}
	
	public static function em_event_save_events($result, $EM_Event, $event_ids){
		global $wpdb;
		if( $result ){
			//delete all previous records of event coupons
			$wpdb->query("DELETE FROM ".EM_META_TABLE." WHERE meta_key='event-coupon' AND object_id IN (".implode(',',$event_ids).")");
			//build template insert
			$insert_templates = $inserts = array();
			foreach(self::event_get_coupons($EM_Event) as $EM_Coupon){
				$insert_templates[] = "( %d, 'event-coupon', ". $wpdb->prepare("%d )", array($EM_Coupon->coupon_id));
			}
			if( count($insert_templates) > 0 ){
				foreach($event_ids as $event_id){
					foreach($insert_templates as $template){
						$inserts[] = $wpdb->prepare($template, array($event_id));
					}
				}
				if( count($inserts) > 0 ) $wpdb->query('INSERT INTO '.EM_META_TABLE." (object_id, meta_key, meta_value) VALUES ".implode(',', $inserts));
			}
		}
		return $result;
	}
	
	public static function em_event_delete_meta($result, $EM_Event){
		//TODO deleted events should delete coupon references
		global $wpdb;
		if($result){
			$result_coupons = $wpdb->query("DELETE FROM ".EM_META_TABLE." WHERE meta_key='event-coupon' AND object_id=".$EM_Event->event_id);
		}
		return $result && $result_coupons !== false;
	}
	
	/**
	 * Removes coupon associations and update the count once booking is deleted.
	 * @param bool $result
	 * @param EM_Booking $EM_Booking
	 * @return bool
	 */
	public static function em_booking_delete($result, $EM_Booking){
		global $wpdb;
		if($result){
			self::refresh_counts($EM_Booking);
		}
		return $result;
	}
	
	/**
	 * Remove coupon associations and update the counts with these deleted bookings.
	 * @param bool $result
	 * @param array $booking_ids
	 * @param EM_Bookings $EM_Bookings
	 * @return bool
	 */
	public static function em_bookings_delete($result, $booking_ids, $EM_Bookings){
		/* @todo when coupon-booking relations are stored, use $booking_ids instead. */
		if( $result ){
			foreach( $EM_Bookings->bookings as $EM_Booking ){
				self::em_booking_delete($result, $EM_Booking);
			}
		}
		return $result;
	}
	
	/**
	 * When the booking status is changed, if the booking has a coupon and is cancelled or rejected, we remove the coupon from this event.
	 * @param bool $result
	 * @param EM_Booking $EM_Booking
	 */
	public static function em_booking_set_status($result, $EM_Booking){
		global $wpdb;
		if($result && in_array($EM_Booking->booking_status, array(2,3)) ){
			//booking cancelled or rejected, so we remove the coupon from the booking and lower the count for coupons:
			self::refresh_counts($EM_Booking);
			unset($EM_Booking->booking_meta['coupon']);
			$EM_Booking->save(false); //important, save and DON'T send an email
		}
		return $result;
	}
	
	/**
	 * Deprecated, use EM_Coupons::refresh_counts instead
	 */
	public static function lower_booking_count( $EM_Booking ){
		return apply_filters('em_coupons_lower_booking_count', self::refresh_counts($EM_Booking), $EM_Booking);
	}
	
	/**
	 * Recounts all coupon usages for a specific booking. Returns true if successful, false if not.
	 * @param EM_Booking $EM_Booking
	 * @return bool
	 */
	public static function refresh_counts( $EM_Booking ){
		$result = true;
		foreach( self::booking_get_coupons($EM_Booking) as $EM_Coupon ){
			$result = $EM_Coupon->recount() !== false && $result;
		}
		return apply_filters('em_coupons_refresh_counts', $result, $EM_Booking);
	}
	
	/**
	 * Outputs coupon code input field at bottom of booking form.
	 * @param EM_Event $EM_Event
	 */
	public static function em_booking_form_footer($EM_Event){
		if( !$EM_Event->is_free(true) && EM_Coupons::event_has_coupons($EM_Event) > 0){
			?>
			<p class="em-bookings-form-coupon">
				<label><?php _e('Coupon Code','em-pro'); ?></label>
				<input type="text" name="coupon_code" class="input em-coupon-code" />
			</p>
			<?php
			add_action('em_booking_js_footer', array('EM_Coupons', 'em_booking_js_footer') );
		}
	}
	
	/**
	 * Echoes a coupon code field in the footer of the cart page. Currently used in MB mode only.
	 * @param EM_Multiple_Booking $EM_Multiple_Booking
	 */
	public static function em_cart_footer($EM_Multiple_Booking){
		if( $EM_Multiple_Booking->get_price() > 0 && !self::booking_has_coupons($EM_Multiple_Booking) && EM_Coupons::count(array('limit'=>1)) > 0 ){
		?>
		<form id='em-cart-coupons-form' class="em-cart-coupons-form em-cart-actions" name='cart-coupons-form' method='post' action='<?php echo apply_filters('em_checkout_coupons_form_action_url',''); ?>#em-booking'>
		 	<input type='hidden' name='action' value='em_coupon_apply'/>
		 	<input type='hidden' name='_wpnonce' value='<?php echo wp_create_nonce('emp_checkout'); ?>'/>
		 	<div>
				<input type="text" name="coupon_code" class="input em-coupon-code" />
				<button type="submit" class="em-coupon-code"><?php _e('Apply Discount','em-pro'); ?></button>
			</div>
		</form>
		<?php
		add_action('em_cart_js_footer', array('EM_Coupons', 'em_cart_gateway_js') );
		add_action('em_cart_gateway_js', array('EM_Coupons', 'em_cart_gateway_js') );
		}
	}
	
	public static function wp_head(){
		//override this with CSS in your own theme
		?>
		<style type="text/css">
			.em-coupon-code { width:150px; }
			#em-coupon-loading { display:inline-block; width:16px; height: 16px; margin-left:4px; background:url(<?php echo plugins_url('events-manager-pro/includes/images/spinner.gif','events-manager-pro'); ?>)}
			.em-coupon-message { display:inline-block; margin:5px 0px 0px 105px; text-indent:22px; }
			.em-coupon-success { color:green; background:url(<?php echo plugins_url('events-manager-pro/includes/images/success.png','events-manager-pro'); ?>) 0px 0px no-repeat }
			.em-coupon-error { color:red; background:url(<?php echo plugins_url('events-manager-pro/includes/images/error.png','events-manager-pro'); ?>) 0px 0px no-repeat }
			.em-cart-coupons-form .em-coupon-message{ margin:5px 0px 0px 0px; }
			#em-coupon-loading { margin-right:4px; }	
		</style>
		<?php
	}
	
	public static function em_cart_gateway_js(){
		include('coupons-cart.js');
	}
	
	public static function em_booking_js_footer(){
		include('coupons.js');
	}
	
	public static function coupon_check_ajax(){
		$result = array('result'=>false, 'message'=> __('Coupon Not Found', 'em-pro'));
		if(!empty($_REQUEST['event_id'])){
			$EM_Event = new EM_Event($_REQUEST['event_id']);
			$EM_Coupon = self::event_get_coupon($_REQUEST['coupon_code'], $EM_Event);
			if( !empty($EM_Event->event_id) && is_object($EM_Coupon) ){
				if( $EM_Coupon->is_valid() ){
					$result['result'] = true;
					$result['message'] = $EM_Coupon->get_discount_text();
				}else{
					$result['message'] = __('Coupon Invalid','em-pro');
				}
			}
		}
		echo EM_Object::json_encode($result);
		exit();
	}
	
	/**
	 * @param EM_Event $EM_Event
	 */
	public static function admin_meta_box($EM_Event){
		//load this only when needed, so moved into the EM_Coupons_Admin object, 
		include_once('coupons-admin.php');
		EM_Coupons_Admin::admin_meta_box($EM_Event);
	}
	
	/**
	 * Returns an array of EM_Coupon objects, accepts search arguments or a numeric array for ids to retreive
	 * @param boolean $args
	 * @param boolean $count
	 * @return array
	 */
	public static function get( $args = array(), $count=false ){
		global $wpdb;
		$coupons_table = EM_COUPONS_TABLE;
		$coupons = array();
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$sql = "SELECT * FROM $coupons_table WHERE coupon_id IN (".implode(",", $args).")";
			$results = $wpdb->get_results($sql,ARRAY_A);
			foreach($results as $result){
				$coupons[$result['coupon_id']] = new EM_Coupon($result);
			}
			return apply_filters('em_coupons_get', $coupons, $args); //We return all the events matched as an EM_Event array. 
		}
		
		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get the default conditions
		$conditions = self::build_sql_conditions($args);
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		
		//Get ordering instructions
		$orderby = array('coupon_name','coupon_code');
		//Now, build orderby sql
		$orderby_sql = ( count($orderby) > 0 ) ? 'ORDER BY '. implode(', ', $orderby) : '';
		
		$selectors = '*';
		if( !empty($args['ids']) ) $selectors = 'coupon_id';
		if( $count ) $selectors = 'COUNT(*)';
		
		//Create the SQL statement and execute
		$sql = "
			SELECT $selectors FROM $coupons_table
			$where
			$orderby_sql
			$limit $offset
		";

		//If we only want the ids, the $selectors was already modified, so return a col instead
		if( !empty($args['ids']) ) {
			return apply_filters('em_coupons_get_ids', $wpdb->get_col($sql), $args);
		}
		//If we're only counting results, return the number of results
		if( $count ){
			return apply_filters('em_coupons_get_array', $wpdb->get_var($sql), $args);	
		}
		
		$results = $wpdb->get_results($sql, ARRAY_A);
		
		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return apply_filters('em_coupons_get_array', $results, $args);
		}
		
		foreach ( $results as $coupon ){
			$coupons[$coupon['coupon_id']] = new EM_Coupon($coupon);
		}
		return apply_filters('em_coupons_get', $coupons, $args);
	}
	
	public static function count($args = array() ){
		return self::get($args, true);
	}
	
	/*
	 * CSV Functions
	 */
	
	public static function em_bookings_table_cols_template($template){
		if( get_option('dbem_multiple_bookings') ){
			$template['coupon'] = '[MB] ' . __('Coupon Code','em-pro');
		}else{
			$template['coupon'] = __('Coupon Code','em-pro');
		}
		return $template;
	}
	
	public static function em_bookings_table_rows_col_coupon($val, $EM_Booking){
		//if in MB mode, change $EM_Booking with the main booking to grab coupon info, given that we don't support per-event coupons in MB mode atm
		if( get_option('dbem_multiple_bookings') ){
			$EM_Multiple_Booking = EM_Multiple_Bookings::get_main_booking($EM_Booking);
			if( $EM_Multiple_Booking !== false ){
				$EM_Booking = $EM_Multiple_Booking;
			}
		}
		//check if coupon code exists for this booking, if so, get it and replace $val with coupon code
		if( self::booking_has_coupons($EM_Booking) ){
			$vals = array();
			$coupons = self::booking_get_coupons($EM_Booking);
			foreach( $coupons as $EM_Coupon ){
				$vals[] = $EM_Coupon->coupon_code;
			}
			$val = implode(' ', $vals);
		}
		return $val;
	}

	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_conditions()
	 */
	public static function build_sql_conditions( $args = array() ){
		$conditions = array();
		//search specific event
		if( !empty($args['code']) ){
            global $wpdb;
            $conditions['code'] = $wpdb->prepare("coupon_code = '%s'", array($args['code']));
        }
		if( !empty($args['event']) && is_numeric($args['event']) && !get_option('dbem_multiple_bookings') ){ //if in MB mode, there are not event-specific coupons atm
			$conditions['event'] = "coupon_id IN (SELECT meta_value FROM ".EM_META_TABLE." WHERE object_id='{$args['event']}' AND meta_key='event-coupon')";
			//search event-wide coupons by default
			if( !empty($args['eventwide']) ){
				$EM_Event = em_get_event($args['event']);
				if( !empty($EM_Event->event_id) ){
					if( $args['eventwide'] === 1 || $args['eventwide'] === true ){
						//in this case, we explicitly want eventwide coupons
						$conditions['eventwide'] = "coupon_eventwide=1 AND coupon_owner='{$EM_Event->event_owner}'";
					}else{
						//if not explicitly requested in args, then we just search for eventwide according to event owner
						$conditions['event'] .= " OR (coupon_eventwide=1 AND coupon_owner='{$EM_Event->event_owner}')";
					}
				}
			}
			//search sitewide coupons by default or if requested
			if( !empty($args['sitewide']) ){
				//sitewide shouldn't have an event requested with it if you only want sitewide events
				$conditions['event'] .= ' OR coupon_sitewide=1 ';
			}else{
				$conditions['sitewide'] = 'coupon_sitewide=1';
			}
			$conditions['event'] = '('.$conditions['event'].')';
		}else{
			//blog ownership
			if( EM_MS_GLOBAL ){
                $blog = (array_key_exists('blog',$args) && is_numeric($args['blog'])) ? $args['blog']:get_current_blog_id();
				if( is_main_site($blog) ){
					$conditions['blog'] = "(".EM_COUPONS_TABLE.".blog_id={$blog} OR ".EM_COUPONS_TABLE.".blog_id IS NULL)";
				}else{
					$conditions['blog'] = "(".EM_COUPONS_TABLE.".blog_id={$blog})";
				}
			}
			//if in MB mode, every coupon is considered sitewide.
            if( !get_option('dbem_multiple_bookings') ){ 
    			//owner lookup
    			if( !empty($args['owner']) && is_numeric($args['owner'])){
    				$conditions['owner'] = "coupon_owner=".$args['owner'];
	    			//when an owner is set, event-wide and sitewide must be explicitly set to filter in/out only these types of coupons
	    			if( $args['eventwide'] === 1 || $args['eventwide'] === true ){
						//we explicitly want to check eventwide coupons, not along with owners because by default it'd include eventwide coupons in simple owner searches
						$conditions['owner'] = '('.$conditions['owner']." AND coupon_eventwide=1)";
	    			}elseif( !$args['eventwide'] ){
						//only need to include eventwide searches if 0, since event-wide searches would also appear if owner is set to 1
						$conditions['eventwide'] = "coupon_eventwide=0";
					}
	    			if( $args['sitewide'] === 1 || $args['sitewide'] === true ){
						//include sitewide coupons
						if( $args['eventwide'] === 1 || $args['eventwide'] === true ){
							//we'll never do an AND search for site-wide/event-wide because it would just negate all coupons that are one or the other
	    					$conditions['owner'] .= " OR coupon_sitewide=1";
	    				}else{
							$conditions['sitewide'] = "coupon_sitewide=1";
						}
	    			}elseif( !$args['sitewide'] ) {
						//exclude sitewide coupons
						$conditions['sitewide'] = "coupon_sitewide=0";
					}
    			}else{
	    			//no owner, so we're looking for either event/site wide coupons
	    			if( $args['eventwide'] === 1 || $args['eventwide'] === true ){
						$conditions['eventwide'] = "coupon_eventwide=1";
	    			}elseif( !$args['eventwide'] ){
						//only need to include eventwide searches if 0, since event-wide searches would also appear if owner is set to 1
						$conditions['eventwide'] = "coupon_eventwide=0";
					}
	    			if( $args['sitewide'] === 1 || $args['sitewide'] === true ){
						//explicitly filter sitewide coupons
	    				if( $args['eventwide'] === 1 || $args['eventwide'] === true ){
							//we'll never do an AND search for site-wide/event-wide because it would just negate all coupons that are one or the other
	    					$conditions['eventwide'] .= " OR coupon_sitewide=1";
	    				}else{ 
	    					$conditions['sitewide'] = "coupon_sitewide=1";
	    				}
	    			}elseif( !$args['sitewide'] ){
						//must not be a sitewide coupon
						$conditions['sitewide'] = "coupon_sitewide=0";
					}
				}
			}else{
				//in Multiple Bookings Mode, coupon ownership is currently ignored for most purposes, but in admin area, we'll restrict just coupons
				if( is_admin() && !empty($_GET['page']) && $_GET['page'] == 'events-manager-coupons' && !empty($args['owner']) && is_numeric($args['owner']) && current_user_can('manage_others_bookings') ){
					$conditions['owner'] = "coupon_owner=".$args['owner'];
				}
			}
		}
		return apply_filters( 'em_coupons_build_sql_conditions', $conditions, $args );
	}
	
	/* 
	 * Adds custom Events search defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	public static function get_default_search($array_or_defaults=array(), $array = array()){
		$defaults = array(
			//site/event-wide lookups - a little special compared to other object condition functions on EM
			'sitewide' => 'enabled', //can be set to true (1) or false (0) whether to exclusively search for this or not
			'eventwide' => 'enabled', //can be set to true (1) or false (0) whether to exclusively search for this or not
            'code' => false,
			'ids'=>false
		); //also accepts event, blog, array
		//sort out whether defaults were supplied or just the array of search values
		if( empty($array) ){
			$array = $array_or_defaults;
		}else{
			$defaults = array_merge($defaults, $array_or_defaults);
		}
		return apply_filters('em_events_get_default_search', parent::get_default_search($defaults,$array), $array, $defaults);
	}
}
EM_Coupons::init();