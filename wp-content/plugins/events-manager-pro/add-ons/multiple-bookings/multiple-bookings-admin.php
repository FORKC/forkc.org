<?php
class EM_Multiple_Bookings_Admin {

    public static function init(){
        add_action('em_options_page_footer_bookings', 'EM_Multiple_Bookings_Admin::settings');
        if( !get_option('dbem_multiple_bookings') ) return false;
        add_action( 'admin_notices', 'EM_Multiple_Bookings_Admin::page_warning', 100 );
		add_action( 'em_options_page_footer_emails', 'EM_Multiple_Bookings_Admin::emails', 1);
		add_action('em_options_page_booking_email_templates_options_top', 'EM_Multiple_Bookings_Admin::single_booking_emails_tip');
    }
    
    public static function page_warning(){
        //Warn about EM page edit
        if ( preg_match( '/(post|page).php/', $_SERVER ['SCRIPT_NAME']) && isset ( $_GET ['action'] ) && $_GET ['action'] == 'edit' && isset($_GET['post']) ){
            $cart_page_id = get_option ( 'dbem_multiple_bookings_cart_page' );
            $checkout_page_id = get_option( 'dbem_multiple_bookings_checkout_page' );
            if( in_array($_GET['post'], array($cart_page_id, $checkout_page_id)) ){
                $page_name = $_GET['post'] == $cart_page_id ? __('Cart','em-pro'):__('Checkout','em-pro');
	        	$message = sprintf ( __ ( "This page corresponds to the <strong>Events Manager</strong> %s page. Its content will be overriden by Events Manager, although if you include the word CONTENTS (exactly in capitals) and surround it with other text, only CONTENTS will be overwritten.", 'em-pro' ), $page_name );
	        	$notice = "<div class='error'><p>$message</p></div>";
	        	echo $notice;
            }
        }
    }
    
    public static function single_booking_emails_tip(){
        ?>
        <p><em><?php _e('Since you are in Multiple Booking Mode, these emails will only be sent in these specific circumstances :', 'em-pro'); ?></em></p>
        <ol>
            <li><em><?php echo sprintf(__('You have enabled the option to email event owners in the %s section below.','em-pro'), '<code>'.__('Multiple Booking Email Templates','em-pro').'</code>'); ?></em></li>
            <li><em><?php _e('When modifications are made to individual bookings which would normally trigger an email to be sent to the attendee.','em-pro'); ?></em></li>
        </ol>
        <?php
    }
    
    /**
     * Settings for Multiple Bookings Mode 
     */
    public static function settings(){
        global $save_button;
        ?>
        <div  class="postbox " id="em-opt-multiple-bookings" >
        	<div class="handlediv" title="<?php esc_attr_e_emp('Click to toggle', 'events-manager'); ?>"><br /></div><h3><span><?php _e('Multiple Bookings Mode','em-pro'); ?> <em>(Beta)</em></span></h3>
        	<div class="inside">
        		<table class='form-table'>
        			<tr class="em-boxheader"><td colspan='2'>
        				<p>
        					<?php _e('Multiple Bookings Mode enables your visitors to make bookings follow a flow similar to that of a shopping cart, meaning users can book multiple events and pay for them in one go.','em-pro'); ?>
        					<a href="http://wp-events-plugin.com/documentation/multiple-booking-mode/"><?php echo sprintf(__('More about %s.','em-pro'), __('Multiple Bookings Mode','em-pro')); ?></a>
        				</p>
        			</td></tr>
        			<?php
        			em_options_radio_binary ( __( 'Enable Multiple Bookings Mode?', 'em-pro' ), 'dbem_multiple_bookings' );
        			?>
        			<tbody id="dbem-js-multiple-bookings">
        				<tr>
							<th><?php echo __( 'Checkout Page', 'em-pro'); ?></th>
							<td>
								<?php wp_dropdown_pages(array('name'=>'dbem_multiple_bookings_checkout_page', 'selected'=>get_option('dbem_multiple_bookings_checkout_page'), 'show_option_none'=>'['.esc_html__emp('None', 'events-manager').']' )); ?>
								<br />
								<em>
									<?php
									echo __('This page will be where the user reviews their bookings, enters additional information (such as user registration info) and proceeds with payment.','em-pro');
									echo ' '. sprintf(__( 'Please <a href="%s">add a new page</a> and assign it here. This is required for Multiple Bookings Mode to work.','em-pro'), 'post-new.php?post_type=page'); 
									?>
								</em>
							</td>
						</tr>
	        			<?php
	        				em_options_radio_binary( __('Redirect To Checkout on Booking?','em-pro'), 'dbem_multiple_bookings_redirect', __('It set to yes, when a booking has been added to the cart, the user will be redirected to the checkout page. Whilst redirecting the confirmation message below will also be shown, you may also want to modify that too let them know they are being redirected.','em-pro'));
	        				em_options_select( __('Checkout Page Booking Form','em-pro'), 'dbem_multiple_bookings_form', EM_Booking_Form::get_forms_names(), __('This form will be shown on the checkout page, which should include user fields you may want when registering new users. Any non-user fields will be added as supplementary information to every booking, if you have identical Field IDs on the individual event booking form, that field value will be saved to the individual booking instead.','em-pro'));
	        			?>
        				<tr>
							<th><?php echo __( 'Cart Page', 'em-pro'); ?></th>
							<td>
								<?php wp_dropdown_pages(array('name'=>'dbem_multiple_bookings_cart_page', 'selected'=>get_option('dbem_multiple_bookings_cart_page'), 'show_option_none'=>'['.esc_html__emp('None', 'events-manager').']' )); ?>
								<br />
								<em><?php 
									echo __('This page will display the events the user has chosen to book and allow them to edit their bookings before checkout.','em-pro');
									echo ' '.sprintf(__( 'Please <a href="%s">add a new page</a> and assign it here. This is required for Multiple Bookings Mode to work.','em-pro'), 'post-new.php?post_type=page'); ?>
								</em>
							</td>
						</tr>
	        			<?php
	        				em_options_input_text( __('Successfully Added Message','em-pro'), 'dbem_multiple_bookings_feedback_added', __('A booking was successfull added to the bookings cart.','em-pro'));
	        				em_options_input_text( __('Loading Cart Contents','em-pro'), 'dbem_multiple_bookings_feedback_loading_cart', __('If caching plugins are used, cart contents are loaded after a page load and this text is shown whilst loading.','em-pro'));
	        				em_options_input_text( __('Event Already Booked','em-pro'), 'dbem_multiple_bookings_feedback_already_added', __('This event has already been added to the cart and cannot be added twice.','em-pro'));
	        				em_options_input_text( __('No Bookings','em-pro'), 'dbem_multiple_bookings_feedback_no_bookings', __('User has not booked any events yet, cart is empty.','em-pro'));
	        				em_options_input_text( __('Empty Cart Warning','em-pro'), 'dbem_multiple_bookings_feedback_empty_cart', __('Warning after the "empty cart" button is clicked.','em-pro'));
	        				em_options_input_text( __('Checkout Form Button','em-pro'), 'dbem_multiple_bookings_submit_button', __('The text shown in the checkout page form.','em-pro'));
	        			?>
        			</tbody>
        			<?php echo $save_button; ?>
        		</table>
        	</div> <!-- . inside -->
        </div> <!-- .postbox -->
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('input:radio[name="dbem_multiple_bookings"]').change(function(){
					if( $('input:radio[name="dbem_multiple_bookings"]:checked').val() == 1 ){
						$('tbody#dbem-js-multiple-bookings').show();
					}else{
						$('tbody#dbem-js-multiple-bookings').hide();					
					}
				}).first().trigger('change');
			});
		</script>
		<?php
    }
    
	public static function emails(){
	    global $save_button;
		$bookings_placeholders = '<a href="'.EM_ADMIN_URL .'&amp;page=events-manager-help#booking-placeholders">'. esc_html__emp('Booking Related Placeholders','events-manager') .'</a>';
		$bookings_placeholder_tip = " ". sprintf(esc_html__emp('This accepts %s placeholders.','events-manager'), $bookings_placeholders);
		?>
		<div  class="postbox " id="em-opt-multiple-booking-emails" >
		<div class="handlediv" title="<?php esc_attr_e_emp('Click to toggle', 'events-manager'); ?>"><br /></div><h3><span><?php _e ( 'Multiple Booking Email Templates', 'em-pro' ); ?> </span></h3>
		<div class="inside">
		    <div class="em-boxheader">
                <p><?php echo sprintf(__( 'When users make a booking in Multiple Bookings Mode or cancels their booking, these emails get sent to the attendee and administrator emails you assign in the %s section above.', 'em-pro' ), '<code>'.esc_html__emp( 'Booking Email Templates', 'events-manager').'</code>'); ?></p>
                <p><?php _e('When administrators modify a set of multiple bookings rather than individual events, these templates will be used to notify the attendee.','em-pro'); ?></p>
            </div>
			<table class='form-table'>
				<?php
				$email_subject_tip = esc_html__emp('You can disable this email by leaving the subject blank.','events-manager');
				em_options_radio_binary ( __( 'Email event owners?', 'em-pro' ), 'dbem_multiple_bookings_contact_email', sprintf(__( 'If enabled, additional emails will be sent to administrators and event owners for EVERY event booked based on the above %s settings.', 'em-pro' ), '<code>'.esc_html__emp( 'Booking Email Templates', 'events-manager').'</code>') );
				?>
				<tr class="em-header"><td colspan='2'><h4><?php esc_html_e_emp('Event Admin/Owner Emails', 'events-manager'); ?></h4></td></tr>
				<tbody class="em-subsection">
    				<tr class="em-subheader"><td colspan='2'>
    					<h5><?php esc_html_e_emp('Confirmed booking email'); ?></h5>
    					<em><?php esc_html_e_emp('This is sent when a person\'s booking is confirmed. This will be sent automatically if approvals are required and the booking is approved. If approvals are disabled, this is sent out when a user first submits their booking.').$bookings_placeholder_tip ?></em>
    				</td></tr>
    				<?php
    				em_options_input_text ( esc_html__emp('Confirmed booking email'), 'dbem_multiple_bookings_contact_email_confirmed_subject', $email_subject_tip );
    				em_options_textarea ( esc_html__emp('Booking confirmed email'), 'dbem_multiple_bookings_contact_email_confirmed_body', '' );
    				?>
    				<tr class="em-subheader"><td colspan='2'>
    					<h5><?php esc_html_e_emp('Pending booking email').$email_subject_tip ?></h5>
    					<em><?php esc_html_e_emp('This is sent when a person\'s booking is pending. If approvals are enabled, this is sent out when a user first submits their booking.').$bookings_placeholder_tip ?></em>
    				</td></tr>
    				<?php
    				em_options_input_text ( esc_html__emp( 'Booking pending email subject'), 'dbem_multiple_bookings_contact_email_pending_subject', $email_subject_tip );
    				em_options_textarea ( esc_html__emp( 'Booking pending email'), 'dbem_multiple_bookings_contact_email_pending_body', '' );
    				?>
    				<tr class="em-subheader"><td colspan='2'>
    					<h5><?php esc_html_e_emp('Booking cancelled','events-manager') ?></h5>
    					<em><?php esc_html_e_emp('An email will be sent to the event contact if someone cancels their booking.').$bookings_placeholder_tip ?></em>
    				</td></tr>
    				<?php
    				em_options_input_text ( esc_html__emp('Booking cancelled email subject'), 'dbem_multiple_bookings_contact_email_cancelled_subject', $email_subject_tip );
    				em_options_textarea ( esc_html__emp('Booking cancelled email'), 'dbem_multiple_bookings_contact_email_cancelled_body', '' );
    				?>
				</tbody>
				<tbody class="em-subsection">
    				<tr class="em-header"><td colspan='2'><h4><?php esc_html_e_emp('Booked User Emails', 'events-manager'); ?></h4></td></tr>
    				<tr class="em-subheader"><td colspan='2'>
    					<h5><?php esc_html_e_emp('Confirmed booking email','events-manager') ?></h5>
    					<em><?php esc_html_e_emp('This is sent when a person\'s booking is confirmed. This will be sent automatically if approvals are required and the booking is approved. If approvals are disabled, this is sent out when a user first submits their booking.','events-manager').$bookings_placeholder_tip ?></em>
    				</td></tr>
    				<?php
    				em_options_input_text ( esc_html__emp( 'Booking confirmed email subject', 'events-manager'), 'dbem_multiple_bookings_email_confirmed_subject', $email_subject_tip );
    				em_options_textarea ( esc_html__emp( 'Booking confirmed email', 'events-manager'), 'dbem_multiple_bookings_email_confirmed_body', '' );
    				?>
    				<tr class="em-subheader"><td colspan='2'>
    					<h5><?php esc_html_e_emp('Pending booking email','events-manager') ?></h5>
    					<em><?php esc_html_e_emp( 'This will be sent to the person when they first submit their booking. Not relevant if bookings don\'t require approval.', 'events-manager').$bookings_placeholder_tip ?></em>
    				</td></tr>
    				<?php
    				em_options_input_text ( esc_html__emp( 'Booking pending email subject', 'events-manager'), 'dbem_multiple_bookings_email_pending_subject', $email_subject_tip);
    				em_options_textarea ( esc_html__emp( 'Booking pending email', 'events-manager'), 'dbem_multiple_bookings_email_pending_body','') ;
    				?>
    				<tr class="em-subheader"><td colspan='2'>
    					<h5><?php esc_html_e_emp('Rejected booking email','events-manager') ?></h5>
    					<em><?php esc_html_e_emp( 'This will be sent automatically when a booking is rejected. Not relevant if bookings don\'t require approval.', 'events-manager').$bookings_placeholder_tip ?></em>
    				</td></tr>
    				<?php
    				em_options_input_text ( esc_html__emp( 'Booking rejected email subject', 'events-manager'), 'dbem_multiple_bookings_email_rejected_subject', $email_subject_tip );
    				em_options_textarea ( esc_html__emp( 'Booking rejected email', 'events-manager'), 'dbem_multiple_bookings_email_rejected_body', '' );
    				?>
    				<tr class="em-subheader"><td colspan='2'>
    					<h5><?php esc_html_e_emp('Booking cancelled','events-manager') ?></h5>
    					<em><?php esc_html_e_emp('This will be sent when a user cancels their booking.','events-manager').$bookings_placeholder_tip ?></em>
    				</td></tr>
    				<?php
    				em_options_input_text ( esc_html__emp( 'Booking cancelled email subject', 'events-manager'), 'dbem_multiple_bookings_email_cancelled_subject', $email_subject_tip );
    				em_options_textarea ( esc_html__emp( 'Booking cancelled email', 'events-manager'), 'dbem_multiple_bookings_email_cancelled_body', '' );
    				?>
    			</tbody>
				<?php echo $save_button; ?>
			</table>
		</div> <!-- . inside -->
		</div> <!-- .postbox -->
		<?php
	}
}
EM_Multiple_Bookings_Admin::init();