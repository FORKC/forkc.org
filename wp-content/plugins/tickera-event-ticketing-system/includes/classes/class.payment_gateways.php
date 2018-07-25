<?php

/*
  Payment Gateway API
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('TC_Gateway_API')) {

    class TC_Gateway_API {

        var $plugin_name = '';
        var $admin_name = '';
        var $public_name = '';
        var $method_img_url = '';
        var $admin_img_url = '';
        var $force_ssl = false;
        var $ipn_url;

        function cart_items($sign = ', ') {
            $cart_contents = $this->cart_contents();
            $items = array();

            foreach ($cart_contents as $item_id => $qty) {
                $ticket = new TC_Ticket($item_id);
                $items[] = apply_filters('tc_cart_item_line', $ticket->details->post_title . ' x ' . $qty, $item_id, $qty, $this);
            }

            $items_result = apply_filters('tc_cart_items_display', implode(apply_filters('tc_cart_items_sign', $sign), $items));

            if (isset($_SESSION['tc_order']) && apply_filters('tc_cart_items_prepend_order_id', false)) {
                $items_result = sprintf(__('Order #%s', 'tc'), $_SESSION['tc_order']) . ': ' . $items_result;
            }

            return apply_filters('tc_cart_items_display_result', $items_result);
        }

        function get_option($option_name = '', $default_value = '', $gateway_name = false) {
            $settings = get_option('tc_settings');
            if ($gateway_name == false) {
                $gateway_name = $this->plugin_name;
            }
            $value = isset($settings['gateways'][$gateway_name][$option_name]) ? (is_string($settings['gateways'][$gateway_name][$option_name]) ? (trim($settings['gateways'][$gateway_name][$option_name])) : $settings['gateways'][$gateway_name][$option_name]) : trim($default_value);
            return apply_filters('tc_gateway_option_value', $value, $gateway_name, $option_name, $default_value);
        }

        function get_global_currencies() {
            $settings = get_option('tc_settings');
            $currencies = isset($settings['gateways']['currencies']) ? $settings['gateways']['currencies'] : array();
            return $currencies;
        }

        function add_error($error = '', $place = '') {
            global $tc;
            $this->maybe_start_session();
            $_SESSION['tc_gateway_error'] = $error;
            wp_redirect($tc->get_payment_slug(true));
            exit;
        }

        function field_name($field_name = '', $gateway_name = false) {
            if ($gateway_name == false) {
                $gateway_name = $this->plugin_name;
            }
            return esc_attr('tc[gateways][' . $gateway_name . '][' . $field_name . ']');
        }

        function on_creation() {
            
        }

        function init() {
            
        }

        function payment_form($cart) {
            
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

            $tc->remove_order_session_data();
            $tc->maybe_skip_confirmation_screen($this, $order);
            return $content;
        }

        function maybe_start_session() {
            if (!session_id()) {
                session_start();
            }

            if (!isset($_SESSION)) {
                session_start();
            }
        }

        function is_payment_page() {
            global $wp;

            if (isset($wp->query_vars) && (array_key_exists('page_payment', $wp->query_vars) || (isset($wp->query_vars['pagename']) && preg_match('/' . tc_get_payment_page_slug() . '/', $wp->query_vars['pagename'], $matches, PREG_OFFSET_CAPTURE, 3)) || (isset($wp->query_vars['pagename']) && $wp->query_vars['pagename'] == tc_get_payment_page_slug()))) {
                return true;
            } else {
                return false;
            }
        }

        function is_active() {
            $settings = get_option('tc_settings');
            if (!isset($settings['gateways']['active']) || !is_array($settings['gateways']['active'])) {
                $settings['gateways']['active'] = array();
            }

            if (in_array($this->plugin_name, $settings['gateways']['active'])) {
                return true;
            } else {
                return false;
            }
        }

        function subtotal() {
            $this->maybe_start_session();
            return isset($_SESSION['tc_cart_subtotal']) ? $_SESSION['tc_cart_subtotal'] : 0;
        }

        function cart_info() {
            $this->maybe_start_session();
            return isset($_SESSION['cart_info']) ? $_SESSION['cart_info'] : array();
        }

        function buyer_info($part) {
            $this->maybe_start_session();
            if ($part == 'full_name') {
                $buyer_first_name = isset($_SESSION['cart_info']['buyer_data']['first_name_post_meta']) ? stripcslashes($_SESSION['cart_info']['buyer_data']['first_name_post_meta']) : '';
                $buyer_last_name = isset($_SESSION['cart_info']['buyer_data']['last_name_post_meta']) ? stripcslashes($_SESSION['cart_info']['buyer_data']['last_name_post_meta']) : '';
                $buyer_full_name = $buyer_first_name . ' ' . $buyer_last_name;
                return $buyer_full_name;
            } else {
                return isset($_SESSION['cart_info']['buyer_data'][$part . '_post_meta']) ? stripcslashes($_SESSION['cart_info']['buyer_data'][$part . '_post_meta']) : '';
            }
        }

        function cart_contents() {
            global $tc;
            return $tc->get_cart_cookie();
        }

        function save_cart_info() {
            $this->maybe_start_session();
            $_SESSION['cart_info']['gateway'] = $this->plugin_name;
            $_SESSION['cart_info']['gateway_admin_name'] = $this->admin_name;
            $_SESSION['cart_info']['gateway_class'] = get_class($this);
        }

        function save_payment_info($payment_info_new = array()) {
            global $tc;

            $payment_info = array();
            $payment_info['gateway_public_name'] = $this->public_name;
            $payment_info['gateway_private_name'] = $this->admin_name;
            $payment_info['method'] = $this->admin_name;
            $payment_info['total'] = $this->total();
            $payment_info['subtotal'] = $this->subtotal();
            $payment_info['fees_total'] = $this->total_fees();
            $payment_info['tax_total'] = $this->total_taxes();
            $payment_info['currency'] = isset($this->currency) ? $this->currency : $tc->get_cart_currency();

            if (!empty($payment_info_new)) {
                foreach ($payment_info_new as $payment_info_key => $payment_info_value) {
                    $payment_info[$payment_info_key] = $payment_info_value;
                }
            }

            $_SESSION['tc_payment_info'] = $payment_info;

            return $payment_info;
        }

        function total() {
            $this->maybe_start_session();
            $discounted_total = isset($_SESSION['discounted_total']) ? $_SESSION['discounted_total'] : '';

            if (isset($discounted_total) && is_numeric($discounted_total)) {
                $total = round($discounted_total, 2);
            } else {
                $total = round($cart_total, 2);
            }

            return $total;
        }

        function total_fees() {
            $this->maybe_start_session();
            return isset($_SESSION['tc_total_fees']) ? $_SESSION['tc_total_fees'] : 0;
        }

        function total_taxes() {
            $this->maybe_start_session();
            return isset($_SESSION['tc_tax_value']) ? $_SESSION['tc_tax_value'] : 0;
        }

        function process_payment($cart) {
            wp_die(__("You must override the process_payment() method in your {$this->admin_name} payment gateway plugin!", 'tc'));
        }

        function order_confirmation_email($msg, $order) {
            return $msg;
        }

        function gateway_admin_settings($settings, $visible) {
            
        }

        function ipn() {
            
        }

        function order_confirmation($order, $payment_info = '', $cart_info = '') {
            
        }

        function _generate_ipn_url() {
            global $tc;
            if (empty($GLOBALS['wp_rewrite'])) {
                $GLOBALS['wp_rewrite'] = new WP_Rewrite();
            }
            $this->ipn_url = trailingslashit($tc->get_payment_gateway_return_slug(true)) . '?payment_gateway_return=' . $this->plugin_name;
        }

        function _generate_cancel_url() {
            global $tc;
            if (empty($GLOBALS['wp_rewrite'])) {
                $GLOBALS['wp_rewrite'] = new WP_Rewrite();
            }

            if ($tc->active_payment_gateways() == 1 && $this->skip_payment_screen == true) {
                $this->cancel_url = $tc->get_cart_slug(true) . '?' . $this->plugin_name . '_cancel'; //$tc->get_cart_slug( true ) . '?' . $this->plugin_name . '_cancel';
            } else {
                $this->cancel_url = $tc->get_payment_slug(true) . '?' . $this->plugin_name . '_cancel';
            }
            $this->cancel_slug = $this->plugin_name . '_cancel';
        }

        function _generate_failed_url() {
            global $tc;
            if (empty($GLOBALS['wp_rewrite'])) {
                $GLOBALS['wp_rewrite'] = new WP_Rewrite();
            }
            if ($tc->active_payment_gateways() == 1 && $this->skip_payment_screen == true) {
                $this->failed_url = $tc->get_cart_slug(true) . '?' . $this->plugin_name . '_failed';
            } else {
                $this->failed_url = $tc->get_payment_slug(true) . '?' . $this->plugin_name . '_failed';
            }
            $this->failed_slug = $this->plugin_name . '_failed';
        }

        function _checkout_confirmation_hook() {
            global $wp_query, $tc;

            if ($wp_query->query_vars['pagename'] == 'cart') {
                if (isset($wp_query->query_vars['checkoutstep']) && $wp_query->query_vars['checkoutstep'] == 'confirmation')
                    do_action('tc_checkout_payment_pre_confirmation_' . $_SESSION['tc_payment_method'], $tc->get_order($_SESSION['tc_order']));
            }
        }

        function isSSL() {
            if (!empty($_SERVER['https'])) {
                return true;
            }

            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                return true;
            }

            return false;
        }

        function force_ssl() {
            if ($this->is_payment_page() && $this->force_ssl && !is_ssl() && $this->is_active()) {
                if (!$this->isSSL()) {
                    wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                    exit();
                }
            }
        }

        function show_cart_errors() {
            if (isset($_GET[$this->cancel_slug]) || isset($_GET[$this->failed_slug])) {
                add_filter('tc_cart_errors', array(&$this, 'cart_error_content'));
            }
        }

        function cart_error_content($content) {
            $content = __('Your transaction has been canceled.', 'tc');
            return $content;
        }

        function __construct() {

            $this->_generate_ipn_url();
            $this->_generate_cancel_url();
            $this->_generate_failed_url();

            $this->on_creation();
            $this->init();

            add_action('tc_gateway_settings', array(&$this, 'gateway_admin_settings'));
            add_action('tc_handle_payment_return_' . $this->plugin_name, array(&$this, 'ipn'));

            add_action('template_redirect', array(&$this, '_checkout_confirmation_hook'));
            add_filter('tc_checkout_confirm_payment_' . $this->plugin_name, array(&$this, 'confirm_payment_form'), 10, 2);
            add_action('tc_payment_confirm_' . $this->plugin_name, array(&$this, 'process_payment'), 10, 2);
            add_filter('tc_order_notification_' . $this->plugin_name, array(&$this, 'order_confirmation_email'), 10, 2);
            add_action('tc_checkout_payment_pre_confirmation_' . $this->plugin_name, array(&$this, 'order_confirmation'));
            add_filter('tc_checkout_payment_confirmation_' . $this->plugin_name, array(&$this, 'order_confirmation_message'), 10, 2);

            add_action('template_redirect', array(&$this, 'force_ssl'));
            add_action('init', array(&$this, 'show_cart_errors'));
        }

    }

}

/**
 * Use this function to register your gateway plugin class
 *
 * @param string $class_name - the case sensitive name of your plugin class
 * @param string $plugin_name - the sanitized private name for your plugin
 * @param string $admin_name - pretty name of your gateway, for the admin side.
 * @param bool $global optional - whether the gateway supports global checkouts
 */
function tc_register_gateway_plugin($class_name, $plugin_name, $admin_name, $global = false, $demo = false) {
    global $tc_gateway_plugins;

    if (!is_array($tc_gateway_plugins)) {
        $tc_gateway_plugins = array();
    }

    if (class_exists($class_name)) {
        $tc_gateway_plugins[$plugin_name] = array($class_name, $admin_name, $global, $demo);
    } else {
        return false;
    }
}

?>