<?php
class EM_Forms {
	public static function init(){
		if( is_admin() ){
			add_action('em_create_events_submenu',array('EM_Forms', 'admin_menu'),1,1);
			add_action('em_options_page_footer_bookings',array('EM_Forms', 'admin_options'),10);
			
			//specific admin stuff for the editor
			if( !empty($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-forms-editor' ){
				add_filter('em_wp_localize_script', 'EM_Forms::em_wp_localize_script',10,1);
				add_filter('admin_enqueue_scripts', 'EM_Forms::admin_enqueue_scripts');
				add_action('admin_init', 'EM_Forms::max_ini_vars_fix', 1, 1);
			}
		}
	}
	
	public static function admin_menu($plugin_pages){
		$plugin_pages[] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Forms Editor','em-pro'),__('Forms Editor','em-pro'),get_option('dbem_capability_forms_editor', 'list_users'),'events-manager-forms-editor',array('EM_Forms','admin_page'));
		return $plugin_pages; //use wp action/filters to mess with the menus
	}
	
	public static function admin_page(){
		?>
		<div class='wrap'>
			<h1><?php _e('Forms Editor','em-pro'); ?></h1>
			<p><?php _e('On this page you can create/edit various forms used within Events Manager Pro.', 'em-pro' ); ?></p>
			<?php do_action('emp_forms_admin_page'); ?>
		</div> <!-- wrap -->
		<?php
	}
	
	public static function admin_options(){
		global $save_button;
		if( current_user_can('list_users') ){
		?>
			<div  class="postbox " id="em-opt-pro-booking-form-options" >
			<div class="handlediv" title="<?php esc_attr_e_emp('Click to toggle', 'events-manager'); ?>"><br /></div><h3 class='hndle'><span><?php _e ( 'PRO Booking Form Options', 'em-pro' ); ?> </span></h3>
			<div class="inside">
				<table class='form-table'>
					<?php 
						em_options_input_text ( __( 'Default required field error', 'em-pro' ), 'dbem_emp_booking_form_error_required', __( 'This is the message shown by default when a required booking form field is left empty, %s will be replaced by the field label.','em-pro' ) );
						em_options_radio_binary ( __( 'Show profile fields to logged in users?', 'em-pro' ), 'dbem_emp_booking_form_reg_show', __( 'When logged in, users usually don\'t see their profile fields, with this enabled, users will be able to update their profile fields alongside their booking. This is particularly useful if the user is missing key information.','em-pro' ), '', '#dbem_emp_booking_form_reg_show_name_row, #dbem_emp_booking_form_reg_show_email_row, #dbem_emp_booking_form_reg_input_row' );
						em_options_radio_binary ( __( 'Show name profile fields?', 'em-pro' ), 'dbem_emp_booking_form_reg_show_name', sprintf(__( 'If fields are editable users will be able to change their %s whilst making a booking.', 'em-pro' ), esc_html__emp('Name','events-manager')) );
						em_options_radio_binary ( __( 'Show email profile fields?', 'em-pro' ), 'dbem_emp_booking_form_reg_show_email', sprintf(__( 'If fields are editable users will be able to change their %s whilst making a booking.', 'em-pro' ), esc_html__emp('Email','events-manager')) );
						em_options_radio_binary ( __( 'Make profile fields editable?', 'em-pro' ), 'dbem_emp_booking_form_reg_input', __( 'If profile fields are set to show to logged in users, you can also choose whether or not to make these fields editable or just for viewing reference.','em-pro' ) );
					?>
				</table>
				<?php echo $save_button; ?> 
			</div> <!-- . inside -->
			</div> <!-- .postbox -->
		<?php
		}
	}
	
	/**
	 * Add extra localized JS options to the em_wp_localize_script filter.
	 * @param array $vars
	 * @return array
	 */
	public static function em_wp_localize_script( $vars ){
		$vars['max_input_vars'] = ini_get('max_input_vars'); //so we can check the directive before submitting a form and stringify
		return $vars;
	}
	
	/**
	 * Enqueues the admin JS for the forms editor 
	 */
	public static function admin_enqueue_scripts(){
		if( !empty($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-forms-editor' ){
			wp_enqueue_script('em-pro-forms-editor', plugins_url('includes/js/forms-editor.js',__FILE__), array('jquery'), EMP_VERSION); //jQuery will load as dependency
		}
	}
	
	/**
	 * Fix for info submitted using JSON workaround for forms containing more fields than the max_ini_vars directive allows.
	 */
	public static function max_ini_vars_fix(){
		if( isset($_REQUEST['em_fields_json']) ){
			$test = str_replace(array('[]\\"','\\"', "\\'",'\\\\'),array('"','"',"'",'\\'), trim($_REQUEST['em_fields_json']));
			$test = json_decode($test, true);
			if (count($test)) $_REQUEST = array_merge($_REQUEST, $test);
		}
	}
	
}
EM_Forms::init();


class EM_Form extends EM_Object {
	
	public $form_fields = array();
	public $form_name = 'Default';
	public $field_values = array();
	public $user_fields = array();
	public $core_user_fields = array(
		'name' => 'Name',
		'user_login' => 'Username Login',
		'user_email' => 'E-mail (required)',
		'user_password' => 'Password',
		'first_name' => 'First Name',
		'last_name' => 'Last Name',
		'user_url' => 'Website',
		'aim' => 'AIM',
		'yim' => 'Yahoo IM',
		'jabber' => 'Jabber / Google Talk',
		'about' => 'Biographical Info'
	);
	protected $custom_user_fields = array();
	public $form_required_error = '';
	static $validate;
	/**
	 * If this form represents user fields, then it's set to true, otherwise set to false
	 * @var boolean
	 */
	public $is_user_form = false;
	
	function __construct( $form_data, $form_name=false, $user_fields = true ){
		if( is_array($form_data) ){
			//load form data from array
			$this->form_fields = $form_data;
		}else{
			//assume the text is the form name
			$this->form_fields = get_option($form_data);
			$this->form_name = $form_data;
		}
		if( !empty($form_name) ){
			$this->form_name = $form_name;
		}
		if( $user_fields ){
			$this->user_fields = apply_filters('emp_form_user_fields',$this->core_user_fields, $this);
			$this->custom_user_fields = array_diff($this->user_fields, $this->core_user_fields);
		}
	}
	
	function get_post( $validate = true ){
	    $custom_user_fields = EM_User_Fields::get_form()->form_fields;
		foreach($this->form_fields as $field){
		    $fieldid = $field['fieldid'];
			$value = '';
			if( !isset($_REQUEST[$fieldid]) ){ //for things like checkboxes when editing
			    $_REQUEST[$fieldid] = '';
			    if($field['type'] == 'checkbox') $_REQUEST[$fieldid] = '0'; //force save a 0 rather than a blank so we can check it
			}
			if( !is_array($_REQUEST[$fieldid])){
				$this->field_values[$fieldid] = wp_kses_data(stripslashes($_REQUEST[$fieldid]));
			}elseif( is_array($_REQUEST[$fieldid])){
			    $array = array();
			    foreach( $_REQUEST[$fieldid] as $key => $array_value ){
			        $array[$key] = wp_kses_data(stripslashes($array_value));
			    }
				$this->field_values[$fieldid] = $array;
			}
			//if this is a custom user field, change $filed to the original field so the right date/time info is retreived
	    	if( array_key_exists($field['type'], $this->custom_user_fields) && array_key_exists($field['fieldid'], $custom_user_fields) ){
	    	    $field = $custom_user_fields[$field['fieldid']];
	    	}
			//dates and time are special
			if( in_array( $field['type'], array('date','time')) ){
				if( !empty($_REQUEST[$fieldid]['start']) ){
					$this->field_values[$fieldid] = wp_kses_data($_REQUEST[$fieldid]['start']);
					if( $field['options_'.$field['type'].'_range'] && !empty($_REQUEST[$fieldid]['end']) ){
						$this->field_values[$fieldid] .= ','. wp_kses_data($_REQUEST[$fieldid]['end']);
					}
				}else{
				    $this->field_values[$fieldid] = '';
				}
			}
			//check that user fields were indeed submitted for validation by logged in users, or were not editable, in which case we populate form with previously saved data 
			if( array_key_exists($field['type'], $this->user_fields) && !self::validate_reg_fields($field) ){
				$this->field_values[$fieldid] = EM_User_Fields::get_user_meta(get_current_user_id(), $field['type']);
			}
		}
		return true;
	}
	
	function get_values(){
		return $this->field_values;
	}
	
	function get_formatted_value( $field, $field_value ){
		//FIX FOR BUG IN 2.2.5.4 and earlier for bookings using use no-user-mode
		if( in_array($field['type'], array('time','date')) ){
			if( is_array($field_value) && !empty($field_value['start']) ){
				$temp_val = $field_value['start'];
				if( !empty($field_value['end']) ){
					$temp_val .= ','.$field_value['start'];
				}
				$field_value = $temp_val;
			}elseif( is_array($field_value) && empty($field_value['start']) ){
			    $field_value = 'n/a'; //fix for empty value saves in 2.2.8
			}
		}
		//output formatted value for special fields
		switch( $field['type'] ){
			case 'checkbox':
				$field_value = ($field_value && $field_value != 'n/a') ? emp__('Yes','events-manager'):emp__('No','events-manager');
				break;
			case 'date':
			    //split ranges (or create single array) and format, then re-implode
			    if( $field_value != 'n/a' ){
					$date_format = ( get_option('dbem_date_format') ) ? get_option('dbem_date_format'):get_option('date_format');
				    $field_values = explode(',', $field_value);
				    foreach($field_values as $key => $value){
						$field_values[$key] = date($date_format, strtotime($value));
					}
				    $field_value = implode(',', $field_values);
				    //set seperator and replace the comma
					$seperator = empty($field['options_date_range_seperator']) ? ' - ': $field['options_date_range_seperator'];
					$field_value = str_replace(',',' '.$seperator.' ', $field_value);
			    }
				break;
			case 'time':
			    //split ranges (or create single array) and format, then re-implode
			    if( $field_value != 'n/a' ){
					$time_format = ( get_option('dbem_time_format') ) ? get_option('dbem_time_format'):get_option('time_format');
				    $field_values = explode(',', $field_value);
				    foreach($field_values as $key => $value){
						$field_values[$key] = date($time_format, strtotime('2010-01-01 '.$value));
					}
				    $field_value = implode(',', $field_values);
					//set seperator and replace the comma
					$seperator = empty($field['options_time_range_seperator']) ? ' - ': $field['options_time_range_seperator'];
					$field_value = str_replace(',',' '.$seperator.' ', $field_value);
				}
				break;
			case 'booking_comment':
				if( $field_value == 'n/a' && !empty($EM_Booking->booking_comment) ){ $field_value = $EM_Booking->booking_comment; }
				break;
			case 'country':
				if( $field_value != 'n/a' ){ 
					$countries = em_get_countries();
					if( !empty($countries[$field_value]) ) $field_value = $countries[$field_value];
				}
				break;
			default:
			    if( is_array($field_value) ){ $field_value = implode(', ', $field_value); }
			    break;
		}
		return $field_value;
	}
	
	/**
	 * Returns true if this field is not a user field or an html field, meaning it is stored information not at a user-account level, false if not.
	 * @param mixed $field_or_id
	 * @return boolean
	 */
	public function is_normal_field( $field_or_id ){
        $field_id = is_array($field_or_id) ? $field_or_id['fieldid'] : $field_or_id;
	    return array_key_exists($field_id, $this->form_fields) && !array_key_exists($field_id, $this->user_fields) && !in_array($field_id, array('user_email','user_name')) && $this->form_fields[$field_id]['type'] != 'html';
	}
	
	/**
	 * Returns true if this is a field stored as at a user-account level, false if not.
	 * @param mixed $field_or_id
	 * @return boolean
	 */
	public function is_user_field( $field_or_id ){
        $field_id = ( is_object($field_or_id) ) ? $field_or_id['fieldid'] : $field_or_id;
	    return array_key_exists($field_id, $this->user_fields) || in_array($field_id, array('user_email','user_name'));
	}
	
	/**
	 * Prints html fields according to this field structure.
	 * @param array $booking_form_fields
	 */
	function __toString(){
		$return = '';
		if( is_array($this->form_fields) ){
			foreach($this->form_fields as $field){
				$return .= self::output_field($field);
			}
		}
		return apply_filters('emp_form_output',$return, $this);
	}
	
	function output_field($field, $post=true){
		ob_start();
		$required = ( !empty($field['required']) ) ? ' '.apply_filters('emp_forms_output_field_required','<span class="em-form-required">*</span>'):'';
		$field = $this->translate_field($field);
		switch($field['type']){
			case 'html':
			     echo $this->output_field_input($field, $post);
			     break;
			case 'text':
			case 'textarea':
			case 'checkbox':
			case 'date':
			case 'checkboxes':
			case 'radio':
			case 'select':
			case 'country':
			case 'multiselect':
			case 'time':
				$tip_type = $field['type'];
				if( $field['type'] == 'textarea' ) $tip_type = 'text';
				if( in_array($field['type'], array('select','multiselect')) ) $tip_type = 'select';
				if( in_array($field['type'], array('checkboxes','radio')) ) $tip_type = 'selection';
				?>
				<p class="input-group input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<label for='<?php echo $field['fieldid'] ?>'>
						<?php if( !empty($field['options_'.$tip_type.'_tip']) ): ?>
							<span class="form-tip" title="<?php echo esc_attr($field['options_'.$tip_type.'_tip']); ?>">
								<?php echo $field['label'] ?> <?php echo $required  ?>
							</span>
						<?php else: ?>
							<?php echo $field['label'] ?> <?php echo $required  ?>
						<?php endif; ?>
					</label>
					<?php echo $this->output_field_input($field, $post); ?>
				</p>
				<?php
				break;
			case 'captcha':
				if( !self::show_reg_fields() ) break;
				if( !is_user_logged_in() ){
					?>
					<p class="input-group input-<?php echo $field['type']; ?> input-field-<?php echo $field['fieldid'] ?>">
					<?php echo $this->output_field_input($field, $post); ?>
					</p>
					<?php
				}
				break;
			default:
				if( array_key_exists($field['type'], $this->user_fields) && self::show_reg_fields($field) ){
					if( array_key_exists($field['type'], $this->core_user_fields) ){
						if( $field['type'] == 'user_password' ){
							?>
							<p class="input-<?php echo $field['type']; ?> input-user-field">
								<label for='<?php echo $field['fieldid'] ?>'>
									<?php if( !empty($field['options_reg_tip']) ): ?>
										<span class="form-tip" title="<?php echo esc_attr($field['options_reg_tip']); ?>">
											<?php echo $field['label'] ?> <?php echo $required  ?>
										</span>
									<?php else: ?>
										<?php echo $field['label'] ?> <?php echo $required  ?>
									<?php endif; ?>
								</label>
								<input type="password" name="<?php echo $field['fieldid'] ?>" />
							</p>
							<?php
						}else{
							//registration fields
							if( empty($_REQUEST[$field['fieldid']]) && is_user_logged_in() && get_option('dbem_emp_booking_form_reg_show') && !EM_Bookings::$force_registration ){
								$post = EM_User_Fields::get_user_meta(get_current_user_id(), $field['type']);
							}
							?>
							<p class="input-<?php echo $field['type']; ?> input-user-field">
								<label for='<?php echo $field['fieldid'] ?>'>
									<?php if( !empty($field['options_reg_tip']) ): ?>
										<span class="form-tip" title="<?php echo esc_attr($field['options_reg_tip']); ?>">
											<?php echo $field['label'] ?> <?php echo $required  ?>
										</span>
									<?php else: ?>
										<?php echo $field['label'] ?> <?php echo $required  ?>
									<?php endif; ?>
								</label> 
								<?php echo $this->output_field_input($field, $post); ?>
							</p>
							<?php	
						}
					}elseif( array_key_exists($field['type'], $this->custom_user_fields) ) {
						?>
						<p class="input-<?php echo $field['type']; ?> input-user-field">
							<label for='<?php echo $field['fieldid'] ?>'>
								<?php if( !empty($field['options_reg_tip']) ): ?>
									<span class="form-tip" title="<?php echo esc_attr($field['options_reg_tip']); ?>">
										<?php echo $field['label'] ?> <?php echo $required  ?>
									</span>
								<?php else: ?>
									<?php echo $field['label'] ?> <?php echo $required  ?>
								<?php endif; ?>
							</label>
							<?php do_action('em_form_output_field_custom_'.$field['type'], $field, $post); ?>
						</p>
						<?php
					}
				}
				break;
		}	
		return apply_filters('emp_forms_output_field', ob_get_clean(), $this, $field);	
	}
	
	function output_field_input($field, $post=true){
		ob_start();
		$default = '';
		$default_html = '';
		if($post === true && !empty($_REQUEST[$field['fieldid']])) {
			$default = is_array($_REQUEST[$field['fieldid']]) ? $_REQUEST[$field['fieldid']]:esc_attr($_REQUEST[$field['fieldid']]);
			$default_html = is_array($_REQUEST[$field['fieldid']]) ? $_REQUEST[$field['fieldid']] : esc_attr($_REQUEST[$field['fieldid']]);
		}elseif( $post !== true && !empty($post) ){
			$default = is_array($post) ? $post:esc_attr($post);
			$default_html = is_array($post) ? $post : esc_attr($post);
		}
		$field_name = !empty($field['name']) ? $field['name']:$field['fieldid'];
		switch($field['type']){
			case 'html':
			    echo $field['options_html_content'];
			    break;			
			case 'text':
				?>
				<input type="text" name="<?php echo $field_name ?>" id="<?php echo $field['fieldid'] ?>" class="input" value="<?php echo $default; ?>"  />
				<?php
				break;	
			case 'textarea':
				$size = 'rows="2" cols="20"';
			    if( defined('EMP_FORMS_TEXTAREA_SIZE') && EMP_FORMS_TEXTAREA_SIZE ){
			        $sizes = explode(',', EMP_FORMS_TEXTAREA_SIZE);
			        if( count($sizes) > 1 ){
						$size = 'rows="'.$sizes[0].'" cols="'.$sizes[1].'"';
					}else{
						$size = EMP_FORMS_TEXTAREA_SIZE;				
					}
			    }
				?>
				<textarea name="<?php echo $field_name ?>" id="<?php echo $field['fieldid'] ?>" class="input" <?php echo $size; ?>><?php echo $default_html; ?></textarea>
				<?php
				break;
			case 'checkbox':
				?>
				<input type="checkbox" name="<?php echo $field_name ?>" id="<?php echo $field['fieldid'] ?>" value="1" <?php if( ($default && $default != 'n/a') || (($post === true || $post === '') && $field['options_checkbox_checked'])) echo 'checked="checked"'; ?> />
				<?php
				break;
			case 'checkboxes':
				echo "<span class=\"input-group\">";
				if(!is_array($default)) $default = array();
				$values = explode("\r\n",$field['options_selection_values']);
				foreach($values as $value){ 
					$value = trim($value); 
					?><input type="checkbox" name="<?php echo $field_name ?>[]" class="<?php echo $field['fieldid'] ?>" value="<?php echo esc_attr($value) ?>" <?php if(in_array($value, $default)) echo 'checked="checked"'; ?> /> <?php echo $value ?><br /><?php 
				}
				echo "</span>";
				break;
			case 'radio':
				echo "<span class=\"input-group\">";
				$values = explode("\r\n",$field['options_selection_values']);
				foreach($values as $value){
					$value = trim($value); 
					?><input type="radio" name="<?php echo $field_name ?>" class="<?php echo $field['fieldid'] ?>" value="<?php echo esc_attr($value) ?>" <?php if($value == $default) echo 'checked="checked"'; ?> /> <?php echo $value ?><br /><?php
				}
				echo "</span>";
				break;
			case 'select':
			case 'multiselect':
				$values = explode("\r\n",$field['options_select_values']);
				$multi = $field['type'] == 'multiselect';
				if($multi && !is_array($default)) $default = (empty($default)) ? array():array($default);
				?>
				<select name="<?php echo $field_name ?><?php echo ($multi) ? '[]':''; ?>" class="<?php echo $field['fieldid'] ?>" <?php echo ($multi) ? 'multiple':''; ?>>
				<?php 
					//calculate default value to be checked
					if( !$field['options_select_default'] ){
						?>
						<option value=""><?php echo esc_html($field['options_select_default_text']); ?></option>
						<?php
					}
					$count = 0;
				?>
				<?php foreach($values as $value): $value = trim($value); $count++; ?>
					<option <?php echo (($count == 1 && $field['options_select_default']) || ($multi && in_array($value, $default)) || ($value == $default) )?'selected="selected"':''; ?>><?php echo esc_html($value) ?></option>
				<?php endforeach; ?>
				</select>
				<?php
				break;
			case 'country':
				?>
				<select name="<?php echo $field_name ?>" class="<?php echo $field['fieldid'] ?>">
				<?php foreach(em_get_countries(__('none selected','em-pro')) as $country_key => $country_name): ?>
					<option value="<?php echo $country_key; ?>"  <?php echo ($country_key == $default) ?'selected="selected"':''; ?>><?php echo $country_name; ?></option>
					<?php endforeach; ?>
				</select>
				<?php	
				break;
			case 'date':
			    $date_type = !empty($field['options_date_range']) ? 'em-date-range':'em-date-single';
				if( !empty($_REQUEST[$field_name]['start']) && preg_match('/\d{4}-\d{2}-\d{2}/', $_REQUEST[$field_name]['start'])) {
					$default = array( $_REQUEST[$field_name]['start'] );
					if( !empty($_REQUEST[$field_name]['end']) && preg_match('/\d{4}-\d{2}-\d{2}/', $_REQUEST[$field_name]['end'])){
						$default[] = $_REQUEST[$field_name]['end'];
					}
				}else{
					if( is_string($default) ) $default = explode(',',$default);
					if( !empty($default[0]) && !preg_match('/\d{4}-\d{2}-\d{2}/', $default[0]) ) $default = ''; //make sure the value is a date
				}
				//we're adding a [%s] to the field id and replacing this for the start-end field names because this way other bits (e.g. attendee forms) can manipulate where the [start] and [end] are placed in the element name. 
				$field_id = strstr($field_name,'[') ? $field_name:$field_name.'[%s]';
				?>
    			<span class="<?php echo $date_type; ?>">			
					<input class="em-date-start em-date-input-loc" type="text" name="<?php echo str_replace('[%s]','[start_loc]', $field_id); ?>" />
					<input class="em-date-input" type="hidden" name="<?php echo str_replace('[%s]','[start]', $field_id); ?>" value="<?php echo !empty($default[0]) ? $default[0]:'' ?>" />
					<?php if( !empty($field['options_date_range']) ) : ?>
					<?php echo $field['options_date_range_seperator']; ?>
					<input class="em-date-end em-date-input-loc" type="text" name="<?php echo str_replace('[%s]','[end_loc]', $field_id); ?>" />
					<input class="em-date-input" type="hidden" name="<?php echo str_replace('[%s]','[end]', $field_id); ?>" value="<?php echo !empty($default[1]) ? $default[1]:'' ?>" />
					<?php endif; ?>
				</span>
    			<?php
    			break;	
			case 'time':
			    $date_type = !empty($field['options_time_range']) ? 'em-time-range':'em-time-single';
				if( !empty($_REQUEST[$field_name]['start']) && !preg_match('/^([01]\d|2[0-3]):([0-5]\d) ?(AM|PM)?$/', $_REQUEST[$field_name]['start']) ) {
					$default = array( $_REQUEST[$field_name]['start'] );
					if( !empty($_REQUEST[$field_name]['end']) && !preg_match('/^([01]\d|2[0-3]):([0-5]\d) ?(AM|PM)?$/', $_REQUEST[$field_name]['end']) ){
						$default[] = $_REQUEST[$field_name]['end'];
					}
				}else{
					if( !is_array($default) ) $default = explode(',',$default);
					if( !empty($default[0]) && !preg_match('/^([01]\d|2[0-3]):([0-5]\d) ?(AM|PM)?$/', $default[0]) ) $default = ''; //make sure the value is a date
				}
				//we're adding a [%s] to the field id and replacing this for the start-end field names because this way other bits (e.g. attendee forms) can manipulate where the [start] and [end] are placed in the element name. 
				$field_id = strstr($field_name,'[') ? $field_name:$field_name.'[%s]';
				?>
    			<span class="<?php echo $date_type; ?>">			
					<input class="em-time-input em-time-start" type="text" size="8" maxlength="8" name="<?php echo str_replace('[%s]','[start]', $field_id); ?>" value="<?php echo !empty($default[0]) ? $default[0]:'' ?>" />
					<?php if( !empty($field['options_time_range']) ) : ?>
					<?php echo $field['options_time_range_seperator']; ?>
					<input class="em-time-input em-time-end" type="text" size="8" maxlength="8" name="<?php echo str_replace('[%s]','[end]', $field_id); ?>" value="<?php echo !empty($default[1]) ? $default[1]:'' ?>" />
					<?php endif; ?>					
				</span>
    			<?php
    			break;	
			case 'captcha':
			    if( !self::show_reg_fields() ) break;
				$lang = str_replace('_', '-', get_locale());
				//language list extracted from - https://developers.google.com/recaptcha/docs/language
				$langs = array('ar', 'bn', 'bg', 'ca', 'zh-CN', 'zh-TW', 'hr', 'cs', 'da', 'nl', 'en-GB', 'en', 'et', 'fil', 'fi', 'fr', 'fr-CA', 'de', 'gu', 'de-AT', 'de-CH', 'el', 'iw', 'hi', 'hu', 'id', 'it', 'ja', 'kn', 'ko', 'lv', 'lt', 'ms', 'ml', 'mr', 'no', 'fa', 'pl', 'pt', 'pt-BR', 'pt-PT', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'es-419', 'sv', 'ta', 'te', 'th', 'tr', 'uk', 'ur', 'vi');
				if( !in_array($lang, $langs) && in_array( substr($lang, 0, 2), $langs) ){
				}elseif( !in_array($lang, $langs) ){
				$lang = 'en';
				}
				?>
					<div class="g-recaptcha" data-sitekey="<?php echo $field['options_captcha_key_pub']; ?>"></div>
					<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang; ?>"></script>
				<?php
				break;
			default:
				if( array_key_exists($field['type'], $this->user_fields) && self::show_reg_fields() ){
					//registration fields
				    if( get_option('dbem_emp_booking_form_reg_input') || !is_user_logged_in() || EM_Bookings::$force_registration ){
						?>
						<input type="text" name="<?php echo $field_name ?>" id="<?php echo $field['fieldid'] ?>" class="input" value="<?php echo $default; ?>"  />
						<?php
					}else{
						echo $default;
					}
				}
				break;
		}	
		return apply_filters('emp_forms_output_field_input', ob_get_clean(), $this, $field, $post);	
	}
	
	/**
	 * Validates all fields, if false, an array of objects is returned.
	 * @return array|string
	 */
	function validate(){
		$reg_fields = self::validate_reg_fields();
		foreach( $this->form_fields as $field ){
			$field_id = $field['fieldid'];
			if( $reg_fields || ( !$reg_fields && !array_key_exists($field['type'], $this->user_fields) ) ){ //don't validate reg info if we won't grab anything in get_post
				$value = ( array_key_exists($field_id, $this->field_values) ) ? $this->field_values[$field_id] : '';
				$this->validate_field($field_id, $value);
			}
		}
		if( count($this->get_errors()) > 0 ){
			return false;
		}
		return true;
	}
	
	/**
	 * Validates a field and adds errors to the object it's referring to (can be any extension of EM_Object)
	 * @param array $field
	 * @param mixed $value
	 */
	function validate_field( $field_id, $value ){
		$field = array_key_exists($field_id, $this->form_fields) ? $this->form_fields[$field_id]:false;
		$field = $this->translate_field($field);
		$value = (is_array($value)) ? $value:trim($value);
		$err = sprintf($this->form_required_error, $field['label']);
		if( is_array($field) ){
			$result = true; //innocent until proven guilty
			switch($field['type']){
				case 'text':
				case 'textarea':
					//regex
					if( trim($value) != '' && !empty($field['options_text_regex']) && !@preg_match('/'.$field['options_text_regex'].'/',$value) ){
						$this_err = (!empty($field['options_text_error'])) ? $field['options_text_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}
					//non-empty match
					if( $result && trim($value) == '' && !empty($field['required']) ){
						$this->add_error($err);
						$result = false;
					}
					break;
				case 'checkbox':
					//non-empty match
					if( empty($value) && !empty($field['required']) ){
						$this_err = (!empty($field['options_checkbox_error'])) ? $field['options_checkbox_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}
					break;
				case 'checkboxes':
				    //decode and trim both submitted and available values
					$values = explode("\r\n",$field['options_selection_values']);
					foreach($values as $k => $v) $values[$k] = html_entity_decode(trim($v));
					if( !is_array($value) ) $value = array();
					foreach( $value as $k => $v ) $value[$k] = html_entity_decode(trim($v));
					//in-values
					if( (empty($value) && !empty($field['required'])) || count(array_diff($value, $values)) > 0 ){
						$this_err = (!empty($field['options_selection_error'])) ? $field['options_selection_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}
					break;
				case 'radio':
				    $value = html_entity_decode($value); //we must make sure all is decoded, both selection values and submitted value itself
					$values = explode("\r\n",html_entity_decode($field['options_selection_values']));
					foreach($values as $k => $v) $values[$k] = trim($v);
					//in-values
					if( (!empty($value) && !in_array($value, $values)) || (empty($value) && !empty($field['required'])) ){
						$this_err = (!empty($field['options_selection_error'])) ? $field['options_selection_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}				
					break;
				case 'multiselect':
				    //decode and trim both submitted and available values
					$values = explode("\r\n",$field['options_select_values']);
					foreach($values as $k => $v) $values[$k] = html_entity_decode(trim($v));
					if( !is_array($value) ) $value = array();
					foreach( $value as $k => $v ) $value[$k] = html_entity_decode(trim($v));
					//in_values
					if( (empty($value) && !empty($field['required'])) || count(array_diff($value, $values)) > 0 ){
						$this_err = (!empty($field['options_select_error'])) ? $field['options_select_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}				
					break;
				case 'select':
					$values = explode("\r\n",$field['options_select_values']);
					foreach($values as $k => $v) $values[$k] = trim($v);
					//in-values
					if( (!empty($value) && !in_array($value, $values)) || (empty($value) && !empty($field['required'])) ){
						$this_err = (!empty($field['options_select_error'])) ? $field['options_select_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}				
					break;
				case 'country':
					$values = em_get_countries(emp__('none selected','events-manager'));
					//in-values
					if( (!empty($value) && !array_key_exists($value, $values)) || (empty($value) && !empty($field['required'])) ){
						$this_err = (!empty($field['options_select_error'])) ? $field['options_select_error']:$err;
						$this->add_error($this_err);
						$result = false;
					}				
					break;			
				case 'date':
				    $dates = !is_array($value) ? explode(',',$value):$value;
				    $start_date = $dates[0];
				    $end_date = !empty($dates[1]) ? $dates[1]:'';
				    if( !empty($start_date) ){
						if( preg_match('/\d{4}-\d{2}-\d{2}/', $start_date) ){
							if( $field['options_date_range'] ){
								if( empty($end_date) ){
									$this_err = (!empty($field['options_date_error_end'])) ? $field['options_date_error_end']:$this->add_error(__('You must also add an end date.','em-pro'));
									$this->add_error($this_err);
									$result = false;
								}elseif( !preg_match('/\d{4}-\d{2}-\d{2}/', $end_date) ){
									$this_err = (!empty($field['options_date_error_format'])) ? $field['options_date_error_format']:emp__('Dates must have correct formatting. Please use the date picker provided.','events-manager');
									$this->add_error($this_err);
									$result = false;
								}else{
									//valid end date, check for date order
									if( strtotime($start_date) > strtotime($end_date) ){
										$this_err = (!empty($field['options_date_range_order'])) ? $field['options_date_range_order']:emp__('Please provide a later end date.','events-manager');
										$this->add_error($this_err);
										$result = false;
									}
								}
							}
						}else{
							$this_err = (!empty($field['options_date_error_format'])) ? $field['options_date_error_format']:emp__('Dates must have correct formatting. Please use the date picker provided.','events-manager');
							$this->add_error($this_err);
							$result = false;
						}
					}elseif( !empty($field['required']) ){
						if( $field['options_date_range'] && !empty($end_date) ){
							$this_err = (!empty($field['options_date_error_start'])) ? $field['options_date_error_start']:$this->add_error(__('You must provide a start date.','em-pro'));
							$this->add_error($this_err);
							$result = false;
						}else{
							$this_err = (!empty($field['options_date_error'])) ? $field['options_date_error']:$err;
							$this->add_error($this_err);
							$result = false;
						}
					}
					break;			
				case 'time':
				    $times = !is_array($value) ? explode(',',$value):$value;
				    $start_time = $times[0];
				    $end_time = !empty($times[1]) ? $times[1]:'';
				    if( !empty($start_time) ){
						if( preg_match('/^([01]\d|2[0-3]):([0-5]\d) ?(AM|PM)?$/', $start_time) ){
							if( $field['options_time_range'] ){
								if( empty($end_time) ){
									$this_err = (!empty($field['options_time_error_end'])) ? $field['options_time_error_end']:$this->add_error(__('You must provide an end time.','em-pro'));
									$this->add_error($this_err);
									$result = false;
								}elseif( !preg_match('/^([01]\d|2[0-3]):([0-5]\d) ?(AM|PM)?$/', $end_time) ){
									$this_err = (!empty($field['options_time_error_format'])) ? $field['options_time_error_format']:$this->add_error(__('Please use the time picker provided to select the appropriate time format.','em-pro'));
									$this->add_error($this_err);
									$result = false;
								}
							}
						}else{
							$this_err = (!empty($field['options_time_error_format'])) ? $field['options_time_error_format']:__('Please use the time picker provided to select the appropriate time format.','em-pro');
							$this->add_error($this_err);
							$result = false;
						}
					}elseif( !empty($field['required']) ){
						if( $field['options_time_range'] && !empty($end_time) ){
							$this_err = (!empty($field['options_time_error_start'])) ? $field['options_time_error_start']:$this->add_error(__('You must provide a start time.','em-pro'));
							$this->add_error($this_err);
							$result = false;
						}else{
							$this_err = (!empty($field['options_time_error'])) ? $field['options_time_error']:$err;
							$this->add_error($this_err);
							$result = false;
						}
					}
					break;		
				case 'captcha':
				    if( !self::validate_reg_fields() || !self::show_reg_fields() ) break;
					if( !empty($this->ignore_captcha) || defined('EMP_CHECKED_CAPTCHA') || is_user_logged_in() ) break;
					$response = array('success'=>false);
					if( !empty($_REQUEST['g-recaptcha-response']) ){
						$secret = sanitize_text_field($field['options_captcha_key_priv']);
						$captcha_post_args = array('httpversion'=>'1.1','user-agent'=>'EventsManagerPro/'.EMP_VERSION);
						$captcha_post_args['body'] = array('secret'=>$secret, 'response'=>$_REQUEST['g-recaptcha-response'], 'remoteip'=>$_SERVER['REMOTE_ADDR']);
						$captcha_response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', $captcha_post_args);
						$response = !is_wp_error($captcha_response) ? json_decode($captcha_response['body'],true) : array('success'=>false);
					}
					if( empty($response['success']) ){
						$err = !empty($field['options_captcha_error']) ? $field['options_captcha_error']:$err;
						$this->add_error($err);
						EM_Pro::log(array('Validation error', 'Response'=>$captcha_response, 'POST'=>$_POST), 'recaptcha');
					}
					define('EMP_CHECKED_CAPTCHA', true); //captchas can only be checked once, and since we only need one captcha per submission....
					break;
				default:
					//Registration and custom fields
					$is_manual_booking_new_user = (is_user_logged_in() && !empty($_REQUEST['manual_booking']) && wp_verify_nonce($_REQUEST['manual_booking'], 'em_manual_booking_'.$_REQUEST['event_id']) && $_REQUEST['person_id'] == -1 );
					if( array_key_exists($field['type'], $this->user_fields) && self::validate_reg_fields($field) ){
						//preliminary checks/exceptions
						if( is_user_logged_in() && !get_option('dbem_emp_booking_form_reg_input') ) break;
						//add field-specific validation
						if ( $field['type'] == 'user_email' ) {
							if( ! is_email( $value ) ){
								$this->add_error( emp__( '<strong>ERROR</strong>: The email address isn&#8217;t correct.', 'events-manager') );
								$result = false;
							}elseif( is_user_logged_in() ){
								$email_exists = email_exists($value);
								if( $email_exists && $email_exists != get_current_user_id() ){
									$this->add_error( get_option('dbem_booking_feedback_email_exists') );
									$result = false;	
								}
							}
						}
						//regex
						if( trim($value) != '' && !empty($field['options_reg_regex']) && !@preg_match('/'.$field['options_reg_regex'].'/',$value) ){
							$this_err = (!empty($field['options_reg_error'])) ? $field['options_reg_error']:$err;
							$this->add_error($this_err);
							$result = false;
						}
						//non-empty match
						if( empty($value) && !empty($field['required']) ){
							$this->add_error($err);
							$result = false;
						}
						//custom field chekcs
						if( array_key_exists($field['type'], $this->custom_user_fields)) {
							//custom field, so just apply 
							$result = apply_filters('em_form_validate_field_custom', $result, $field, $value, $this);
						}
					}
					break;
			}
		}else{
			$result = false;
		}
		return apply_filters('emp_form_validate_field',$result, $field, $value, $this);
	}
	
	/**
	 * Gets an array of field keys accepted by the booking form 
	 */
	function get_fields_map(){
		$map = array (
			'fieldid','label','type','required',
			'options_select_values','options_select_default','options_select_default_text','options_select_error','options_select_tip',
			'options_selection_values','options_selection_default','options_selection_error','options_selection_tip',
			'options_checkbox_error','options_checkbox_checked','options_checkbox_tip',
			'options_text_regex','options_text_error','options_text_tip',
			'options_reg_regex', 'options_reg_error','options_reg_tip',
			'options_captcha_theme','options_captcha_key_priv','options_captcha_key_pub', 'options_captcha_error', 'options_captcha_tip',
			'options_date_error','options_date_range','options_date_range_seperator','options_date_error_format','options_date_error_end','options_date_tip',
			'options_time_error','options_time_range','options_time_range_seperator','options_time_error_format','options_time_error_end','options_time_error_start','options_time_tip',
			'options_html_content'
		);
		return apply_filters('em_form_get_fields_map', $map);
	}
	
	function translate_field( $field_data ){
		if( EM_ML::$is_ml && !empty($field_data['ml']) ){
			foreach($field_data['ml'] as $field_key => $translations){
				if( array_key_exists($field_key, $field_data) && !empty($translations[EM_ML::$current_language]) ){
					$field_data[$field_key] = $translations[EM_ML::$current_language];
				}
			}
		}
		return $field_data;
	}
	
	/*
	 * --------------------------------------------------------
	 * Admin-Side Functions
	 * --------------------------------------------------------
	 */
	
	function get_input_default($key, $field_values, $type='text', $value=""){
		$return = '';
		if(is_array($field_values)){
			switch ($type){
				case 'text':
					$return = (array_key_exists($key,$field_values)) ? 'value="'.esc_attr($field_values[$key]).'"':'value="'.esc_attr($value).'"';
					break;
				case 'textarea':
					$return = (array_key_exists($key,$field_values)) ? esc_html($field_values[$key]):esc_html($value);
					break;
				case 'select':
					$return = ( array_key_exists($key,$field_values) && $value == $field_values[$key] ) ? 'selected="selected"':'';
					break;
				case 'checkbox':
					$return = ( !empty($field_values[$key]) && $field_values[$key] == 1 ) ? 'checked="checked"':'';
					break;
				case 'radio':
					$return = ( $value == $field_values[$key] ) ? 'checked="checked"':'';
					break;
			}
		}
		return apply_filters('emp_form_get_input_default',$return, $key, $field_values, $type, $value);
	}
	function input_default($key, $fields, $type = 'text', $value=""){ echo self::get_input_default($key, $fields, $type, $value); }

	
	/**
	 * Returns whether or not to show registration fields, and if a field type or field object is passed it'll check whether that specific field should be shown in this instance.
	 * Takes into account whether a user is logged in and fields like email and name should be shown.
	 * @param string $field
	 * @return mixed
	 */
	public static function show_reg_fields( $field = false ){
		$show_reg = (!is_user_logged_in() && get_option('dbem_bookings_anonymous')) || EM_Bookings::$force_registration || (is_user_logged_in() && get_option('dbem_emp_booking_form_reg_show'));
		$is_hidden_reg = false;
		if( !empty($field) ){
			$field_type = !empty($field['type']) ? $field['type'] : (string) $field;
			$hidden_fields = array('user_password','user_login'); //usernameas and passwords are always hidden once a user exists
			if( is_user_logged_in() && !EM_Bookings::$force_registration ){
				if( !get_option('dbem_emp_booking_form_reg_show_email') ){
					$hidden_fields = array_merge($hidden_fields, array('user_email'));
				}
				if( !get_option('dbem_emp_booking_form_reg_show_name') ){
					$hidden_fields = array_merge($hidden_fields, array('first_name','last_name', 'name'));
				}
				$hidden_fields = apply_filters('emp_hidden_reg_fields', $hidden_fields);
				$is_hidden_reg = in_array($field_type, $hidden_fields);
			}elseif( (!is_user_logged_in() || EM_Bookings::$force_registration) && get_option('dbem_bookings_registration_disable') ){
				$is_hidden_reg = in_array($field_type, apply_filters('emp_hidden_reg_fields', $hidden_fields));
			}
		}
		return apply_filters('emp_form_show_reg_fields', $show_reg && !$is_hidden_reg, $field); 
	}

	public static function validate_reg_fields( $field = false ){
		if( EM_Gateways::is_manual_booking(true) ) return true; //short circuit if we're on a manual booking for a new user
		$validate = is_user_logged_in() ? get_option('dbem_emp_booking_form_reg_show') && get_option('dbem_emp_booking_form_reg_input') : true;
		return $validate && self::show_reg_fields( $field );
	}
	
	function editor($user_fields = true, $custom_fields = true, $captcha_fields = true){
		$fields = $this->form_fields;
		if( empty($fields) ){ $fields = array(self::get_fields_map());  }
		$fields['blank_em_template'] = self::get_fields_map();
		$form_name = "em-form-". sanitize_title_with_dashes($this->form_name);
		?>
		<form method="post" action="" class="em-form-custom<?php if( EM_ML::$is_ml ) echo ' em-ml' ?>" id="<?php echo $form_name; ?>">
			<div>
				<div class="booking-custom-head">
					<div class='bc-col-sort bc-col'>&nbsp;</div>
					<div class='bc-col-label bc-col'><?php _e('Label','em-pro'); ?></div>
					<div class='bc-col-id bc-col'><?php _e('Field ID','em-pro'); ?><a title="<?php _e('DO NOT change these values if you want to keep your field settings associated with previous booking fields.','em-pro'); ?>">?</a></div>
					<div class='bc-col-type bc-col'><?php _e('Type','em-pro'); ?></div>
					<div class='bc-col-required bc-col'><?php _e('Required','em-pro'); ?></div>
				</div>
				<ul class="booking-custom-body">
					<?php foreach($fields as $field_key => $field_values): ?>
					<li class="booking-custom-item" <?php if( $field_key === 'blank_em_template' ){ echo 'id="booking-custom-item-template"'; }; ?>>
						<div class='bc-col-sort bc-col'><span class="dashicons dashicons-sort"></span></div>
						<div class='bc-col-label bc-col'>
							<input type="text" name="label[]" class="booking-form-custom-label" <?php self::input_default('label',$field_values); ?> />
							<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('label'); ?>
						</div>
						<div class='bc-col-id bc-col'><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" <?php self::input_default('fieldid',$field_values); ?> /></div>
						<div class='bc-col-type bc-col'>
							<select name="type[]" class="booking-form-custom-type">
								<option value=""><?php echo _e('Select Type','em-pro'); ?></option>
								<?php if($custom_fields): ?>
								<optgroup label="<?php _e('Customizable Fields','em-pro'); ?>">
									<option <?php self::input_default('type',$field_values,'select','text'); ?>>text</option>
									<option <?php self::input_default('type',$field_values,'select','html'); ?>>html</option>
									<option <?php self::input_default('type',$field_values,'select','checkbox'); ?>>checkbox</option>
									<option <?php self::input_default('type',$field_values,'select','textarea'); ?>>textarea</option>
									<option <?php self::input_default('type',$field_values,'select','checkboxes'); ?>>checkboxes</option>
									<option <?php self::input_default('type',$field_values,'select','radio'); ?>>radio</option>
									<option <?php self::input_default('type',$field_values,'select','select'); ?>>select</option>
									<option <?php self::input_default('type',$field_values,'select','multiselect'); ?>>multiselect</option>
									<option <?php self::input_default('type',$field_values,'select','country'); ?>>country</option>
									<option <?php self::input_default('type',$field_values,'select','date'); ?>>date</option>
									<option <?php self::input_default('type',$field_values,'select','time'); ?>>time</option>
									<?php if($captcha_fields): ?>
									<option <?php self::input_default('type',$field_values,'select','captcha'); ?>>captcha</option>
									<?php endif; ?>
								</optgroup>
								<?php endif; ?>
								<?php if($user_fields): ?>
								<optgroup label="<?php _e('Registration Fields','em-pro'); ?>" class="bc-core-user-fields">
									<?php foreach( $this->core_user_fields as $field_id => $field_name ): ?>
									<option value="<?php echo $field_id; ?>" <?php self::input_default('type',$field_values,'select',$field_id); ?>><?php echo $field_name; ?></option>
									<?php endforeach; ?>
								</optgroup>
								<?php 
									if( count($this->custom_user_fields) > 0 ){
										?>
										<optgroup label="<?php _e('Custom Registration Fields','em-pro'); ?>" class="bc-custom-user-fields">
											<?php foreach( $this->custom_user_fields as $field_id => $field_name ): ?>
											<option value="<?php echo $field_id; ?>" <?php self::input_default('type',$field_values,'select',$field_id); ?>><?php echo $field_name; ?></option>
											<?php endforeach; ?>
										</optgroup>
										<?php
									}
								?>
								<?php endif; ?>
							</select>
						</div>
						<div class='bc-col-required bc-col'>
							<input type="checkbox" class="booking-form-custom-required" value="1" <?php self::input_default('required',$field_values,'checkbox'); ?> />
							<input type="hidden" name="required[]" <?php self::input_default('required',$field_values,'text'); ?> />
						</div>
						<div class='bc-col-options bc-col'>
							<a href="#" class="booking-form-custom-field-remove" title="<?php esc_html_e('remove','em-pro'); ?>"><span class="dashicons dashicons-trash"></span></a>
							<a href="#" class="booking-form-custom-field-options" title="<?php esc_html_e('options','em-pro'); ?>"><span class="dashicons dashicons-admin-generic"></span></a>
						</div>
						<div class='booking-custom-types'>
							<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('label', $field_values); ?>
							<?php if($custom_fields): ?>
							<!-- select,multiselect -->
							<div class="bct-select bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
									<div class="bct-input">
										<textarea name="options_select_values[]"><?php self::input_default('options_select_values',$field_values,'textarea'); ?></textarea>
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_select_values'); ?>
										<em><?php _e('Available options, one per line.','em-pro'); ?></em>	
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_select_values', $field_values, 'textarea'); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Use Default?','em-pro'); ?></div>
									<div class="bct-input">
										<input type="checkbox" <?php self::input_default('options_select_default',$field_values,'checkbox'); ?> value="1" />
										<input type="hidden" name="options_select_default[]" <?php self::input_default('options_select_default',$field_values); ?> /> 
										<em><?php _e('If checked, the first value above will be used.','em-pro'); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Default Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_select_default_text[]" <?php self::input_default('options_select_default_text',$field_values,'text',__('Select ...','em-pro')); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_select_default_text'); ?>
										<em><?php _e('Shown when a default value isn\'t selected, selected by default.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_select_default_text', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_select_tip[]" <?php self::input_default('options_select_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_select_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_select_tip', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_select_error[]" <?php self::input_default('options_select_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_select_error'); ?>
										<em>
											<?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_select_error', $field_values); ?>
								</div>
								<?php do_action('emp_forms_editor_select_multiselect_options', $this, $field_values); ?>
							</div>
							<!-- html -->
							<div class="bct-html bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Content','em-pro'); ?></div>
									<div class="bct-input">
										<em><?php _e('This html will be displayed on your form, the label for this field is used only for reference purposes.','em-pro'); ?></em>
										<textarea name="options_html_content[]"><?php if( !empty($field_values['options_html_content']) ) echo $field_values['options_html_content']; ?></textarea>
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_html_content'); ?>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_html_content', $field_values, 'textarea'); ?>
								</div>
								<?php do_action('emp_forms_editor_html_options', $this, $field_values); ?>
							</div>
							<!-- country -->
							<div class="bct-country bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_country_error[]" <?php self::input_default('options_country_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_country_error'); ?>
										<em>
											<?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_country_error', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_country_tip[]" <?php self::input_default('options_country_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_country_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_country_tip', $field_values); ?>
								</div>
								<?php do_action('emp_forms_editor_country_options', $this, $field_values); ?>
							</div>
							<!-- date -->
							<div class="bct-date bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Date Range?','em-pro'); ?></div>
									<div class="bct-input">
										<input type="checkbox" <?php self::input_default('options_date_range',$field_values,'checkbox'); ?> value="1" />
										<input type="hidden" name="options_date_range[]" <?php self::input_default('options_date_range',$field_values); ?> /> 
										<em><?php _e('If selected, this field will also have an end-date.','em-pro'); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Separator','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_date_range_seperator[]" <?php self::input_default('options_date_range_seperator',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_date_range_seperator'); ?>
										<em><?php _e('This text will appear between the two date fields if this is a date range.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_date_range_seperator', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_date_tip[]" <?php self::input_default('options_date_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_date_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_date_tip', $field_values); ?>
								</div>
								<div class="bct-field">
									<p><strong><?php _e('Error Messages','em-pro'); ?></strong></p>
									<div class="bct-label"><?php _e('Field Required','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_date_error[]" <?php self::input_default('options_date_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_date_error'); ?>
										<em>
											<?php _e('This error will show this field is required and no value is entered.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_date_error', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Incorrect Formatting','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_date_error_format[]" <?php self::input_default('options_date_error_format',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_date_error_format'); ?>
										<em>
											<?php _e('This error will show if an incorrect date format is used.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.__('Please use the date picker provided to select the appropriate date format.','em-pro').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_date_error_format', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('End date required','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_date_error_end[]" <?php self::input_default('options_date_error_end',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_date_error_end'); ?>
										<em>
											<?php _e('This error will show if the field is a date-range and no end date is selected.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.__('You must provide an end date.','em-pro').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_date_error_end', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Start date required','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_date_error_start[]" <?php self::input_default('options_date_error_start',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_date_error_start'); ?>
										<em>
											<?php _e('This error will show if the field is a date-range and no start date is selected.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.__('You must provide a start date.','em-pro').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_date_error_start', $field_values); ?>
								</div>
								<?php do_action('emp_forms_editor_date_options', $this, $field_values); ?>
							</div>
							<!-- time -->
							<div class="bct-time bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Time Range?','em-pro'); ?></div>
									<div class="bct-input">
										<input type="checkbox" <?php self::input_default('options_time_range',$field_values,'checkbox'); ?> value="1" />
										<input type="hidden" name="options_time_range[]" <?php self::input_default('options_time_range',$field_values); ?> /> 
										<em><?php _e('If selected, this field will also have an end-time.','em-pro'); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Separator','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_time_range_seperator[]" <?php self::input_default('options_time_range_seperator',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_time_range_seperator'); ?>
										<em><?php _e('This text will appear between the two date fields if this is a date range.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_time_range_seperator', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_time_tip[]" <?php self::input_default('options_time_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_time_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_time_tip', $field_values); ?>
								</div>
								<p><strong><?php _e('Error Messages','em-pro'); ?></strong></p>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Field Required','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_time_error[]" <?php self::input_default('options_time_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_time_error'); ?>
										<em>
											<?php _e('This error will show this field is required and no value is entered.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_time_error', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Incorrect Formatting','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_time_error_format[]" <?php self::input_default('options_time_error_format',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_time_error_format'); ?>
										<em>
											<?php _e('This error will show if an incorrect time format is used.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.__('Please use the time picker provided to select the appropriate time format.','em-pro').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_time_error_format', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('End time required','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_time_error_end[]" <?php self::input_default('options_time_error_end',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_time_error_end'); ?>
										<em>
											<?php _e('This error will show if the field is a time-range and no end time is selected.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.__('You must provide an end time.','em-pro').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_time_error_end', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Start time required','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_time_error_start[]" <?php self::input_default('options_time_error_start',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_time_error_start'); ?>
										<em>
											<?php _e('This error will show if the field is a time-range and no start time is selected.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.__('You must provide a start time.','em-pro').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_time_error_start', $field_values); ?>
								</div>
								<?php do_action('emp_forms_editor_time_options', $this, $field_values); ?>
							</div>
							<!-- checkboxes,radio -->
							<div class="bct-selection bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Options','em-pro'); ?></div>
									<div class="bct-input">
										<textarea name="options_selection_values[]"><?php self::input_default('options_selection_values',$field_values,'textarea'); ?></textarea>
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_selection_values'); ?>
										<em><?php _e('Available options, one per line.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_selection_values', $field_values, 'textarea'); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_selection_tip[]" <?php self::input_default('options_selection_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_selection_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_selection_tip', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_selection_error[]" <?php self::input_default('options_selection_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_selection_error'); ?>
										<em>
											<?php _e('This error will show if a value isn\'t chosen.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_selection_error', $field_values); ?>
								</div>
								<?php do_action('emp_forms_editor_selection_radio_options', $this, $field_values); ?>
							</div>
							<!-- checkbox -->
							<div class="bct-checkbox bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Checked by default?','em-pro'); ?></div>
									<div class="bct-input">
										<input type="checkbox" <?php self::input_default('options_checkbox_checked',$field_values,'checkbox'); ?> value="1" />
										<input type="hidden" name="options_checkbox_checked[]" <?php self::input_default('options_checkbox_checked',$field_values); ?> /> 
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_checkbox_tip[]" <?php self::input_default('options_checkbox_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_checkbox_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_checkbox_tip', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_checkbox_error[]" <?php self::input_default('options_checkbox_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_checkbox_error'); ?>
										<em>
											<?php _e('This error will show if this box is not checked.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_checkbox_error', $field_values); ?>
								</div>
								<?php do_action('emp_forms_editor_checkbox_options', $this, $field_values); ?>
							</div>
							<!-- text,textarea,email,name -->
							<div class="bct-text bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_text_tip[]" <?php self::input_default('options_text_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_text_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_text_tip', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Regex','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_text_regex[]" <?php self::input_default('options_text_regex',$field_values); ?> />
										<em><?php _e('By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: ','em-pro'); ?><code>^[0-9]+$</code></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_text_error[]" <?php self::input_default('options_text_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_text_error'); ?>
										<em>
											<?php _e('If the regex above does not match this error will be displayed.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_text_error', $field_values); ?>
								</div>
								<?php do_action('emp_forms_editor_text_options', $this, $field_values); ?>
							</div>	
							<?php endif; ?>
							<?php if($user_fields): ?>
							<!-- registration -->
							<div class="bct-registration bct-options" style="display:none;">
								<div class="bct-field">
									<div class="bct-label"><?php _e('Tip Text','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_reg_tip[]" <?php self::input_default('options_reg_tip',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_reg_tip'); ?>
										<em><?php _e('Will appear next to your field label as a question mark with a popup tip bubble.','em-pro'); ?></em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_reg_tip', $field_values); ?>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Regex','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_reg_regex[]" <?php self::input_default('options_reg_regex',$field_values); ?> />
										<em><?php _e('By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: ','em-pro'); ?><code>^[0-9]+$</code></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_reg_error[]" <?php self::input_default('options_reg_error',$field_values); ?> />
										<?php if( EM_ML::$is_ml ) $this->editor_ml_fields_trigger('options_reg_error'); ?>
										<em>
											<?php _e('If the regex above does not match this error will be displayed.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
									<?php if( EM_ML::$is_ml ) $this->editor_ml_fields('options_reg_error', $field_values); ?>
								</div>
							</div>
							<?php endif; ?>
							<?php if($captcha_fields): ?>
							<div class="bct-captcha bct-options" style="display:none;">
								<!-- captcha -->
								<?php 
									$recaptcha_url = "https://www.google.com/recaptcha/admin#list"; 
								?>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Site Key','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_captcha_key_pub[]" <?php self::input_default('options_captcha_key_pub',$field_values); ?> />
										<em><?php echo sprintf(__('Required, get your keys <a href="%s">here</a>','em-pro'),$recaptcha_url); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Secret Key','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_captcha_key_priv[]" <?php self::input_default('options_captcha_key_priv',$field_values); ?> />
										<em><?php echo sprintf(__('Required, get your keys <a href="%s">here</a>','em-pro'),$recaptcha_url); ?></em>
									</div>
								</div>
								<div class="bct-field">
									<div class="bct-label"><?php _e('Error Message','em-pro'); ?></div>
									<div class="bct-input">
										<input type="text" name="options_captcha_error[]" <?php self::input_default('options_captcha_error',$field_values); ?> />
										<em>
											<?php _e('This error will show if the captcha is not correct.','em-pro'); ?>
											<br /><?php _e('Default:','em-pro'); echo ' <code>'.sprintf(get_option('dbem_emp_booking_form_error_required'), '[FIELD]').'</code>'; ?>
										</em>
									</div>
								</div>
							</div>
							<?php endif; ?>
							<a class="button-secondary bct-options-toggle" href="#" style="display:none;"><?php esc_html_e('hide options','em-pro'); ?></a>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<p>
				<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('form_fields'); ?>" />
				<input type="hidden" name="form_action" value="form_fields" />
				<input type="hidden" name="form_name" value="<?php echo $this->form_name; ?>" />
				<input type="button" value="<?php _e('Add booking field','em-pro'); ?>" class="booking-form-custom-field-add button-secondary">
				<input type="submit" name="events_update" value="<?php _e ( 'Save Form', 'em-pro' ); ?> &raquo;" class="button-primary" />
			</p>
		</form>
		<?php
	}
	
	function editor_ml_fields( $id, $field_values, $type = 'text' ){
		$orig_value = !empty($field_values[$id]) ? $field_values[$id]:'';
		?>
		<div class="bct-options bct-ml bct-ml-<?php echo esc_attr($id); ?>" style="display:none;">
			<?php foreach( EM_ML::get_langs() as $lang => $lang_name ) : ?>
				<?php if( $lang != EM_ML::$wplang ): ?>
				<?php
				$value = !empty($field_values['ml'][$id][$lang]) ? $field_values['ml'][$id][$lang]:'';
				?>
				<div class="bct-field">
					<div class="bct-label"><?php echo $lang_name; ?></div>
					<div class="bct-input">
						<?php if( $type == 'textarea') : ?>
						<textarea name="ml[<?php echo esc_attr($id); ?>][<?php echo $lang ?>][]" placeholder="<?php echo esc_attr($orig_value); ?>" /><?php echo esc_html($value); ?></textarea><br />
						<?php else: ?>
						<input name="ml[<?php echo esc_attr($id); ?>][<?php echo $lang ?>][]" type="text" placeholder="<?php echo esc_attr($orig_value); ?>" value="<?php echo esc_attr($value); ?>" /><br />
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>
			<?php endforeach; ?>
			<em><?php esc_html_e('If translations are left blank, the default value will be used.','events-manager') ?></em>
		</div>
		<?php
	}
	
	function editor_ml_fields_trigger( $id ){
		?>
		<span class="em-translatable bc-translatable dashicons dashicons-admin-site" rel="bct-ml-<?php echo esc_attr($id); ?>"></span>
		<?php
	}
	
	function editor_save(){
		//Update Values
		return update_option('em_booking_form_fields',$this->form_fields);
	}
	
	function editor_get_post(){
		if( !empty($_REQUEST['form_action']) && $_REQUEST['form_action'] == 'form_fields' && wp_verify_nonce($_REQUEST['_wpnonce'], 'form_fields') ){
			//Booking form fields
			$fields_map = self::get_fields_map();
			//extract request info back into item lists, but first assign fieldids to new items and sanitize old ones
			$field_ids = array();
			foreach( $_REQUEST['fieldid'] as $fieldid_key => $fieldid ){
				if( !$this->is_user_form && $_REQUEST['type'][$fieldid_key] == 'name' ){ //name field
					$_REQUEST['fieldid'][$fieldid_key] = 'user_name';
				}elseif( !$this->is_user_form && array_key_exists($_REQUEST['type'][$fieldid_key], $this->user_fields) ){ //other fields
					$_REQUEST['fieldid'][$fieldid_key] = $_REQUEST['type'][$fieldid_key];
				}else{
					if( empty($fieldid) ){
						$_REQUEST['fieldid'][$fieldid_key] = sanitize_title($_REQUEST['label'][$fieldid_key]); //assign unique id
					}
					//check for duplicate IDs and if they exist, change the latter one to avoid conflicts
					if( in_array($_REQUEST['fieldid'][$fieldid_key], $field_ids) ){
						$suffix = 2;
						$field_id_raw = $_REQUEST['fieldid'][$fieldid_key];
						if( preg_match('/_([0-9]+)$/', $field_id_raw, $suffix_match) ){
							$field_id_raw = str_replace($suffix_match[0], '', $field_id_raw);
							$suffix = $suffix_match[1] + 1;
						}
						while( in_array($field_id_raw.'_'.$suffix, $field_ids) ) $suffix++;
						$_REQUEST['fieldid'][$fieldid_key] = $field_id_raw.'_'.$suffix;
					}
				}
				$_REQUEST['fieldid'][$fieldid_key] = $field_ids[] = str_replace('.','_',$_REQUEST['fieldid'][$fieldid_key]);
			}
			//get field values
			global $allowedposttags;
			$this->form_fields = array();
			foreach( $_REQUEST as $key => $value){
				if( is_array($value) && in_array($key,$fields_map) ){
					foreach($value as $item_index => $item_value){
						if( !empty($_REQUEST['fieldid'][$item_index]) ){
							$item_value = preg_replace('/  +/', ' ', stripslashes(wp_kses($item_value, $allowedposttags)));
							$this->form_fields[$_REQUEST['fieldid'][$item_index]][$key] = $item_value;
						}
					}
				}
			}
			//ML Saving
			if( EM_ML::$is_ml && !empty($_REQUEST['ml']) && is_array($_REQUEST['ml']) ){
				foreach( $_REQUEST['ml'] as $fieldid_key => $fieldid_langs ){
					if( is_array($fieldid_langs) && in_array($fieldid_key,$fields_map) ){
						foreach( EM_ML::get_langs() as $lang => $lang_name ){
							if( !empty($fieldid_langs[$lang]) && is_array($fieldid_langs[$lang]) ){
								foreach($fieldid_langs[$lang] as $item_index => $item_value){
									if( empty($item_value) ) continue;
									$item_value = preg_replace('/(^[ \t]+|[ \t]+$)/', '', $item_value);
									$item_value = preg_replace('/('.PHP_EOL.'[ \t]+|[ \t]+'.PHP_EOL.')/', PHP_EOL, $item_value);
									if( empty($item_value) ) continue;
									$item_value = preg_replace('/  +/', ' ', stripslashes(wp_kses($item_value, $allowedposttags)));
									$this->form_fields[$_REQUEST['fieldid'][$item_index]]['ml'][$fieldid_key][$lang] = $item_value;
								}
							}
						}
					}
				}
			}
			return true;
		}
		return false;
	}

}