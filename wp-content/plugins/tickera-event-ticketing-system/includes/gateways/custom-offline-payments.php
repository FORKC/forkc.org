<?php
/*
  Custom Offline Payments Gateway
 */

class TC_Gateway_Custom_Offline_Payments extends TC_Gateway_API {

    var $plugin_name = 'custom_offline_payments';
    var $admin_name = '';
    var $public_name = '';
    var $method_img_url = '';
    var $admin_img_url = '';
    var $force_ssl = false;
    var $ipn_url;
    var $automatically_activated = false;
    var $skip_payment_screen = false;

    //Support for older payment gateway API
    function on_creation() {
        $this->init();
    }

    function init() {
        global $tc;

        $this->skip_payment_screen = apply_filters($this->plugin_name . '_skip_payment_screen', $this->skip_payment_screen);
        $this->admin_name = $this->get_option('admin_name', __('Offline Payment', 'tc'));
        $this->public_name = $this->get_option('public_name', __('Cash on Delivery', 'tc'));

        $this->method_img_url = apply_filters('tc_gateway_method_img_url', $tc->plugin_url . 'images/gateways/custom-offline-payments.png', $this->plugin_name);
        $this->admin_img_url = apply_filters('tc_gateway_admin_img_url', $tc->plugin_url . 'images/gateways/small-custom-offline-payments.png', $this->plugin_name);

        add_action('tc_order_created', array(&$this, 'send_payment_instructions'), 10, 5);
        add_filter($this->plugin_name . '_instructions', array(&$this, 'modify_instruction_message'), 10, 2);
    }

    function payment_form($cart) {
        global $tc;
        return $this->get_option('info');
    }

    function modify_instruction_message($message, $order_id) {
        if (!is_int($order_id)) {
            $order = tc_get_order_id_by_name($order_id);
            $order = new TC_Order($order->ID);
        } else {
            $order = new TC_Order($order_id);
        }

        $payment_info = get_post_meta($order->id, 'tc_payment_info', true);
        $cart_info = get_post_meta($order->id, 'tc_cart_info', true);
        $buyer_name = $cart_info['buyer_data']['first_name_post_meta'] . ' ' . $cart_info['buyer_data']['last_name_post_meta'];

        $placeholders = array('ORDER_ID', 'ORDER_TOTAL', 'BUYER_NAME');
        $placeholder_values = array(strtoupper($order->details->post_title), apply_filters('tc_cart_currency_and_format', $payment_info['total']), $buyer_name);

        $message = str_replace($placeholders, $placeholder_values, $message);

        return $message;
    }

    function send_payment_instructions($order_id, $status, $cart_contents, $cart_info, $payment_info) {
        global $tc, $order_instructions_sent;

        if ($payment_info['gateway_private_name'] == $this->admin_name) {
            $send_instructions = $this->get_option('instructions_email', 'no') == 'yes';

            if ($send_instructions == 'yes' && $status == 'order_received') {
                add_filter('wp_mail_content_type', 'set_content_type');
                add_filter('wp_mail_from', 'client_email_from_email', 999);
                add_filter('wp_mail_from_name', 'client_email_from_name', 999);

                $client_headers = '';
                $to = $this->buyer_info('email');
                $message = apply_filters($this->plugin_name . '_instructions', $this->get_option('instructions'), $order_id);
                $subject = $this->get_option('instructions_email_subject');

                if ($order_instructions_sent !== $this->buyer_info('email')) {
                    wp_mail($to, stripslashes($subject), apply_filters('tc_order_created_client_email_message', stripcslashes(wpautop($message))), apply_filters('tc_order_created_client_email_headers', $client_headers));
                    $order_instructions_sent = $this->buyer_info('email');
                }
            }
        }
    }

    function process_payment($cart) {
        global $tc;

        tc_final_cart_check($cart);
        
        $this->maybe_start_session();
        $this->save_cart_info();

        $order_id = $tc->generate_order_id();

        $payment_info = array();
        $payment_info['currency'] = $tc->get_cart_currency();
        $payment_info = $this->save_payment_info($payment_info);

        $order = $tc->create_order($order_id, $this->cart_contents(), $this->cart_info(), $payment_info, false);

        ob_start();
        @wp_redirect($tc->get_confirmation_slug(true, $order_id));
        tc_js_redirect($tc->get_confirmation_slug(true, $order_id));
        exit;
    }

    function order_confirmation($order, $payment_info = '', $cart_info = '') {
        global $tc;

        $tc_payment_info = isset($_SESSION['tc_payment_info']) ? $_SESSION['tc_payment_info'] : $payment_info;

        $total = $tc_payment_info['total'];

        $automatic_status = $this->get_option('automatic_status');

        $paid = false;

        if ($total > 0) {//get default status for 100% discount and/or free orders
            if ($automatic_status == 'order_paid') {
                $paid = true;
            } else {
                $paid = false;
            }
        }

        $order = tc_get_order_id_by_name($order);
        $tc->update_order_payment_status($order->ID, $paid);
    }

    function order_confirmation_message($order, $cart_info = '') {
        global $tc;

        $order = tc_get_order_id_by_name($order);

        $order = new TC_Order($order->ID);

        $content = '';

        if ($order->details->post_status == 'order_received') {
            $content .= '<p>' . sprintf(__('Your payment via %s for this order totaling <strong>%s</strong> is not yet complete.', 'tc'), $this->public_name, apply_filters('tc_cart_currency_and_format', $order->details->tc_payment_info['total'])) . '</p>';
            $content .= '<p>' . __('Current order status:', 'tc') . ' <strong>' . __('Pending Payment', 'tc') . '</strong></p>';
        } else if ($order->details->post_status == 'order_fraud') {
            $content .= '<p>' . __('Your payment is under review. We will back to you soon.', 'tc') . '</p>';
        } else if ($order->details->post_status == 'order_paid') {
            $content .= '<p>' . sprintf(__('Your payment via %s for this order totaling <strong>%s</strong> is complete.', 'tc'), $this->public_name, apply_filters('tc_cart_currency_and_format', $order->details->tc_payment_info['total'])) . '</p>';
        } else if ($order->details->post_status == 'order_cancelled') {
            $content .= '<p>' . sprintf(__('Your payment via %s for this order totaling <strong>%s</strong> is cancelled.', 'tc'), $this->public_name, apply_filters('tc_cart_currency_and_format', $order->details->tc_payment_info['total'])) . '</p>';
        }

        $content = apply_filters('tc_order_confirmation_message_content_' . $this->plugin_name, $content);
        $content = apply_filters('tc_order_confirmation_message_content', $content, $order);

        $content .= '<br /><br />' . apply_filters($this->plugin_name . '_instructions', $tc->get_setting('gateways->custom_offline_payments->instructions'), $order->details->ID);

        $tc->remove_order_session_data();
        $tc->maybe_skip_confirmation_screen($this, $order);

        return stripcslashes(wpautop($content));
    }

    function gateway_admin_settings($settings, $visible) {
        global $tc;
        ?>
        <div id="<?php echo esc_attr($this->plugin_name); ?>" class="postbox" <?php echo (!$visible ? 'style="display:none;"' : ''); ?>>
            <h3><span><?php printf(__('%s Settings', 'tc'), wp_unslash($this->admin_name)); ?></span>
                <span class="description"><?php _e('Track offline / custom payments (Cash on Delivery, Money Orders, Bank Deposits, Cheques etc.) manually.', 'tc') ?></span>
            </h3>
            <div class="inside">
                <?php
                $fields = array(
                    'public_name' => array(
                        'title' => __('Public Method Name', 'tc'),
                        'type' => 'text',
                        'description' => __('Insert public name for this payment method which will be visible to buyers', 'tc'),
                        'default' => $this->public_name
                    ),
                    'admin_name' => array(
                        'title' => __('Admin Method Name', 'tc'),
                        'type' => 'text',
                        'description' => __('Insert admin name for this payment method which will be visible within the administration panel (Orders screen etc.)', 'tc'),
                        'default' => $this->admin_name
                    ),
                    'info' => array(
                        'title' => __('Payment Method Info', 'tc'),
                        'type' => 'wp_editor',
                        'description' => __('Information about the payment method which will be visible to user upon choosing this payment method.', 'tc')
                    ),
                    'instructions' => array(
                        'title' => __('Payment Instructions', 'tc'),
                        'type' => 'wp_editor',
                        'description' => __('Your customers who checkout using the custom offline payment method will be given a set of instructions (set by you) to complete the purchase process immediately after checkout completion. Available placeholders: ORDER_ID, ORDER_TOTAL, BUYER_NAME', 'tc')
                    ),
                    'instructions_email' => array(
                        'title' => __('E-mail Instructions', 'tc'),
                        'type' => 'select',
                        'options' => array(
                            'no' => __('No', 'tc'),
                            'yes' => __('Yes', 'tc')
                        ),
                        'default' => 'no',
                        'description' => __('Send an email with the payment instructions to a customer upon creating an order. The e-mail will be sent only if status of a order is "Order Received".', 'tc')
                    ),
                    'instructions_email_subject' => array(
                        'title' => __('Instructions E-mail Subject', 'tc'),
                        'type' => 'text',
                        'default' => __('Payment Instructions', 'tc')
                    ),
                    'automatic_status' => array(
                        'title' => __('Automatic Payment Status', 'tc'),
                        'type' => 'select',
                        'options' => array(
                            'order_received' => __('Order Received', 'tc'),
                            'order_paid' => __('Order Paid', 'tc')
                        ),
                        'default' => 'order_received',
                        'description' => __('Automatic payment status which will be set for all custom offline payment orders.', 'tc')
                    ),
                    'admin_gateway' => array(
                        'title' => __('Visible To Admins Only', 'tc'),
                        'type' => 'select',
                        'options' => array(
                            'no' => __('No', 'tc'),
                            'yes' => __('Yes', 'tc')
                        ),
                        'default' => 'no',
                        'description' => __('Mark yes if you want this payment gateway to be only visible to admins.', 'tc')
                    ),
                );

                $form = new TC_Form_Fields_API($fields, 'tc', 'gateways', $this->plugin_name);
                ?>
                <table class="form-table">
                    <?php $form->admin_options(); ?>
                </table>

            </div>
        </div>
        <?php
    }

}

tc_register_gateway_plugin('TC_Gateway_Custom_Offline_Payments', 'custom_offline_payments', __('Offline Payments', 'tc'));
?>