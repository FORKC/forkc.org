<?php
class EM_Multiple_Bookings{
    
    /**
     * Multiple Booking object instance for current user session
     * @var EM_Multiple_Booking
     */
    public static $booking_data;
    public static $session_started = false;
    
    public static function init(){
		include('multiple-booking.php');
		include('multiple-bookings-widget.php');
		add_action('init', 'EM_Multiple_Bookings::wp_init');
		add_filter('em_booking_save','EM_Multiple_Bookings::em_booking_save',100,2); //when saving bookings, we need to make sure MB objects update the total price
		add_filter('em_get_booking','EM_Multiple_Bookings::em_get_booking'); //switch EM_Booking with EM_Multiple_Booking object if applicable
		add_filter('em_wp_localize_script', 'EM_Multiple_Bookings::em_wp_localize_script');
		add_filter('em_scripts_and_styles_public_enqueue_pages', 'EM_Multiple_Bookings::pages_enqueue');
		//cart/checkout pages
		add_filter('the_content', 'EM_Multiple_Bookings::pages');
		//ajax calls for cart actions
		add_action('wp_ajax_emp_checkout_remove_item','EM_Multiple_Bookings::remove_booking');
		add_action('wp_ajax_nopriv_emp_checkout_remove_item','EM_Multiple_Bookings::remove_booking');
		add_action('wp_ajax_emp_empty_cart','EM_Multiple_Bookings::empty_cart_ajax');
		add_action('wp_ajax_nopriv_emp_empty_cart','EM_Multiple_Bookings::empty_cart_ajax');
		//ajax calls for cart checkout
		add_action('wp_ajax_emp_checkout','EM_Multiple_Bookings::checkout');
		add_action('wp_ajax_nopriv_emp_checkout','EM_Multiple_Bookings::checkout');
		//ajax calls for cart contents
		add_action('wp_ajax_em_cart_page_contents','EM_Multiple_Bookings::cart_page_contents_ajax');
		add_action('wp_ajax_nopriv_em_cart_page_contents','EM_Multiple_Bookings::cart_page_contents_ajax');
		add_action('wp_ajax_em_checkout_page_contents','EM_Multiple_Bookings::checkout_page_contents_ajax');
		add_action('wp_ajax_nopriv_em_checkout_page_contents','EM_Multiple_Bookings::checkout_page_contents_ajax');
		add_action('wp_ajax_em_cart_contents','EM_Multiple_Bookings::cart_contents_ajax');
		add_action('wp_ajax_nopriv_em_cart_contents','EM_Multiple_Bookings::cart_contents_ajax');
		//cart content widget and shortcode
		add_action('wp_ajax_em_cart_widget_contents','EM_Multiple_Bookings::cart_widget_contents_ajax');
		add_action('wp_ajax_nopriv_em_cart_widget_contents','EM_Multiple_Bookings::cart_widget_contents_ajax');
		add_shortcode('em_cart_contents', 'EM_Multiple_Bookings::cart_contents');
		add_action('em_booking_js_footer', 'EM_Multiple_Bookings::em_booking_js_footer');
		//booking admin pages
		add_action('em_bookings_admin_page', 'EM_Multiple_Bookings::bookings_admin_notices'); //add MB warnings if booking is part of a bigger booking
		add_action('em_bookings_multiple_booking', 'EM_Multiple_Bookings::booking_admin',1,1); //handle page for showing a single multiple booking
			//no user booking mode
			add_filter('em_booking_get_person_editor', 'EM_Multiple_Bookings::em_booking_get_person_editor', 100, 2); 
			if( !empty($_REQUEST['emp_no_user_mb_global_change']) && !empty($_REQUEST['action']) && $_REQUEST['action'] == 'booking_modify_person'){ //only hook in if we're editing a no-user booking
				add_filter('em_booking_get_person_post', 'EM_Multiple_Bookings::em_booking_get_person_post', 100, 2);
			}
		//booking table and csv filters
		add_filter('em_bookings_table_rows_col', array('EM_Multiple_Bookings','em_bookings_table_rows_col'),10,5);
		add_filter('em_bookings_table_cols_template', array('EM_Multiple_Bookings','em_bookings_table_cols_template'),10,2);
		add_action('shutdown', 'EM_Multiple_Bookings::session_save');
		//multilingual hook
		add_action('em_ml_init', 'EM_Multiple_Bookings::em_ml_init');
    }
    
    public static function wp_init(){
    	if( (empty($_REQUEST['action']) || $_REQUEST['action'] != 'manual_booking') && !(!empty($_REQUEST['manual_booking']) && wp_verify_nonce($_REQUEST['manual_booking'], 'em_manual_booking_'.$_REQUEST['event_id'])) ){ //not admin area or a manual booking
    		//modify traditional booking forms behaviour
    		add_action('em_booking_form_custom','EM_Multiple_Bookings::prevent_user_fields', 1); //prevent user fields from showing
    		add_filter('em_booking_validate', 'EM_Multiple_Bookings::prevent_user_validation', 1); //prevent user fields validation
    		//hooking into the booking process
    		add_action('em_booking_add','EM_Multiple_Bookings::em_booking_add', 5, 3); //prevent booking being made and add to cart
    	}
    	//if we're manually booking, don't load the cart JS stuff
    	if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'manual_booking' ){
    		define('EM_CART_JS_LOADED',true);
    	}
    }
    
    public static function em_ml_init(){ include('multiple-bookings-ml.php'); }
    
    public static function em_get_booking($EM_Booking){
        if( !empty($EM_Booking->booking_id) && $EM_Booking->event_id == 0 ){
            return new EM_Multiple_Booking($EM_Booking);
        }
        return $EM_Booking;
    }
    
    public static function em_wp_localize_script( $vars ){
	    $vars['mb_empty_cart'] = get_option('dbem_multiple_bookings_feedback_empty_cart');
	    return $vars;
    }
    
    /**
     * Starts a session, and returns whether session successfully started or not.
     * We can start a session after the headers are sent in this case, it's ok if a session was started earlier, since we're only grabbing server-side data
     */
    public static function session_start(){
        global $EM_Notices;
        if( !self::$session_started ){
            self::$session_started = !session_id() ? @session_start() : true;
        }
        return self::$session_started;
    }
    
    /**
     * Grabs multiple booking from session, or creates a new multiple booking object
     * @return EM_Multiple_Booking
     */
    public static function get_multiple_booking(){
        if( empty(self::$booking_data) ){
	        self::session_start();
	        //load and unserialize EM_Multiple_Booking from session
	        if( !empty($_SESSION['em_multiple_bookings']) && is_serialized($_SESSION['em_multiple_bookings']) ){
	            $obj = unserialize( $_SESSION['em_multiple_bookings'] );
				if ( get_class( $obj ) == 'EM_Multiple_Booking' ) {
					self::$booking_data = $obj;
				}
	        }
	        //create new EM_Multiple_Booking object if one wasn't created
            if ( !is_object(self::$booking_data) || get_class( self::$booking_data ) != 'EM_Multiple_Booking' ){
    			self::$booking_data = new EM_Multiple_Booking();
    		}
        }
        return self::$booking_data; 
    }
    
    public static function session_save(){
        if( !empty(self::$booking_data) ){
			//clean booking data to remove unecessary bloat
			foreach( self::$booking_data->bookings as $EM_Booking ){
				//don't try removing the booking objects, because there's no booking ID yet, but anything with an ID already can go
				$EM_Booking->tickets = null;
				$EM_Booking->event = null;
				foreach($EM_Booking->tickets_bookings->tickets_bookings as $key => $EM_Ticket_Booking){
					$EM_Booking->tickets_bookings->tickets_bookings[$key]->event = null;
					$EM_Booking->tickets_bookings->tickets_bookings[$key]->ticket = null;
				}
			}
			$_SESSION['em_multiple_bookings'] = serialize(self::$booking_data);
        }
    }
    
    public static function prevent_user_fields(){
		add_filter('emp_form_show_reg_fields', create_function('','return false;'));
    }
    
    public static function prevent_user_validation($result){
        self::prevent_user_fields();
        return $result;
    }
    
    /**
     * Hooks into em_booking_add ajax action early and prevents booking from being saved to the database, instead it adds the booking to the bookings cart.
     * If this is not an AJAX request (due to JS issues) then a redirect is made after processing the booking.
     * @param EM_Event $EM_Event
     * @param EM_Booking $EM_Booking
     * @param boolean $post_validation
     */
    public static function em_booking_add( $EM_Event, $EM_Booking, $post_validation ){
        global $EM_Notices;
        $feedback = '';
        $result = false;
        if( self::session_start() ){
	        if ( $post_validation ) {
	            //booking can be added to cart
	            if( self::get_multiple_booking()->add_booking($EM_Booking) ){
	                $result = true;
		            $feedback = get_option('dbem_multiple_bookings_feedback_added');
		            $EM_Notices->add_confirm( $feedback, !defined('DOING_AJAX') ); //if not ajax, make this notice static for redirect
	            }else{
	                $result = false;
	                $feedback = '';
	                $EM_Notices->add_error( $EM_Booking->get_errors(), !defined('DOING_AJAX') ); //if not ajax, make this notice static for redirect
	            }
	        }else{
				$result = false;
				$EM_Notices->add_error( $EM_Booking->get_errors() );
			}
        }else{
			$EM_Notices->add_error(__('Sorry for the inconvenience, but we are having technical issues adding your bookings, please contact an administrator about this issue.','em-pro'), !defined('DOING_AJAX'));
        }
		ob_clean(); //em_booking_add uses ob_start(), so flush it here
		if( defined('DOING_AJAX') ){
			$return = array('result'=>$result, 'message'=>$feedback, 'errors'=> $EM_Notices->get_errors());
			if( $result && get_option('dbem_multiple_bookings_redirect') ){
			    $return['redirect'] = get_permalink(get_option('dbem_multiple_bookings_checkout_page'));
	        }
            echo EM_Object::json_encode(apply_filters('em_action_'.$_REQUEST['action'], $return, $EM_Booking));
		}else{
			wp_redirect(em_wp_get_referer());
		}
	    die();
    }
    
    /**
     * @param boolean $result
     * @param EM_Booking $EM_Booking
     */
    public static function em_booking_save($result, $EM_Booking){
        //only do this to a previously saved EM_Booking object, not newly added
    	if( $result && get_class($EM_Booking) == 'EM_Booking' && $EM_Booking->previous_status !== false ){
            $EM_Multiple_Booking = self::get_main_booking( $EM_Booking );
            //if part of multiple booking, recalculate and save mb object too
            if( $EM_Multiple_Booking !== false ){
                $EM_Multiple_Booking->calculate_price();
                $EM_Multiple_Booking->save(false);
            }
        }
        return $result;
    }
    
    public static function remove_booking(){
        $EM_Multiple_Booking = self::get_multiple_booking();
        if( !empty($_REQUEST['event_id']) && $EM_Multiple_Booking->remove_booking($_REQUEST['event_id']) ){
		    if( count($EM_Multiple_Booking->bookings) == 0 ) self::empty_cart(); 
		    $feedback = '';
		    $result = true;
		}else{
		    $feedback = __('Could not remove booking due to an unexpected error.', 'em-pro');
		    $result = false;
		}
        if( defined('DOING_AJAX') ){
        	$return = array('result'=>$result, 'message'=>$feedback);
        	header('Content-Type: text/javascript; charset=utf-8'); //to prevent MIME type errors in MultiSite environments
        	echo EM_Object::json_encode(apply_filters('em_action_'.$_REQUEST['action'], $return, $EM_Multiple_Booking));
        }else{
        	wp_redirect(em_wp_get_referer());
        }
        die();
    }
    
    public static function empty_cart(){
	    self::session_start();
        unset($_SESSION['em_multiple_bookings']);
        self::$booking_data = null;
    }
    
    public static function empty_cart_ajax(){
	    self::empty_cart();
        header( 'Content-Type: application/javascript; charset=UTF-8', true ); //add this for HTTP -> HTTPS requests which assume it's a cross-site request
	    echo EM_Object::json_encode(array('success'=>true));
	    die();
    }
    
    public static function checkout(){
        global $EM_Notices, $EM_Booking;
		check_ajax_referer('emp_checkout');
		$EM_Booking = $EM_Multiple_Booking = self::get_multiple_booking();
        //remove filters so that our master booking validates user fields
		remove_action('em_booking_form_custom','EM_Multiple_Bookings::prevent_user_fields', 1); //prevent user fields from showing
		remove_filter('em_booking_validate', 'EM_Multiple_Bookings::prevent_user_validation', 1); //prevent user fields validation
		//now validate the master booking
        $EM_Multiple_Booking->get_post();
        $post_validation = $EM_Multiple_Booking->validate();
		//re-add filters to prevent individual booking problems
		add_action('em_booking_form_custom','EM_Multiple_Bookings::prevent_user_fields', 1); //prevent user fields from showing
		add_filter('em_booking_validate', 'EM_Multiple_Bookings::prevent_user_validation', 1); //prevent user fields validation
		$bookings_validation = $EM_Multiple_Booking->validate_bookings();
		//fire the equivalent of the em_booking_add action, but multiple variation 
		do_action('em_multiple_booking_add', $EM_Multiple_Booking->get_event(), $EM_Multiple_Booking, $post_validation && $bookings_validation); //get_event returns blank, just for backwards-compatabaility
		//proceed with saving bookings if all is well
		$result = false; $feedback = '';
        if( $bookings_validation && $post_validation ){
			//save user registration
       	    $registration = em_booking_add_registration($EM_Multiple_Booking);

        	//save master booking, which in turn saves the other bookings too
        	if( $registration && $EM_Multiple_Booking->save_bookings() ){
        	    $result = true;
        		$EM_Notices->add_confirm( $EM_Multiple_Booking->feedback_message );
        		$feedback = $EM_Multiple_Booking->feedback_message;
        		self::empty_cart(); //we're done with this checkout!
        	}else{
        		$EM_Notices->add_error( $EM_Multiple_Booking->get_errors() );
        		$feedback = $EM_Multiple_Booking->feedback_message;
        	}
        	global $em_temp_user_data; $em_temp_user_data = false; //delete registered user temp info (if exists)
        }else{
            $EM_Notices->add_error( $EM_Multiple_Booking->get_errors() );
        }
		if( defined('DOING_AJAX') ){
        	header( 'Content-Type: application/javascript; charset=UTF-8', true ); //add this for HTTP -> HTTPS requests which assume it's a cross-site request
		    if( $result ){
				$return = array('result'=>true, 'message'=>$feedback, 'checkout'=>true);
				echo EM_Object::json_encode(apply_filters('em_action_'.$_REQUEST['action'], $return, $EM_Multiple_Booking));
			}elseif( !$result ){
				$return = array('result'=>false, 'message'=>$feedback, 'errors'=>$EM_Notices->get_errors(), 'checkout'=>true);
				echo EM_Object::json_encode(apply_filters('em_action_'.$_REQUEST['action'], $return, $EM_Multiple_Booking));
			}
			die();
		}
    }
    
    /**
     * Hooks into the_content and checks if this is a checkout or cart page, and if so overwrites the page content with the relevant content. Uses same concept as em_content.
     * @param string $page_content
     * @return string
     */
    public static function pages($page_content) {
    	global $post, $wpdb, $wp_query, $EM_Event, $EM_Location, $EM_Category;
    	if( empty($post) ) return $page_content; //fix for any other plugins calling the_content outside the loop
    	$cart_page_id = get_option ( 'dbem_multiple_bookings_cart_page' );
    	$checkout_page_id = get_option( 'dbem_multiple_bookings_checkout_page' );
    	if( in_array($post->ID, array($cart_page_id, $checkout_page_id)) ){
    		ob_start();
    		if( $post->ID == $checkout_page_id && $checkout_page_id != 0 ){
    			self::checkout_page();
    		}elseif( $post->ID == $cart_page_id && $cart_page_id != 0 ){
    			self::cart_page();
    		}
    		$content = ob_get_clean();
    		//Now, we either replace CONTENTS or just replace the whole page
    		if( preg_match('/CONTENTS/', $page_content) ){
    			$content = str_replace('CONTENTS',$content,$page_content);
    		}
    		return $content;
    	}
    	return $page_content;
    }
    
    public static function pages_enqueue( $pages ){
    	$pages['checkout'] = get_option( 'dbem_multiple_bookings_checkout_page' );
    	$pages['cart'] = get_option ( 'dbem_multiple_bookings_cart_page' );
    	return $pages;
    }
    
    public static function cart_contents_ajax(){
    	emp_locate_template('multiple-bookings/cart-table.php', true);
    	die();
    }
    
    /* Checkout Page Code */
    
    public static function em_booking_js_footer(){
        if( !defined('EM_CART_JS_LOADED') ){
	        include('multiple-bookings.js');
			do_action('em_cart_js_footer');
			define('EM_CART_JS_LOADED',true);
        }
    }
	
	public static function checkout_page_contents_ajax(){
		emp_locate_template('multiple-bookings/page-checkout.php',true);
		die();
	}

	public static function checkout_page(){
	    if( !self::get_multiple_booking()->validate_bookings_spaces() ){
	        global $EM_Notices;
	        $EM_Notices->add_error(self::get_multiple_booking()->get_errors());
	    }
		//load contents if not using caching, do not alter this conditional structure as it allows the cart to work with caching plugins
		echo '<div class="em-checkout-page-contents" style="position:relative;">';
		if( !defined('WP_CACHE') || !WP_CACHE ){
			emp_locate_template('multiple-bookings/page-checkout.php',true);
		}else{
			echo '<p>'.get_option('dbem_multiple_bookings_feedback_loading_cart').'</p>';
		}
		echo '</div>';
		EM_Bookings::enqueue_js();
    }
    
    /* Shopping Cart Page */
	
	public static function cart_page_contents_ajax(){
		emp_locate_template('multiple-bookings/page-cart.php',true);
		die();
	}
        
    public static function cart_page(){
		if( !EM_Multiple_Bookings::get_multiple_booking()->validate_bookings_spaces() ){
			global $EM_Notices;
			$EM_Notices->add_error(EM_Multiple_Bookings::get_multiple_booking()->get_errors());
		}
		//load contents if not using caching, do not alter this conditional structure as it allows the cart to work with caching plugins
		echo '<div class="em-cart-page-contents" style="position:relative;">';
		if( !defined('WP_CACHE') || !WP_CACHE ){
			emp_locate_template('multiple-bookings/page-cart.php',true);
		}else{
			echo '<p>'.get_option('dbem_multiple_bookings_feedback_loading_cart').'</p>';
		}
		echo '</div>';
		if( !defined('EM_CART_JS_LOADED') ){
			//load 
			function em_cart_js_footer(){
				?>
				<script type="text/javascript">
					<?php include('multiple-bookings.js'); ?>
					<?php do_action('em_cart_js_footer'); ?>
				</script>
				<?php
			}
			add_action('wp_footer','em_cart_js_footer', 100);
			add_action('admin_footer','em_cart_js_footer', 100);
			define('EM_CART_JS_LOADED',true);
		}
	}
    
    /* Shopping Cart Widget */
    
    public static function cart_widget_contents_ajax(){
        emp_locate_template('multiple-bookings/widget.php', true, array('instance'=>$_REQUEST));
        die();
    }
    
    public static function cart_contents( $instance ){
		$defaults = array(
				'title' => __('Event Bookings Cart','em-pro'),
				'format' => '#_EVENTLINK - #_EVENTDATES<ul><li>#_BOOKINGSPACES Spaces - #_BOOKINGPRICE</li></ul>',
				'loading_text' =>  __('Loading...','em-pro'),
				'checkout_text' => __('Checkout','em-pro'),
				'cart_text' => __('View Cart','em-pro'),
				'no_bookings_text' => __('No events booked yet','em-pro')
		);
		$instance = array_merge($defaults, (array) $instance);
		ob_start();
		?>
		<div class="em-cart-widget">
			<form>
				<input type="hidden" name="action" value="em_cart_widget_contents" />
				<input type="hidden" name="format" value="<?php echo $instance['format'] ?>" />
				<input type="hidden" name="cart_text" value="<?php echo $instance['cart_text'] ?>" />
				<input type="hidden" name="checkout_text" value="<?php echo $instance['checkout_text'] ?>" />
				<input type="hidden" name="no_bookings_text" value="<?php echo $instance['no_bookings_text'] ?>" />
				<input type="hidden" name="loading_text" value="<?php echo $instance['loading_text'] ?>" />
			</form>
			<div class="em-cart-widget-contents">
				<?php if( !defined('WP_CACHE') || !WP_CACHE ) emp_locate_template('multiple-bookings/widget.php', true, array('instance'=>$instance)); ?>
			</div>
		</div>
		<?php		
		if( !defined('EM_CART_WIDGET_JS_LOADED') ){ //load cart widget JS once per page
			function em_cart_widget_js_footer(){
				?>
				<script type="text/javascript">
					<?php include('cart-widget.js'); ?>
				</script>
				<?php
			}
			add_action('wp_footer','em_cart_widget_js_footer', 1000);
			define('EM_CART_WIDGET_JS_LOADED',true);
		}
		return ob_get_clean();
	}

    /*
     * ----------------------------------------------------------
    * Booking Table and CSV Export
    * ----------------------------------------------------------
    */
    
    public static function em_bookings_table_rows_col($value, $col, $EM_Booking, $EM_Bookings_Table, $csv){
        if( preg_match('/^mb_/', $col) ){
            $col = preg_replace('/^mb_/', '', $col);
	    	if( !empty($EM_Booking) && get_class($EM_Booking) != 'EM_Multiple_Booking' ){
				//is this part of a multiple booking?
				$EM_Multiple_Booking = self::get_main_booking( $EM_Booking );
				if( $EM_Multiple_Booking !== false ){
                	$EM_Form = EM_Booking_Form::get_form(false, get_option('dbem_multiple_bookings_form'));
                	if( array_key_exists($col, $EM_Form->form_fields) ){
                		$field = $EM_Form->form_fields[$col];
                		if( isset($EM_Multiple_Booking->booking_meta['booking'][$col]) ){
                			$value = $EM_Form->get_formatted_value($field, $EM_Multiple_Booking->booking_meta['booking'][$col]);
                		}
                	}
                }
            }
        }
    	return $value;
    }
    
     public static function em_bookings_table_cols_template($template, $EM_Bookings_Table){
    	$EM_Form = EM_Booking_Form::get_form(false, get_option('dbem_multiple_bookings_form'));
    	foreach($EM_Form->form_fields as $field_id => $field ){
            if( $EM_Form->is_normal_field($field) ){ //user fields already handled, htmls shouldn't show
                //prefix MB fields with mb_ to avoid clashes with normal booking forms
        		$template['mb_'.$field_id] = $field['label'];
        	}
    	}
    	return $template;
    }

    /*
     * ----------------------------------------------------------
    * No-User Bookings Admin Stuff
    * ----------------------------------------------------------
    */
    public static  function em_booking_get_person_editor($summary, $EM_Person){
		global $EM_Booking;
		if( !empty($EM_Booking) && current_user_can('manage_others_bookings') ){
			$EM_Multiple_Booking = self::get_main_booking( $EM_Booking );
			if( !empty($EM_Multiple_Booking) ){
				ob_start();
				?>
				<p>
					<em>
						<?php 
						if($EM_Multiple_Booking->booking_id == $EM_Booking->booking_id ){
							esc_html_e('This booking makes part of multiple bookings made at once.','em-pro');
							esc_html_e('Since this is part of multiple booking, you can also change these values for all individual bookings.', 'em-pro');
						}else{
							esc_html_e('This booking contains multiple individual bookings made at once.', 'em-pro');
						} 
						esc_html_e('You can sync this modification with all other related bookings.','em-pro');
						?>
					</em><br />
					<?php _e('Make these changes to all bookings?','em-pro'); ?> </th><td><input type="checkbox" name="emp_no_user_mb_global_change" value="1" checked="checked" />
				</p>
				<?php
				$notice = ob_get_clean();
				$summary = $summary . $notice;
			}
		}
		//if this is an MB booking or part of one, add a note mentioning that all bookings made will get modified
		return $summary;
	}
	
	/**
	 * Saves personal booking information to all bookings if user has permission
	 * @return boolean
	 */
	public static  function em_booking_get_person_post( $result, $EM_Booking ){
		if( $result && current_user_can('manage_others_bookings') ){
			//if this is an MB booking or part of one, edit all the other records too
			$EM_Multiple_Booking = self::get_main_booking( $EM_Booking );
			if( !empty($EM_Multiple_Booking) ){
				//save personal info to main booking if this isn't the main booking
				if( get_class($EM_Booking) != 'EM_Multiple_Booking' ){
					$EM_Multiple_Booking->booking_meta['registration'] = $EM_Booking->booking_meta['registration'];
					$EM_Multiple_Booking->save(false);
				}
				//save other sub-bookings
				$EM_Bookings = $EM_Multiple_Booking->get_bookings();
				foreach($EM_Bookings as $booking){ /* @var $booking EM_Booking */
					if($EM_Booking->booking_id != $booking->booking_id ){
						//save data
						$booking->booking_meta['registration'] = $EM_Booking->booking_meta['registration'];
						$booking->save(false);
					}
				}
				//FIXME - we shouldn't have to overwrite actions this way, need better way for booking admin pages
				$_REQUEST['action'] = 'multiple_booking';
			}
		}
		return $result;
	}

    /*
     * ----------------------------------------------------------
    * Admin Stuff
    * ----------------------------------------------------------
    */
    public static function bookings_admin_notices(){
		global $EM_Booking;
		$EM_Notices = new EM_Notices(false); //not global because we'll get repeated printing of errors here, this is just a notice
		if( current_user_can('manage_others_bookings') ){
	    	if( !empty($EM_Booking) && get_class($EM_Booking) != 'EM_Multiple_Booking' ){
				//is this part of a multiple booking?
				$EM_Multiple_Booking = self::get_main_booking( $EM_Booking );
				if( $EM_Multiple_Booking !== false ){
					$EM_Notices->add_info(sprintf(__('This single booking is part of a larger booking made by this person at once. <a href="%s">View Main Booking</a>.','em-pro'), $EM_Multiple_Booking->get_admin_url()));
					echo $EM_Notices;
				}
			}elseif( !empty($EM_Booking) && get_class($EM_Booking) == 'EM_Multiple_Booking' ){
				$EM_Notices->add_info(__('This booking contains a set of bookings made by this person. To edit particular bookings click on the relevant links below.','em-pro'));
				echo $EM_Notices;
			}
		}
    }
    
    public static function get_main_booking( $EM_Booking ){
		global $wpdb;
		if( get_class($EM_Booking) == 'EM_Multiple_Booking' ) return $EM_Booking; //If already main booking, return that
		if( get_class($EM_Booking) != 'EM_Booking' ) return false; //if this is not an EM_Booking object, just return false
		$main_booking_id = $wpdb->get_var($wpdb->prepare('SELECT booking_main_id FROM '.EM_BOOKINGS_RELATIONSHIPS_TABLE.' WHERE booking_id=%d', $EM_Booking->booking_id));
		if( !empty($main_booking_id) ){
			return new EM_Multiple_Booking($main_booking_id);
		}
		return false;
	}
    
    public static function booking_admin(){
		emp_locate_template('multiple-bookings/admin.php',true);
		if( !defined('EM_CART_JS_LOADED') ){
			//load 
			function em_cart_js_footer(){
				?>
				<script type="text/javascript">
					<?php include('multiple-bookings.js'); ?>
				</script>
				<?php
			}
			add_action('wp_footer','em_cart_js_footer', 20);
			add_action('admin_footer','em_cart_js_footer', 20);
			define('EM_CART_JS_LOADED',true);
		}
	}
}
EM_Multiple_Bookings::init();