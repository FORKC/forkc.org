<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('TC_Discounts')) {

    class TC_Discounts {

        var $form_title = '';
        var $discount_message = '';
        var $valid_admin_fields_type = array('text', 'textarea', 'image', 'function');

        function __construct() {
            $this->form_title = __('Discount Codes', 'tc');
            $this->valid_admin_fields_type = apply_filters('tc_valid_admin_fields_type', $this->valid_admin_fields_type);
        }

        function TC_Discounts() {
            $this->__construct();
        }

        public static function max_discount($value, $total) {
            if ($value > $total) {
                $value = $total;
            }
            return $value;
        }

        function unset_discount() {
            global $tc;
            $tc->start_session();
            $_SESSION['tc_discount_code'] = '';
            $_SESSION['discount_value_total'] = 0;
        }

        function get_discount_total_by_order($order_id) {
            global $tc;
            $order = new TC_Order($order_id);
            $fees_total = isset($order->details->tc_payment_info['fees_total']) ? $order->details->tc_payment_info['fees_total'] : 0;
            $tax_total = isset($order->details->tc_payment_info['tax_total']) ? $order->details->tc_payment_info['tax_total'] : 0;
            $subtotal = isset($order->details->tc_payment_info['subtotal']) ? $order->details->tc_payment_info['subtotal'] : 0;
            $total = isset($order->details->tc_payment_info['total']) ? $order->details->tc_payment_info['total'] : 0;

            $discount_total = $subtotal - ($total - $tax_total - $fees_total);

            $order_discount_code = get_post_meta($order_id, 'tc_discount_code', true);
            if (empty($order_discount_code)) {
                return 0;
            } else {
                return $discount_total;
            }
        }

        public static function discount_used_times($discount_code) {

            $discount_used_times = 0;

            $orders_args = array(
                'posts_per_page' => -1,
                'meta_key' => 'tc_discount_code',
                'meta_value' => $discount_code,
                'post_type' => 'tc_orders',
                'post_status' => 'any'
            );

            $discount_object = get_page_by_title($discount_code, OBJECT, 'tc_discounts');
            $discount_object = new TC_Discount($discount_object->ID);
            $discount_availability = $discount_object->details->discount_availability;
            $discount_availability = explode(',', $discount_availability);

            $discount_usage_limit = $discount_object->details->usage_limit;

            //1. Get all orders where this discount is used
            $orders_with_the_discount_code = get_posts($orders_args);

            //2.Get all ticket instances (tickets / attendees) from the order
            foreach ($orders_with_the_discount_code as $order) {
                $tickets_instances_args = array(
                    'posts_per_page' => -1,
                    'post_parent' => $order->ID,
                    'post_type' => 'tc_tickets_instances',
                    'post_status' => 'any'
                );
                $ticket_instances = get_posts($tickets_instances_args);

                foreach ($ticket_instances as $ticket_instance) {
                    $ticket_type_id = get_post_meta($ticket_instance->ID, 'ticket_type_id', true);

                    //3. check if the ticket type was affected by the discount code (discount code could be applied to the ticket type)
                    if ((is_array($discount_availability) && count($discount_availability) == 1 && in_array('', $discount_availability)) || !is_array($discount_availability) || in_array($ticket_type_id, $discount_availability)) {
                        $discount_used_times++;
                    }
                }
            }

            if ($discount_used_times > $discount_usage_limit && is_numeric($discount_usage_limit)) {
                $discount_used_times = $discount_usage_limit;
            }

            return $discount_used_times;
        }

        function discounted_cart_total($total = false, $discount_code = '') {
            global $tc, $discount, $discount_value_total, $init_total, $new_total;

            $cart_subtotal = 0;

            if (empty($discount)) {
                $discount = new TC_Discounts();
            }
            if (!$total) {

                $cart_contents = $tc->get_cart_cookie();

                foreach ($cart_contents as $ticket_type => $ordered_count) {
                    $ticket = new TC_Ticket($ticket_type);
                    $cart_subtotal = $cart_subtotal + (tc_get_ticket_price($ticket->details->ID) * $ordered_count);
                }

                if (!isset($_SESSION)) {
                    session_start();
                }

                $_SESSION['tc_cart_subtotal'] = $cart_subtotal;
            }

            $cart_contents = $tc->get_cart_cookie();

            $discount_value = 0;
            $current_date = current_time("Y-m-d H:i:s");
            if ($discount_code == '') {
                $discount_code = (isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '');
            }

            $discount_object = get_page_by_title($discount_code, OBJECT, 'tc_discounts');
   

            if (!empty($discount_object) && $discount_object->post_status == 'publish') {
                $discount_object = new TC_Discount($discount_object->ID);

                /*
                 * $discount_object->details->usage_limit
                 */

                if (is_numeric(trim($discount_object->id))) {//discount object is not empty means discount code is entered
                    if ($discount_object->details->expiry_date >= $current_date) {

                        $discount_availability = $discount_object->details->discount_availability;

                        $discount_availability = explode(',', $discount_availability);

                        $usage_limit = ($discount_object->details->usage_limit !== '' ? $discount_object->details->usage_limit : 9999999); //set "unlimited" if empty

                        $number_of_discount_uses = $this->discount_used_times($discount_code); //get real number of discount code uses

                        $discount_codes_available = (int) $usage_limit - (int) $number_of_discount_uses;


                        if ($discount_object->details->discount_type == 3) {//fixed per order
                            if($discount_codes_available > 0){
                                $discount_value = round($discount_object->details->discount_value, 2);
                            }else{
                                $discount->discount_message = __('Discount code invalid or expired', 'tc');
                                $this->unset_discount();
                            }
                        } else {

                            //Discount is available for all ticket types
                            if ((is_array($discount_availability) && count($discount_availability) == 1 && in_array('', $discount_availability)) || !is_array($discount_availability)) {
                                foreach ($cart_contents as $ticket_type_id => $ordered_count) {

                                    $ticket = new TC_Ticket($ticket_type_id);
                                    $ticket_price = tc_get_ticket_price($ticket->details->ID);

                                    $discount_value_per_each = ($discount_object->details->discount_type == 1 ? $discount_object->details->discount_value : round((($ticket_price / 100) * $discount_object->details->discount_value), 2) );

                                    $max_discount = ($ordered_count >= $discount_codes_available ? $discount_codes_available : $ordered_count);

                                    if ($max_discount > 0) {
                                        for ($i = 1; $i <= (int) $max_discount; $i++) {
                                            $discount_value = $discount_value + $discount_value_per_each;
                                            $number_of_discount_uses++;
                                            $discount_codes_available = $usage_limit - $number_of_discount_uses;
                                            //$max_discount				 = ($ordered_count >= $discount_codes_available ? $discount_codes_available : $ordered_count);
                                        }
                                    } else {
                                        if ($discount_value == 0) {
                                            $discount->discount_message = __('Discount code invalid or expired', 'tc');
                                        } else {
                                            $discount->discount_message = sprintf(__('Discount applied for %s item(s)', 'tc'), $number_of_discount_uses);
                                        }
                                    }

                                    $i = 1;
                                }
                            } else {
                                //Discount is available for selected (limited) ticket types
                                $is_in_cart = false;

                                foreach ($cart_contents as $ticket_type_id => $ordered_count) {
                                    if (is_array($discount_availability) && in_array($ticket_type_id, $discount_availability)) {
                                        $is_in_cart = true;
                                        break;
                                    }
                                }

                                if ($is_in_cart) {

                                    $discount_value = 0;

                                    foreach ($discount_availability as $ticket_id) {
                                        if (isset($cart_contents[$ticket_id])) {

                                            $ordered_count = $cart_contents[$ticket_id];

                                            $ticket = new TC_Ticket($ticket_id);
                                            $ticket_price = tc_get_ticket_price($ticket->details->ID);

                                            $discount_value_per_each = ($discount_object->details->discount_type == 1 ? $discount_object->details->discount_value : round((($ticket_price / 100) * $discount_object->details->discount_value), 2));

                                            $max_discount = ($ordered_count >= $discount_codes_available ? $discount_codes_available : $ordered_count);

                                            if ($max_discount > 0) {
                                                for ($i = 1; $i <= $max_discount; $i++) {

                                                    $discount_value = $discount_value + $discount_value_per_each;
                                                    $number_of_discount_uses++;
                                                    $discount_codes_available = ($discount_object->details->usage_limit == '' ? 99999 : $discount_object->details->usage_limit) - $number_of_discount_uses;
                                                    //$max_discount				 = ($ordered_count >= $discount_codes_available ? $discount_codes_available : $ordered_count);
                                                }
                                            } else {
                                                if ($discount_value == 0) {
                                                    $discount->discount_message = __('Discount code invalid or expired', 'tc');
                                                    $this->unset_discount();
                                                } else {
                                                    $discount->discount_message = sprintf(__('Discount applied for %s item(s)', 'tc'), $number_of_discount_uses);
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $this->unset_discount();
                                    $discount->discount_message = __("Discount code is not valid for the ticket type(s) in the cart.", 'tc');
                                }
                            }
                        }
                    } else {
                        $this->unset_discount();
                        $discount->discount_message = __('Discount code expired', 'tc');
                    }
                }
            } else {
                $this->unset_discount();
                $discount->discount_message = __('Discount code cannot be found', 'tc');
            }

            $discount_value_total = round($discount_value, 2);

            add_filter('tc_cart_discount', 'tc_cart_discount_value_total', 10, 0);

            if (!function_exists('tc_cart_discount_value_total')) {

                function tc_cart_discount_value_total() {
                    global $discount_value_total;
                    if (!isset($_SESSION)) {
                        session_start();
                    }

                    $total = $_SESSION['tc_cart_subtotal'];

                    $_SESSION['discount_value_total'] = TC_Discounts::max_discount(tc_minimum_total($discount_value_total), $total);

                    return TC_Discounts::max_discount($discount_value_total, $total);
                }

            }

            $init_total = $total;

            add_filter('tc_cart_subtotal', 'tc_cart_subtotal_minimum');

            if (!function_exists('tc_cart_subtotal_minimum')) {

                function tc_cart_subtotal_minimum() {
                    global $init_total;
                    if (!isset($_SESSION)) {
                        session_start();
                    }
                    return tc_minimum_total($_SESSION['tc_cart_subtotal']);
                }

            }

            if (!session_id()) {
                session_start();
            }

            $new_total = (isset($_SESSION['tc_cart_subtotal']) ? $_SESSION['tc_cart_subtotal'] : 0) - $discount_value;

            add_filter('tc_cart_total', 'tc_cart_total_minimum_total');

            if (!function_exists('tc_cart_total_minimum_total')) {

                function tc_cart_total_minimum_total() {
                    global $new_total;

                    if (!session_id()) {
                        session_start();
                    }

                    $_SESSION['tc_cart_total'] = tc_minimum_total($new_total);

                    return tc_minimum_total($new_total);
                }

            }

            if (((int) $new_total == (int) $total) || empty($total)) {
                
            } else {
                //$discount->discount_message = sprintf( __( 'Discount applied for %s item(s)', 'tc' ), $number_of_discount_uses );
                $discount->discount_message = __('Discount code applied.', 'tc');
                $_SESSION['tc_discount_code'] = $discount_code;
            }

            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['discounted_total'] = tc_minimum_total(apply_filters('tc_discounted_total', $new_total));
            $discounted_total = $new_total;


            /* add_filter( 'tc_discounted_fees_total', 'tc_discounted_fees_total' );
              if ( !function_exists( 'tc_discounted_fees_total' ) ) {

              function tc_discounted_fees_total( $fees ) {
              global $new_total;
              return $new_total;
              }

              } */
//return $discounted_total;
        }

        public static function discount_code_message($message) {
            global $discount;
            $message = $discount->discount_message;
            return $message;
        }

        function get_discount_fields($bulk = false) {

            $default_fields = array(
                /* array(
                  'field_name'		 => 'post_title',
                  'field_title'		 => __( 'Discount Code', 'tc' ),
                  'field_type'		 => 'text',
                  'field_description'	 => __( 'Discount Code, e.g. ABC123', 'tc' ),
                  'table_visibility'	 => true,
                  'post_field_type'	 => 'post_title'
                  ), */
                array(
                    'field_name' => 'discount_type',
                    'field_title' => __('Discount Type', 'tc'),
                    'field_type' => 'function',
                    'function' => 'tc_get_discount_types',
                    'field_description' => '',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'field_name' => 'discount_value',
                    'field_title' => __('Discount Value', 'tc'),
                    'field_type' => 'text',
                    'field_description' => __('For example: 9.99', 'tc'),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'number' => true,
                    'required' => true
                ),
                array(
                    'field_name' => 'discount_availability',
                    'field_title' => __('Discount Available for', 'tc'),
                    'field_type' => 'function',
                    'function' => 'tc_get_ticket_types',
                    'field_description' => 'Select ticket type(s)',
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
                array(
                    'field_name' => 'usage_limit',
                    'field_title' => __('Usage Limit', 'tc'),
                    'placeholder' => __('Unlimited', 'tc'),
                    'field_type' => 'text',
                    'field_description' => __('(optional) How many times this discount code can be used before it is void, e.g. 100', 'tc'),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta',
                    'number' => true
                ),
                array(
                    'field_name' => 'expiry_date',
                    'field_title' => __('Expiration Date', 'tc'),
                    'field_type' => 'text',
                    'field_description' => __('The date this discount will expire (24 hour format)', 'tc'),
                    'table_visibility' => true,
                    'post_field_type' => 'post_meta'
                ),
            );

            if ($bulk) {
                $first_field = array(
                    'field_name' => 'post_titles',
                    'field_title' => __('Discount Code', 'tc'),
                    'field_type' => 'textarea',
                    'field_description' => __('Discount Code, e.g. ABC123. <strong>One discount code per line</strong>.', 'tc'),
                    'table_visibility' => true,
                    'post_field_type' => 'post_title',
                );
            } else {
                $first_field = array(
                    'field_name' => 'post_title',
                    'field_title' => __('Discount Code', 'tc'),
                    'field_type' => 'text',
                    'field_description' => __('Discount Code, e.g. ABC123', 'tc'),
                    'table_visibility' => true,
                    'post_field_type' => 'post_title',
                    'required' => true
                );
            }

            array_unshift($default_fields, $first_field);

            return apply_filters('tc_discount_fields', $default_fields);
        }

        function get_columns() {
            $fields = $this->get_discount_fields();
            $results = search_array($fields, 'table_visibility', true);

            $columns = array();

            $columns['ID'] = __('ID', 'tc');

            foreach ($results as $result) {
                $columns[$result['field_name']] = $result['field_title'];
            }

            $columns['edit'] = __('Edit', 'tc');
            $columns['delete'] = __('Delete', 'tc');

            return $columns;
        }

        function check_field_property($field_name, $property) {
            $fields = $this->get_discount_fields();
            $result = search_array($fields, 'field_name', $field_name);
            return isset($result[0]['post_field_type']) ? $result[0]['post_field_type'] : '';
        }

        function is_valid_discount_field_type($field_type) {
            if (in_array($field_type, $this->valid_admin_fields_type)) {
                return true;
            } else {
                return false;
            }
        }

        function add_new_discount() {
            global $user_id, $post;

            if (isset($_POST['add_new_discount'])) {

                $metas = array();

                foreach ($_POST as $field_name => $field_value) {
                    if (preg_match('/_post_title/', $field_name)) {
                        $title = sanitize_text_field($field_value);
                    }

                    if (preg_match('/_post_excerpt/', $field_name)) {
                        $excerpt = sanitize_text_field($field_value);
                    }

                    if (preg_match('/_post_content/', $field_name)) {
                        $content = sanitize_text_field($field_value);
                    }

                    if (preg_match('/_post_meta/', $field_name)) {
                        if (is_array($field_value)) {
                            $field_value = implode(',', $field_value);
                        }
                        $metas[sanitize_key(str_replace('_post_meta', '', $field_name))] = sanitize_text_field($field_value);
                    }

                    do_action('tc_after_discount_post_field_type_check');
                }

                $metas = apply_filters('discount_code_metas', $metas);

                $arg = array(
                    'post_author' => (int) $user_id,
                    'post_excerpt' => (isset($excerpt) ? $excerpt : ''),
                    'post_content' => (isset($content) ? $content : ''),
                    'post_status' => 'publish',
                    'post_title' => (isset($title) ? $title : ''),
                    'post_type' => 'tc_discounts',
                );

                if (isset($_POST['post_id'])) {
                    $arg['ID'] = (int) $_POST['post_id']; //for edit 
                }

                $post_id = @wp_insert_post($arg, true);

//Update post meta
                if ($post_id !== 0) {
                    if (isset($metas)) {
                        foreach ($metas as $key => $value) {
                            update_post_meta($post_id, $key, $value);
                        }
                    }
                }

                return $post_id;
            }
        }

    }

}
?>
