<?php
/*
  2Checkout - Payment Gateway
 */

class TC_Gateway_2Checkout extends TC_Gateway_API {

    var $plugin_name = 'checkout';
    var $admin_name = '';
    var $public_name = '';
    var $method_img_url = '';
    var $admin_img_url = '';
    var $force_ssl = false;
    var $ipn_url;
    var $API_Username, $API_Password, $SandboxFlag, $returnURL, $API_Endpoint, $version, $currency, $locale;
    var $currencies = array();
    var $automatically_activated = false;
    var $skip_payment_screen = true;

    //Support for older payment gateway API
    function on_creation() {
        $this->init();
    }

    function init() {
        global $tc;

        $this->admin_name = __('2Checkout', 'tc');
        $this->public_name = __('2Checkout', 'tc');

        $this->method_img_url = apply_filters('tc_gateway_method_img_url', $tc->plugin_url . 'images/gateways/2checkout.png', $this->plugin_name);
        $this->admin_img_url = apply_filters('tc_gateway_admin_img_url', $tc->plugin_url . 'images/gateways/small-2checkout.png', $this->plugin_name);

        $this->currency = $this->get_option('currency', 'USD', '2checkout');
        $this->API_Username = $this->get_option('sid', '', '2checkout');
        $this->API_Password = $this->get_option('secret_word', '', '2checkout');
        $this->SandboxFlag = $this->get_option('mode', 'sandbox', '2checkout');

        $currencies = array(
            "AED" => __('AED - United Arab Emirates Dirham', 'tc'),
            "ARS" => __('ARS - Argentina Peso', 'tc'),
            "AUD" => __('AUD - Australian Dollar', 'tc'),
            "BRL" => __('BRL - Brazilian Real', 'tc'),
            "CAD" => __('CAD - Canadian Dollar', 'tc'),
            "CHF" => __('CHF - Swiss Franc', 'tc'),
            "DKK" => __('DKK - Danish Krone', 'tc'),
            "EUR" => __('EUR - Euro', 'tc'),
            "GBP" => __('GBP - British Pound', 'tc'),
            "HKD" => __('HKD - Hong Kong Dollar', 'tc'),
            "INR" => __('INR - Indian Rupee', 'tc'),
            "ILS" => __('ILS - Israeli New Shekel', 'tc'),
            "LTL" => __('LTL - Lithuanian Litas', 'tc'),
            "JPY" => __('JPY - Japanese Yen', 'tc'),
            "MYR" => __('MYR - Malaysian Ringgit', 'tc'),
            "MXN" => __('MXN - Mexican Peso', 'tc'),
            "NOK" => __('NOK - Norwegian Krone', 'tc'),
            "NZD" => __('NZD - New Zealand Dollar', 'tc'),
            "PHP" => __('PHP - Philippine Peso', 'tc'),
            "RON" => __('RON - Romanian New Leu', 'tc'),
            "RUB" => __('RUB - Russian Ruble', 'tc'),
            "SEK" => __('SEK - Swedish Krona', 'tc'),
            "SGD" => __('SGD - Singapore Dollar', 'tc'),
            "TRY" => __('TRY - Turkish Lira', 'tc'),
            "USD" => __('USD - U.S. Dollar', 'tc'),
            "ZAR" => __('ZAR - South African Rand', 'tc'),
            "AFN" => __('AFN - Afghan Afghani', 'tc'),
            "ALL" => __('ALL - Albanian Lek', 'tc'),
            "AZN" => __('AZN - Azerbaijani an Manat', 'tc'),
            "BSD" => __('BSD - Bahamian Dollar', 'tc'),
            "BDT" => __('BDT - Bangladeshi Taka', 'tc'),
            "BBD" => __('BBD - Barbados Dollar', 'tc'),
            "BZD" => __('BZD - Belizean dollar', 'tc'),
            "BMD" => __('BMD - Bermudian Dollar', 'tc'),
            "BOB" => __('BOB - Bolivian Boliviano', 'tc'),
            "BWP" => __('BWP - Botswana Pula', 'tc'),
            "BND" => __('BND - Brunei Dollar', 'tc'),
            "BGN" => __('BGN - Bulgarian Lev', 'tc'),
            "CLP" => __('CLP - Chilean Peso', 'tc'),
            "CNY" => __('CNY - Chinese Yuan Renminbi', 'tc'),
            "COP" => __('COP - Colombian Peso', 'tc'),
            "CRC" => __('CRC - Costa Rican Colon', 'tc'),
            "HRK" => __('HRK - Croatian Kuna', 'tc'),
            "CZK" => __('CZK - Czech Republic Koruna', 'tc'),
            "DOP" => __('DOP - Dominican Peso', 'tc'),
            "XCD" => __('XCD - East Caribbean Dollar', 'tc'),
            "EGP" => __('EGP - Egyptian Pound', 'tc'),
            "FJD" => __('FJD - Fiji Dollar', 'tc'),
            "GTQ" => __('GTQ - Guatemala Quetzal', 'tc'),
            "HNL" => __('HNL - Honduras Lempira', 'tc'),
            "HUF" => __('HUF - Hungarian Forint', 'tc'),
            "IDR" => __('IDR - Indonesian Rupiah', 'tc'),
            "JMD" => __('JMD - Jamaican Dollar', 'tc'),
            "KZT" => __('KZT - Kazakhstan Tenge', 'tc'),
            "KES" => __('KES - Kenyan Shilling', 'tc'),
            "LAK" => __('LAK - Laosian kip', 'tc'),
            "MMK" => __('MMK - Myanmar Kyat', 'tc'),
            "LBP" => __('LBP - Lebanese Pound', 'tc'),
            "LRD" => __('LRD - Liberian Dollar', 'tc'),
            "MOP" => __('MOP - Macanese Pataca', 'tc'),
            "MVR" => __('MVR - Maldiveres Rufiyaa', 'tc'),
            "MRO" => __('MRO - Mauritanian Ouguiya', 'tc'),
            "MUR" => __('MUR - Mauritius Rupee', 'tc'),
            "MAD" => __('MAD - Moroccan Dirham', 'tc'),
            "NPR" => __('NPR - Nepalese Rupee', 'tc'),
            "TWD" => __('TWD - New Taiwan Dollar', 'tc'),
            "NIO" => __('NIO - Nicaraguan Cordoba', 'tc'),
            "PKR" => __('PKR - Pakistan Rupee', 'tc'),
            "PGK" => __('PGK - New Guinea kina', 'tc'),
            "PEN" => __('PEN - Peru Nuevo Sol', 'tc'),
            "PLN" => __('PLN - Poland Zloty', 'tc'),
            "QAR" => __('QAR - Qatari Rial', 'tc'),
            "WST" => __('WST - Samoan Tala', 'tc'),
            "SAR" => __('SAR - Saudi Arabian riyal', 'tc'),
            "SCR" => __('SCR - Seychelles Rupee', 'tc'),
            "SBD" => __('SBD - Solomon Islands Dollar', 'tc'),
            "KRW" => __('KRW - South Korean Won', 'tc'),
            "LKR" => __('LKR - Sri Lanka Rupee', 'tc'),
            "CHF" => __('CHF - Switzerland Franc', 'tc'),
            "SYP" => __('SYP - Syrian Arab Republic Pound', 'tc'),
            "THB" => __('THB - Thailand Baht', 'tc'),
            "TOP" => __('TOP - Tonga Pa&#x27;anga', 'tc'),
            "TTD" => __('TTD - Trinidad and Tobago Dollar', 'tc'),
            "UAH" => __('UAH - Ukraine Hryvnia', 'tc'),
            "VUV" => __('VUV - Vanuatu Vatu', 'tc'),
            "VND" => __('VND - Vietnam Dong', 'tc'),
            "XOF" => __('XOF - West African CFA Franc BCEAO', 'tc'),
            "YER" => __('YER - Yemeni Rial', 'tc'),
        );

        $this->currencies = $currencies;
    }

    function payment_form($cart) {

    }

    function process_payment($cart) {
        global $tc;

        tc_final_cart_check($cart);
        
        $this->maybe_start_session();
        $this->save_cart_info();

        if ($this->SandboxFlag == 'sandbox') {
            $url = 'https://www.2checkout.com/checkout/purchase';
        } else {
            $url = 'https://www.2checkout.com/checkout/purchase';
        }

        $order_id = $tc->generate_order_id();

        $params = array();
        $params['total'] = $this->total();
        $params['sid'] = $this->API_Username;
        $params['cart_order_id'] = $order_id;
        $params['merchant_order_id'] = $order_id;
        $params['return_url'] = $tc->get_confirmation_slug(true, $order_id);
        $params['x_receipt_link_url'] = $tc->get_confirmation_slug(true, $order_id);
        $params['skip_landing'] = '1';
        $params['fixed'] = 'Y';
        $params['currency_code'] = $this->currency;
        $params['mode'] = '2CO';
        $params['card_holder_name'] = $this->buyer_info('full_name');
        $params['email'] = $this->buyer_info('email');

        if ($this->SandboxFlag == 'sandbox') {
            $params['demo'] = 'Y';
        }

        $params["li_0_type"] = "product";
        $params["li_0_name"] = $this->cart_items();
        $params["li_0_price"] = $this->total();
        $params["li_0_tangible"] = 'N';

        $param_list = array();

        foreach ($params as $k => $v) {
            $param_list[] = "{$k}=" . rawurlencode($v);
        }

        $param_str = implode('&', $param_list);

        $paid = false;

        $payment_info = $this->save_payment_info();

        $tc->create_order($order_id, $this->cart_contents(), $this->cart_info(), $payment_info, $paid);

        ob_start();
        @wp_redirect("{$url}?{$param_str}");
        tc_js_redirect("{$url}?{$param_str}");
        exit(0);
    }

    function order_confirmation($order, $payment_info = '', $cart_info = '') {
        global $tc;

        $total = $_REQUEST['total'];

        $hashSecretWord = $this->get_option('secret_word', '', '2checkout'); //2Checkout Secret Word
        $hashSid = $this->get_option('sid', '', '2checkout');
        $hashTotal = $total; //Sale total to validate against
        $hashOrder = $_REQUEST['order_number']; //2Checkout Order Number

        if ($this->SandboxFlag == 'sandbox') {
            $StringToHash = strtoupper(md5($hashSecretWord . $hashSid . 1 . $hashTotal));
        } else {
            $StringToHash = strtoupper(md5($hashSecretWord . $hashSid . $hashOrder . $hashTotal));
        }

        if ($StringToHash != $_REQUEST['key']) {
            $tc->update_order_status($order->ID, 'order_fraud');
        } else {
            $paid = true;
            $order = tc_get_order_id_by_name($order);
            $tc->update_order_payment_status($order->ID, true);
        }

        $this->ipn();
    }

    function gateway_admin_settings($settings, $visible) {
        global $tc;
        ?>
        <div id="<?php echo esc_attr($this->plugin_name); ?>" class="postbox" <?php echo (!$visible ? 'style="display:none;"' : ''); ?>>
            <h3><span><?php printf(__('%s Settings', 'tc'), $this->admin_name); ?></span>
                <span class="description">
                    <?php echo sprintf(__('Sell your tickets via <a target="_blank" href="%s">2Checkout.com</a>', 'tc'), "https://www.2checkout.com/referral?r=95d26f72d1"); ?>
                </span>
            </h3>
            <div class="inside">


                <?php
                $fields = array(
                    'mode' => array(
                        'title' => __('Mode', 'tc'),
                        'type' => 'select',
                        'options' => array(
                            'sandbox' => __('Sandbox / Test', 'tc'),
                            'live' => __('Live', 'tc')
                        ),
                        'default' => 'sandbox',
                    ),
                    'sid' => array(
                        'title' => __('Seller ID', 'tc'),
                        'type' => 'text',
                        'description' => sprintf(__('Login to your 2Checkout dashboard to obtain the seller ID and secret word. <a target="_blank" href="%s">Instructions &raquo;</a>', 'tc'), "http://help.2checkout.com/articles/FAQ/Where-do-I-set-up-the-Secret-Word/"),
                    ),
                    'secret_word' => array(
                        'title' => __('Secret Word', 'tc'),
                        'type' => 'text',
                        'description' => '',
                        'default' => 'tango'
                    ),
                    'currency' => array(
                        'title' => __('Currency', 'tc'),
                        'type' => 'select',
                        'options' => $this->currencies,
                        'default' => 'USD',
                    ),
                );
                $form = new TC_Form_Fields_API($fields, 'tc', 'gateways', '2checkout');
                ?>
                <table class="form-table">
                    <?php $form->admin_options(); ?>
                </table>
            </div>
        </div>
        <?php
    }

    function ipn() {
        global $tc;

        if (isset($_REQUEST['message_type']) && $_REQUEST['message_type'] == 'INVOICE_STATUS_CHANGED') {
            $sale_id = $_REQUEST['sale_id']; //just for calculating hash
            $tco_vendor_order_id = $_REQUEST['vendor_order_id']; //order "name"
            $total = $_REQUEST['invoice_list_amount'];

            $order_id = tc_get_order_id_by_name($tco_vendor_order_id); //get order id from order name
            $order_id = $order_id->ID;
            $order = new TC_Order($order_id);

            if (!$order) {
                header('HTTP/1.0 404 Not Found');
                header('Content-type: text/plain; charset=UTF-8');
                echo 'Invoice not found';
                exit;
            }

            $hash = md5($sale_id . $this->get_option('sid', '', '2checkout') . $_REQUEST['invoice_id'] . $this->get_option('sid', 'secret_word', '2checkout'));

            if ($_REQUEST['md5_hash'] != strtolower($hash)) {
                header('HTTP/1.0 403 Forbidden');
                header('Content-type: text/plain; charset=UTF-8');
                echo "2Checkout hash key doesn't match";
                exit;
            }

            if (strtolower($_REQUEST['invoice_status']) != "deposited") {
                header('HTTP/1.0 200 OK');
                header('Content-type: text/plain; charset=UTF-8');
                echo 'Waiting for deposited invoice status.';
                exit;
            }

            if (intval(round($total, 2)) >= round($order->details->tc_payment_info['total'], 2)) {
                $tc->update_order_payment_status($order_id, true);
                header('HTTP/1.0 200 OK');
                header('Content-type: text/plain; charset=UTF-8');
                echo 'Order completed and verified.';
                exit;
            } else {
                $tc->update_order_status($order_id, 'order_fraud');
                header('HTTP/1.0 200 OK');
                header('Content-type: text/plain; charset=UTF-8');
                echo 'Fraudulent order detected and changed status.';
                exit;
            }
        }
    }

}

tc_register_gateway_plugin('TC_Gateway_2Checkout', 'checkout', __('2Checkout', 'tc'));
?>