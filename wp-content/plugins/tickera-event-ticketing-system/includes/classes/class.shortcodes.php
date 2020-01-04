<?php
/*
  Shortcodes
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

class TC_Shortcodes extends TC {

    function __construct() {
//register shortcodes
        add_shortcode('tc_cart', array(&$this, 'tc_cart_page'));
        add_shortcode('tc_additional_fields', array(&$this, 'tc_additional_fields'));
        add_shortcode('tc_additional_fields_edd', array(&$this, 'tc_additional_fields_edd'));

        add_shortcode('tc_process_payment', array(&$this, 'tc_process_payment_page'));

        add_shortcode('tc_ipn', array(&$this, 'tc_ipn_page'));

        add_shortcode('tc_order_history', array(&$this, 'tc_order_history_page'));
        add_shortcode('tc_payment', array(&$this, 'tc_payment_page'));
        add_shortcode('tc_order_confirmation', array(&$this, 'tc_order_confirmation_page'));
        add_shortcode('tc_order_details', array(&$this, 'tc_order_details_page'));

        add_shortcode('ticket', array(&$this, 'ticket_cart_button'));
        add_shortcode('tc_ticket', array(&$this, 'ticket_cart_button'));
        add_shortcode('ticket_price', array(&$this, 'ticket_price'));
        add_shortcode('tc_ticket_price', array(&$this, 'ticket_price'));
        add_shortcode('tickets_sold', array(&$this, 'tickets_sold'));
        add_shortcode('tickets_left', array(&$this, 'tickets_left'));

        add_shortcode('event', array(&$this, 'event'));
        add_shortcode('tc_event', array(&$this, 'event'));
        add_shortcode('event_tickets_sold', array(&$this, 'event_tickets_sold'));
        add_shortcode('event_tickets_left', array(&$this, 'event_tickets_left'));
        add_shortcode('tc_event_date', array(&$this, 'event_date'));
        add_shortcode('tc_event_location', array(&$this, 'event_location'));
        add_shortcode('tc_event_terms', array(&$this, 'event_terms'));
        add_shortcode('tc_event_sponsors_logo', array(&$this, 'event_sponsors_logo'));
        add_shortcode('tc_event_logo', array(&$this, 'event_logo'));
    }

    function event($atts) {
        ob_start();
        global $tc, $post;

        extract(shortcode_atts(array(
            'id' => false,
            'event_table_class' => 'event_tickets tickera',
            'ticket_type_title' => __('Ticket Type', 'tc'),
            'price_title' => __('Price', 'tc'),
            'cart_title' => __('Cart', 'tc'),
            'soldout_message' => __('Tickets are sold out.', 'tc'),
            'quantity_title' => __('Qty.', 'tc'),
            'quantity' => false,
            'type' => 'cart',
            'open_method' => 'regular',
            'title' => __('Add to Cart', 'tc'),
            'wrapper' => ''), $atts));

        if (empty($id) || !$id) {
            $id = $post->ID;
        }

        $event = new TC_Event($id);
        $event_tickets = $event->get_event_ticket_types('publish');

        if (count($event_tickets) > 0) {

            if ($event->details->post_status == 'publish') {
                ?>

                <div class="tickera">
                    <div class="tc-event-table-wrap">
                        <table class="<?php echo $event_table_class; ?>">
                            <tr>
                                <?php do_action('tc_event_col_title_before_ticket_title'); ?>
                                <th><?php echo $ticket_type_title; ?></th>
                                <?php do_action('tc_event_col_title_before_ticket_price'); ?>
                                <th><?php echo $price_title; ?></th>
                                <?php if ($quantity) { ?>
                                    <th><?php echo $quantity_title; ?></th>
                                <?php }
                                ?>
                                <?php do_action('tc_event_col_title_before_cart_title'); ?>
                                <th><?php echo $cart_title; ?></th>
                            </tr>
                            <?php
                            foreach ($event_tickets as $event_ticket_id) {
                                $event_ticket = new TC_Ticket($event_ticket_id);
                                if (TC_Ticket::is_sales_available($event_ticket_id)) {
                                    ?>
                                    <tr>
                                        <?php do_action('tc_event_col_value_before_ticket_type', $event_ticket_id); ?>
                                        <td><?php echo apply_filters('tc_tickets_table_title', $event_ticket->details->post_title, $event_ticket_id); ?></td>
                                        <?php do_action('tc_event_col_value_before_ticket_price', $event_ticket_id); ?>
                                        <td><?php echo do_shortcode('[ticket_price id="' . $event_ticket->details->ID . '"]'); ?></td>
                                        <?php do_action('tc_event_col_value_before_cart_title', $event_ticket_id); ?>
                                        <?php if ($quantity) { ?>
                                            <td><?php tc_quantity_selector($event_ticket->details->ID); ?></td>
                                        <?php } ?>
                                        <td><?php echo do_shortcode('[ticket id="' . $event_ticket->details->ID . '" type="' . $type . '" title="' . $title . '" soldout_message="' . $soldout_message . '" open_method="' . $open_method . '"]'); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </table>
                    </div><!-- .tc-event-table-wrap -->
                </div><!-- tickera -->

                <?php
                $content = ob_get_clean();

                return $content;
            }
        }
    }

    function ticket_cart_button($atts) {
        global $tc;

        $tc_general_settings = get_option('tc_general_setting', false);

        extract(shortcode_atts(array(
            'id' => false,
            'title' => __('Add to Cart', 'tc'),
            'show_price' => false,
            'price_position' => 'after',
            'price_wrapper' => 'span',
            'price_wrapper_class' => 'price',
            'soldout_message' => __('Tickets are sold out.', 'tc'),
            'type' => 'cart',
            'open_method' => 'regular',
            'quantity' => false,
            'wrapper' => ''), $atts));

        $show_price = (bool) $show_price;
        $quantity = (bool) $quantity;
        if ($id) {
            //do nothing, id is set
        } else {
            $id = -1;
        }

        if (isset($id) && TC_Ticket::is_sales_available($id)) {
            $ticket_type = new TC_Ticket($id, 'publish');
        }

        $event_id = get_post_meta($id, 'event_name', true);

        if (isset($ticket_type->details->ID) && get_post_status($event_id) == 'publish') {//check if ticket still exists
            if ($show_price) {
                $with_price_content = ' <span class="' . $price_wrapper_class . '">' . do_shortcode('[ticket_price id="' . $id . '"]') . '</span> ';
            } else {
                $with_price_content = '';
            }

            if (is_array($tc->get_cart_cookie()) && array_key_exists($id, $tc->get_cart_cookie())) {
                $button = sprintf('<' . $price_wrapper . ' class="tc_in_cart">%s <a href="%s">%s</a></' . $price_wrapper . '>', __('Ticket added to', 'tc'), $tc->get_cart_slug(true), __('Cart', 'tc'));
            } else {
                if ($ticket_type->is_ticket_exceeded_quantity_limit() === false) {

                    if (isset($tc_general_settings['force_login']) && $tc_general_settings['force_login'] == 'yes' && !is_user_logged_in()) {
                        $button = '<form class="cart_form">'
                                . ($price_position == 'before' ? $with_price_content : '') . '<a href="' . apply_filters('tc_force_login_url', wp_login_url(get_permalink()), get_permalink()) . '" class="add_to_cart_force_login" id="ticket_' . $id . '"><span class="title">' . $title . '</span></a>' . ($price_position == 'after' ? $with_price_content : '')
                                . '<input type="hidden" name="ticket_id" class="ticket_id" value="' . $id . '"/>'
                                . '</form>';
                    } else {
                        $button = '<form class="cart_form">'
                                . ($quantity == true ? tc_quantity_selector($id, true) : '')
                                . ($price_position == 'before' ? $with_price_content : '') . '<a href="#" class="add_to_cart" data-button-type="' . esc_attr($type) . '" data-open-method="' . esc_attr($open_method) . '" id="ticket_' . esc_attr($id) . '"><span class="title">' . $title . '</span></a>' . ($price_position == 'after' ? $with_price_content : '')
                                . '<input type="hidden" name="ticket_id" class="ticket_id" value="' . esc_attr($id) . '"/>'
                                . '</form>';
                    }
                } else {
                    $button = '<span class="tc_tickets_sold">' . $soldout_message . '</span>';
                }
            }

            if ($id && get_post_type($id) == 'tc_tickets') {
                return $button;
            } else {
                return __('Unknown ticket ID', 'tc');
            }
        } else {
            return '';
        }
    }

    function ticket_price($atts) {
        global $tc;
        extract(shortcode_atts(array(
            'id' => ''
                        ), $atts));

        $ticket = new TC_Ticket($id, 'publish');
        return apply_filters('tc_cart_currency_and_format', tc_get_ticket_price($ticket->details->ID));
    }

    function event_tickets_sold($atts) {
        global $post;
        extract(shortcode_atts(array(
            'event_id' => ''
                        ), $atts));

        if (empty($event_id)) {
            $event_id = $post->ID;
        }
        return tc_get_event_tickets_count_sold($event_id);
    }

    function event_date($atts) {
        global $post;

        extract(shortcode_atts(array(
            'event_id' => '',
            'id' => '',
                        ), $atts));

        if (empty($id) && empty($event_id)) {
            $id = $post->ID;
        }

        if (!empty($event_id)) {
            $id = $event_id;
        } elseif (!empty($id)) {
            $id = $id;
        }

        $event = new TC_Event($id);


        return $event->get_event_date();
    }

    function event_location($atts) {
        global $post;
        extract(shortcode_atts(array(
            'id' => ''
                        ), $atts));

        if (empty($id)) {
            $id = $post->ID;
        }

        $event = new TC_Event($id);

        return $event->details->event_location;
    }

    function event_terms($atts) {
        global $post;
        extract(shortcode_atts(array(
            'id' => ''
                        ), $atts));

        if (empty($id)) {
            $id = $post->ID;
        }

        $event = new TC_Event($id);

        return apply_filters('tc_shortcode_event_terms', wpautop($event->details->event_terms), $event->details->event_terms);
    }

    function event_sponsors_logo($atts) {
        global $post;
        extract(shortcode_atts(array(
            'id' => '',
            'class' => 'event_sponsors_logo',
            'width' => 'auto',
            'height' => 'auto'
                        ), $atts));

        if (empty($id)) {
            $id = $post->ID;
        }

        $event = new TC_Event($id);
        $img_scr = $event->details->sponsors_logo_file_url;

        if (!empty($img_scr)) {
            return '<img src="' . esc_attr($img_scr) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" class="' . esc_attr($class) . '" />';
        } else {
            return '';
        }
    }

    function event_logo($atts) {
        global $post;
        extract(shortcode_atts(array(
            'id' => '',
            'class' => 'event_logo',
            'width' => 'auto',
            'height' => 'auto'
                        ), $atts));

        if (empty($id)) {
            $id = $post->ID;
        }

        $event = new TC_Event($id);
        $img_scr = $event->details->event_logo_file_url;

        if (!empty($img_scr)) {
            return '<img src="' . esc_attr($img_scr) . '" width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" class="' . esc_attr($class) . '" />';
        } else {
            return '';
        }
    }

    function event_tickets_left($atts) {
        global $post;
        extract(shortcode_atts(array(
            'event_id' => ''
                        ), $atts));

        if (empty($event_id)) {
            $event_id = $post->ID;
        }
        return tc_get_event_tickets_count_left($event_id);
    }

    function tickets_sold($atts) {
        extract(shortcode_atts(array(
            'ticket_type_id' => ''
                        ), $atts));
        return tc_get_tickets_count_sold($ticket_type_id);
    }

    function tickets_left($atts) {
        extract(shortcode_atts(array(
            'ticket_type_id' => ''
                        ), $atts));
        return tc_get_tickets_count_left($ticket_type_id);
    }

    function tc_cart_page($atts) {
        global $tc;
        ob_start();
        $theme_file = locate_template(array('shortcode-cart-contents.php'));

        if ($theme_file != '') {
            include($theme_file);
        } else {
            include( $tc->plugin_dir . 'includes/templates/shortcode-cart-contents.php' );
        }
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_additional_fields($atts) {
        global $tc;
        ob_start();
        $theme_file = locate_template(array('shortcode-cart-additional-info-fields.php'));
        if ($theme_file != '') {
            include($theme_file);
        } else {
            include( $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-info-fields.php' );
        }
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_additional_fields_edd($atts) {
        global $tc;
        ob_start();
        include( $tc->plugin_dir . 'includes/templates/shortcode-cart-additional-info-fields-edd.php' );
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_process_payment_page($atts) {
        global $tc;
        ob_start();
        include( $tc->plugin_dir . 'includes/templates/page-process-payment.php' );
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_ipn_page($atts) {
        global $tc;
        ob_start();
        include( $tc->plugin_dir . 'includes/templates/page-ipn.php' );
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_order_history_page($atts) {
        global $tc;
        ob_start();
        include( $tc->plugin_dir . 'includes/templates/shortcode-order-history-contents.php' );
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_payment_page($atts) {
        global $tc;
        ob_start();
        include( $tc->plugin_dir . 'includes/templates/page-payment.php' );
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_order_confirmation_page($atts) {
        global $tc;
        ob_start();
        include( $tc->plugin_dir . 'includes/templates/page-confirmation.php' );
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

    function tc_order_details_page($atts) {
        global $tc, $wp;
        ob_start();
        include( $tc->plugin_dir . 'includes/templates/page-order.php' );
        $content = wpautop(ob_get_clean(), true);
        return $content;
    }

}

$tc_shortcodes = new TC_Shortcodes();
?>
