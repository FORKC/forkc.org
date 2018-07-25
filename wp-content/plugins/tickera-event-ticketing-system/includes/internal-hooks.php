<?php
add_action('manage_users_columns', 'tc_modify_user_columns');

function tc_modify_user_columns($column_headers) {
    $column_headers['tc_number_of_orders'] = __('Ticket Orders', 'tc');
    return $column_headers;
}

add_action('manage_users_custom_column', 'tc_add_number_of_orders_value', 10, 3);

function tc_add_number_of_orders_value($value, $column_name, $user_id) {
    $user = get_userdata($user_id);
    if ('tc_number_of_orders' == $column_name) {
        global $wpdb;
        if (user_can($user_id, 'manage_options') && $user_id != 0) {
            $value = '-';
        } else {
            $where = "WHERE post_author = " . (int) $user_id . " AND post_type = 'tc_orders' AND (post_status = 'order_paid' OR post_status = 'order_received' OR post_status = 'order_fraud')";
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts $where");
            $value = '<a href="' . admin_url('user-edit.php?user_id=' . $user_id . '#tc_order_history') . '">' . apply_filters('tc_get_user_number_of_orders_value', $count, $user_id) . '</a>';
        }
    }
    return $value;
}

add_action('edit_user_profile', 'tc_show_extra_profile_fields_order_history');

//

function tc_show_extra_profile_fields_order_history($user) {
    global $tc;
    ?>

    <h3><?php _e('Ticket Order History', 'tc'); ?><a name="tc_order_history"></a></h3>

    <table class="form-table">

        <tr>
            <th></th>
            <td>
                <?php
                $user_orders = TC_Orders::get_user_orders($user->ID);

                if (count($user_orders) == 0) {
                    _e('No Orders Found', 'tc');
                } else {
                    ?>
                    <table cellspacing="0" class="widefat shadow-table" cellpadding="10">
                        <tr>
                            <td><?php _e('Order ID', 'tc'); ?></td>
                            <td><?php _e('Status', 'tc'); ?></td>
                            <td><?php _e('Date', 'tc'); ?></td>
                            <td><?php _e('Total', 'tc'); ?></td>
                            <td><?php _e('Details', 'tc'); ?></td>
                        </tr>
                        <?php
                        $style = '';
                        foreach ($user_orders as $user_order) {
                            $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                            $order = new TC_Order($user_order->ID);
                            ?>
                            <tr <?php echo $style; ?>> 
                                <td>
                                    <?php echo $order->details->post_title; ?>
                                </td>
                                <td>
                                    <?php
                                    $post_status = $order->details->post_status;
                                    $init_post_status = $post_status;

                                    if ($post_status == 'order_fraud') {
                                        $color = "tc_order_fraud";
                                    } else if ($post_status == 'order_received') {
                                        $color = "tc_order_recieved"; //yellow
                                    } else if ($post_status == 'order_paid') {
                                        $color = "tc_order_paid";
                                    } else if ($post_status == 'order_cancelled') {
                                        $color = "tc_order_cancelled";
                                    }

                                    if ($post_status == 'order_fraud') {
                                        $post_status = __('Held for Review', 'tc');
                                    }

                                    $post_status = ucwords(str_replace('_', ' ', $post_status));
                                    echo sprintf(__('%1$s %2$s %3$s', 'tc'), '<span class="' . apply_filters('tc_order_history_color', $color, $init_post_status) . '">', __(ucwords($post_status), 'tc'), '</span>');
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo tc_format_date($order->details->tc_order_date, true);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    echo apply_filters('tc_cart_currency_and_format', $order->details->tc_payment_info['total']);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $order_status_url = admin_url('post.php?post=' . $order->details->ID . '&action=edit');
                                    ?>
                                    <a href="<?php echo $order_status_url; ?>"><?php _e('Order Details', 'tc'); ?></a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                    <?php
                }
                ?>
            </td>
        </tr>

    </table>
    <?php
}

add_action('tc_cart_col_title_before_total_price', 'tc_cart_col_title_before_total_price');

function tc_is_disabled_fee_column() {
    $tc_general_settings = get_option('tc_general_setting', false);
    $use_global_fees = isset($tc_general_settings['use_global_fees']) ? $tc_general_settings['use_global_fees'] : 'no';
    $show_fees = isset($tc_general_settings['show_fees']) ? $tc_general_settings['show_fees'] : 'yes';

    $disabled = true;

    if ($use_global_fees == 'yes') {
        $global_fee_type = $tc_general_settings['global_fee_type'];
        $global_fee_scope = $tc_general_settings['global_fee_scope'];

        if (empty($global_fee_scope) || $global_fee_scope == '' || $global_fee_scope == 'ticket') {
            $global_fee_scope = 'ticket';
            $disabled = false;
        }

        if ($global_fee_scope == 'order' && $global_fee_type == 'fixed') {
            $disabled = true;
        }
    } else {
        if ($show_fees == 'no') {
            $disabled = true;
        } else {
            $disabled = false;
        }
    }

    if ($show_fees == 'no') {
        $disabled = true;
    }

    if ($disabled) {
        add_filter('tc_cart_table_colspan', 'tc_cart_table_colspan_modify', 10, 1);
    }

    return $disabled;
}

function tc_cart_table_colspan_modify($colspan) {
    return $colspan - 1;
}

function tc_cart_col_title_before_total_price() {
    $tc_general_settings = get_option('tc_general_setting', false);
    $fees_label = isset($tc_general_settings['fees_label']) ? $tc_general_settings['fees_label'] : 'FEES';


    $use_global_fees = isset($tc_general_settings['use_global_fees']) ? $tc_general_settings['use_global_fees'] : 'no';

    $disabled = tc_is_disabled_fee_column();

    if (!isset($tc_general_settings['show_fees']) || (isset($tc_general_settings['show_fees']) && $tc_general_settings['show_fees'] == 'yes')) {
        if (!$disabled) {
            ?>
            <th><?php echo $fees_label; ?></th>
            <?php
        }
    }
}

add_action('tc_cart_col_value_before_total_price', 'tc_cart_col_value_before_total_price', 10, 3);

function tc_cart_col_value_before_total_price($ticket_type, $ordered_count, $ticket_price) {
    global $tc, $total_fees;

    $tc_general_settings = get_option('tc_general_setting', false);

    if (!isset($total_fees) || !is_numeric($total_fees)) {
        $total_fees = 0;
    }

    $ticket = new TC_Ticket($ticket_type);
    $fee_type = $ticket->details->ticket_fee_type;
    $fee = $ticket->details->ticket_fee;

    if ($fee == '' || !isset($fee)) {
        $fee = 0;
    } else {
        if ($fee_type == 'fixed') {
            $fee = round(($ordered_count * $fee), 2);
        } else {
            $fee = round((($ticket_price * $ordered_count) / 100) * $fee, 2);
        }
    }

    $use_global_fees = isset($tc_general_settings['use_global_fees']) ? $tc_general_settings['use_global_fees'] : 'no';

    if ($use_global_fees == 'yes') {
        $global_fee_type = $tc_general_settings['global_fee_type'];
        $global_fee_value = $tc_general_settings['global_fee_value'];
        $global_fee_scope = $tc_general_settings['global_fee_scope'];

        if (empty($global_fee_scope) || $global_fee_scope == '') {
            $global_fee_scope = 'ticket';
        }

        if ($global_fee_value == '' || !isset($global_fee_value)) {
            $fee = 0;
        } else {
            if ($global_fee_type == 'fixed') {
                $fee = apply_filters('tc_global_fixed_fee_value', round(($ordered_count * $global_fee_value), 2), $ordered_count, $global_fee_value);
            } else {
                $fee = apply_filters('tc_global_percentage_fee_value', round((($ticket_price * $ordered_count) / 100) * $global_fee_value, 2), $ticket_price, $ordered_count, $global_fee_value);
            }
        }
    }

    $total_fees = apply_filters('tc_total_fees_value', $total_fees + $fee, $use_global_fees, isset($global_fee_scope) ? $global_fee_scope : 'ticket', $ordered_count, isset($global_fee_value) ? $global_fee_value : 0, $ticket_price, isset($global_fee_type) ? $global_fee_type : 'percentage' );

    if (!isset($_SESSION)) {
        session_start();
    }

    $disabled = tc_is_disabled_fee_column();

    $_SESSION['tc_total_fees'] = $total_fees;

    $tc_general_settings = get_option('tc_general_setting', false);
    if (!isset($tc_general_settings['show_fees']) || (isset($tc_general_settings['show_fees']) && $tc_general_settings['show_fees'] == 'yes')) {
        if (!$disabled) {
            ?>
            <td class="ticket-fee" class="ticket_fee"><?php echo apply_filters('tc_cart_currency_and_format', $fee); ?></td>
            <?php
        }
    }
}

add_filter('tc_total_fees_value', 'tc_total_fees_value_modify', 10, 7);

function tc_total_fees_value_modify($total_fees, $use_global_fees, $global_fee_scope, $ordered_count, $global_fee_value, $ticket_price, $global_fee_type) {
    if ($use_global_fees == 'yes' && $global_fee_scope == 'order' && $global_fee_type == 'fixed') {
        $total_fees = $global_fee_value;
    }
    return $total_fees;
}

function tc_modify_global_fixed_fee_value($value, $ordered_count, $global_fee_value) {
    return $global_fee_value;
}

function tc_global_percentage_fee_value($value, $ticket_price, $ordered_count, $global_fee_value) {
    return $value;
}

add_action('tc_cart_col_value_before_total_price_total', 'tc_cart_col_value_before_total_price_total', 11, 1);

function tc_cart_col_value_before_total_price_total($total) {
    global $tc, $total_fees;
    $tc_general_settings = get_option('tc_general_setting', false);
    $fees_label = isset($tc_general_settings['fees_label']) ? $tc_general_settings['fees_label'] : 'FEES';

    do_action('tc_cart_col_value_before_total_price_fees');
    add_filter('tc_cart_total', 'tc_cart_total_with_fees', 10, 1);

    if (!function_exists('tc_cart_total_with_fees')) {

        function tc_cart_total_with_fees($total_price) {
            global $total_fees;
            //return apply_filters( 'tc_discounted_total', $total_price );
            return $total_price + apply_filters('tc_discounted_fees_total', $total_fees);
        }

    }

    if (!isset($tc_general_settings['show_fees']) || (isset($tc_general_settings['show_fees']) && $tc_general_settings['show_fees'] == 'yes')) {
        ?>
        <span class="total_item_title"><?php echo $fees_label; ?>:</span><span class="total_item_amount"><?php echo apply_filters('tc_cart_currency_and_format', $total_fees); ?></span>
        <?php
    }
}

add_action('tc_cart_col_value_before_total_price_total', 'tc_cart_tax', 12, 1);

function tc_cart_tax($total) {
    global $tc, $total_fees, $tax_value;

    $tc_general_settings = get_option('tc_general_setting', false);

    $tax_inclusive = tc_is_tax_inclusive();

    $total_cart = round($total + $total_fees, 2);

    if ($tax_inclusive) {
        $tax_value = round($total_cart - ($total_cart / (($tc->get_tax_value() / 100) + 1)), 2);
    } else {
        $tax_value = round($total_cart * ($tc->get_tax_value() / 100), 2);
    }

    $tax_label = isset($tc_general_settings['tax_label']) ? $tc_general_settings['tax_label'] : 'TAX';

    if (!isset($_SESSION)) {
        session_start();
    }

    $_SESSION['tc_tax_value'] = $tax_value;

    $_SESSION['cart_info']['total'] = $tax_inclusive ? $total_cart : ($total_cart + $tax_value);

    add_filter('tc_cart_total', 'tc_cart_total_with_tax', 10, 1);

    if (!function_exists('tc_cart_total_with_tax')) {

        function tc_cart_total_with_tax($total_price) {

            $tax_inclusive = tc_is_tax_inclusive();

            global $total_fees, $tax_value;
            if (!session_id()) {
                session_start();
            }
            $_SESSION['tc_cart_total'] = $tax_inclusive ? $total_price : ($total_price + $tax_value);
            return $tax_inclusive ? $total_price : ($total_price + $tax_value);
        }

    }

    do_action('tc_cart_col_value_before_total_price_tax');

    if (!isset($tc_general_settings['show_tax_rate']) || (isset($tc_general_settings['show_tax_rate']) && $tc_general_settings['show_tax_rate'] == 'yes')) {
        ?>
        <span class="total_item_title"><?php echo $tax_label; ?>:</span><span class="total_item_amount"><?php echo apply_filters('tc_cart_currency_and_format', $tax_value); ?></span>
        <?php
    }
}

add_filter('tc_discounted_total', 'tc_discounted_total', 10, 1);

function tc_discounted_total($total) {
    $tax_inclusive = tc_is_tax_inclusive();
    $tax_value = $_SESSION['tc_tax_value'];
    $total_fees = $_SESSION['tc_total_fees'];

    if ($tax_inclusive) {
        $discounted_total = round($total + $total_fees, 2);
    } else {
        $discounted_total = round($total + $total_fees + $tax_value, 2);
    }

    return $discounted_total;
}

add_filter('tc_event_date_time_element', 'tc_event_date_time_element', 10, 1);

function tc_event_date_time_element($date) {
    return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date), false);
}

add_filter('tc_checkins_date_checked', 'tc_checkins_date_checked', 10, 1);

function tc_checkins_date_checked($date) {
    return tc_format_date($date); //date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date, false );
}

add_filter('tc_checkins_status', 'tc_checkins_status', 10, 1);

function tc_checkins_status($status) {
    if ($status == 'Pass') {
        $status = '<span class="status_green">' . $status . '</span>';
    }
    if ($status == 'Fail') {
        $status = '<span class="status_red">' . $status . '</span>';
    }
    return $status;
}

add_filter('tc_checkins_api_key_id', 'tc_checkins_api_key_id', 10, 1);

function tc_checkins_api_key_id($api_key_id) {
    $api_key = new TC_API_Key($api_key_id);
    $api_key_name = '<a target="_blank" href="' . admin_url('edit.php?post_type=tc_events&page=tc_settings&tab=api&action=edit&ID=' . $api_key_id) . '">' . $api_key->details->api_key_name . '</a>';
    return $api_key_name;
}

/* Order status and color */

add_filter('tc_order_field_value', 'tc_order_field_value', 10, 5);

function tc_order_field_value($order_id, $value, $meta_key, $field_type, $field_id = false) {
    global $tc;

    if ($field_type == 'order_status') {
        $new_value = str_replace('_', ' ', $value);

        if ($value == 'order_fraud') {
            $color = "tc_order_fraud";
        } else if ($value == 'order_received') {
            $color = "tc_order_recieved"; //yellow
        } else if ($value == 'order_paid') {
            $color = "tc_order_paid";
        } else if ($value == 'order_cancelled') {
            $color = "tc_order_cancelled";
        } else {
            $color = "black";
        }

        $note = '';

        $notes = TC_Order::get_order_notes($order_id);

        if (isset($notes) && isset($notes['tc_order_notes']) && count($notes['tc_order_notes']) > 0) {
            $note_text = '';

            if (isset($notes['tc_order_notes'][0])) {
                $note_text = wp_kses_post($notes['tc_order_notes'][0]['created_at'] . '' . $notes['tc_order_notes'][0]['note']);
            }

            $note = '<i class="fa fa-flag" title="' . esc_attr($note_text) . '" alt="' . esc_attr($note_text) . '" aria-hidden="true"></i>';
        }

        return sprintf(__('%1$s %2$s %3$s', 'tc'), '<span class="' . $color . '">', __(ucwords($new_value), 'tc'), $note . '</span>');
    } else if ($field_id == 'order_date') {
        return tc_format_date($value); //date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $value, false );
    } else if ($field_id == 'customer') {

        $order = new TC_Order($order_id);
        $author_id = $order->details->post_author;

        if (!user_can($author_id, 'manage_options') && $author_id != 0) {
            $buyer_info = '<a href="' . admin_url('user-edit.php?user_id=' . $author_id) . '">' . $value['buyer_data']['first_name_post_meta'] . ' ' . $value['buyer_data']['last_name_post_meta'] . '</a>';
        } else {
            $buyer_info = isset($value['buyer_data']) ? ($value['buyer_data']['first_name_post_meta'] . ' ' . $value['buyer_data']['last_name_post_meta']) : __('N/A', 'tc');
        }

        return $buyer_info;
    } elseif ($field_id == 'parent_event') {
        $events = $tc->get_cart_events($value);
        foreach ($events as $event_id) {
            $event = new TC_Event($event_id);
            if (isset($event->details->ID)) {
                echo '<a href="post.php?post=' . $event->details->ID . '&action=edit">' . $event->details->post_title . '</a> x ' . $tc->get_cart_event_tickets($value, $event->details->ID) . '<br />';
            } else {
                _e('N/A', 'tc');
            }
        }
    } elseif ($field_id == 'gateway') {
        return $value['gateway'];
    } elseif ($field_id == 'gateway_admin_name') {
        return $value['gateway_admin_name'];
    } elseif ($field_id == 'discount') {
        $discounts = new TC_Discounts();
        $discount_total = $discounts->get_discount_total_by_order($order_id);
        if (isset($value['coupon_code'])) {
            $discount_object = get_page_by_title($value['coupon_code'], OBJECT, 'tc_discounts');
        } else {
            $discount_total = 0;
        }

        if ($discount_total > 0) {
            $discount_total = apply_filters('tc_cart_currency_and_format', $discount_total) . '<br />' . __('Code: ', 'tc') . '<a href="edit.php?post_type=tc_events&page=tc_discount_codes&action=edit&ID=' . $discount_object->ID . '">' . $value['coupon_code'] . '</a>';
        } else {
            $discount_total = '-';
        }
        return $discount_total;
    } elseif ($field_id == 'total') {
        return apply_filters('tc_cart_currency_and_format', $value['total']);
    } elseif ($field_id == 'subtotal') {
        return apply_filters('tc_cart_currency_and_format', $value['subtotal']);
    } elseif ($field_id == 'subtotal') {
        return apply_filters('tc_cart_currency_and_format', $value['subtotal']);
    } elseif ($field_id == 'fees_total') {
        return apply_filters('tc_cart_currency_and_format', $value['fees_total']);
    } elseif ($field_id == 'tax_total') {
        return apply_filters('tc_cart_currency_and_format', $value['tax_total']);
    } else {
        return $value;
    }
}

/* Add additional fields to events admin */

add_filter('tc_event_fields', 'my_custom_events_admin_fields');

function my_custom_events_admin_fields($event_fields) {

    $event_fields[] = array(
        'field_name' => 'event_shortcode',
        'field_title' => __('Shortcode', 'tc'),
        'field_type' => 'read-only',
        'table_visibility' => true,
        'show_in_post_type' => false
    );

    if (current_user_can(apply_filters('tc_event_activation_capability', 'edit_others_tc_events')) || current_user_can('manage_options')) {
        $event_fields[] = array(
            'field_name' => 'event_active',
            'field_title' => __('Active', 'tc'),
            'field_type' => 'read-only',
            'table_visibility' => true,
            'table_edit_invisible' => true,
            'show_in_post_type' => false
        );
    }

    return $event_fields;
}

add_filter('tc_event_object_details', 'my_custom_tc_event_object_details');

function my_custom_tc_event_object_details($object_details) {
    $object_details->event_shortcode = '[event id="' . $object_details->ID . '"]';

    $event_status = get_post_status($object_details->ID);
    $on = $event_status == 'publish' ? 'tc-on' : '';
    $object_details->event_active = '<div class="tc-control ' . $on . '" event_id="' . esc_attr($object_details->ID) . '"><div class="tc-toggle"></div></div>';

    return $object_details;
}

/* Add custom fields to tickets admin */
add_filter('tc_ticket_fields', 'my_custom_tickets_admin_fields');

function my_custom_tickets_admin_fields($ticket_fields) {

    /* $ticket_fields[] = array(
      'field_name'		 => 'tickets_sold',
      'field_title'		 => __( 'Quantity Sold', 'tc' ),
      'field_type'		 => 'read-only',
      'table_visibility'	 => true,
      'post_field_type'	 => 'read-only'
      ); */

    $ticket_fields[] = array(
        'field_name' => 'ticket_shortcode',
        'field_title' => __('Shortcode', 'tc'),
        'field_type' => 'read-only',
        'table_visibility' => true,
        'post_field_type' => 'read-only'
    );

    return $ticket_fields;
}

add_filter('tc_ticket_object_details', 'my_custom_tc_ticket_object_details');

function my_custom_tc_ticket_object_details($object_details) {
    $object_details->ticket_shortcode = '[ticket id="' . $object_details->ID . '"]';

    global $wpdb;
    $sold_records = $wpdb->get_results($wpdb->prepare(
                    "
	SELECT      COUNT(*) as cnt, p.post_parent
	FROM        $wpdb->posts p, $wpdb->postmeta pm
                    WHERE p.ID = pm.post_id
                    AND pm.meta_key = 'ticket_type_id'
                    AND pm.meta_value = %d
                    GROUP BY p.post_parent
	", $object_details->ID
    ));
    $sold_count = 0;
    foreach ($sold_records as $sold_record) {
        if (get_post_status($sold_record->post_parent) == 'order_paid') {
            $sold_count = $sold_count + $sold_record->cnt;
        }
    }

    $ticket_status = get_post_status($object_details->ID);
    $on = $ticket_status == 'publish' ? 'tc-on' : '';
    $object_details->ticket_active = '<div class="tc-control ' . $on . '" ticket_id="' . esc_attr($object_details->ID) . '"><div class="tc-toggle"></div></div>';

    $object_details->quantity_sold = $sold_count;
    return $object_details;
}

add_filter('tc_ticket_instance_field_value', 'tc_ticket_instance_field_value', 10, 5);

function tc_ticket_instance_field_value($value = false, $field_value = false, $post_field_type = false, $col_field_id = false, $field_id = false) {//$value, $post_field_type, $var_name
    if ($field_id == 'order') {

        $parent_post = get_post_ancestors($value);
        $parent_post = isset($parent_post[0]) ? $parent_post[0] : 0;

        $order = new TC_Order($parent_post);

        $order_found = false;

        if (get_post_type($order->details->ID) == 'tc_orders') {
            $order_found = true;
        }

        $order_found = apply_filters('tc_order_found', $order_found, $order->details->ID);

        if ($order_found) {

            if (current_user_can('manage_orders_cap')) {
                $value = apply_filters('tc_ticket_instance_order_admin_url', '<a target="_blank" href="' . admin_url('post.php?post=' . $order->details->ID . '&action=edit') . '">' . $order->details->post_title . '</a>', $parent_post, $order->details->post_title);
            } else {
                $value = $order->details->post_title;
            }
        } else {
            $value = __('N/A', 'tc');
        }
    }

    if ($field_id == 'event') {
        $value = tc_get_ticket_instance_event(false, false, $value);
    }

    if ($field_id == 'ticket_code') {
        $value = $field_value;
    }

    if ($field_id == 'ticket_type_id') {
        $ticket_type = new TC_Ticket($field_value);
        $value = apply_filters('tc_checkout_owner_info_ticket_title', isset($ticket_type->details->post_title) ? $ticket_type->details->post_title : __('N/A', 'tc'), $field_value, array(), $value);
    }

    if ($field_id == 'ticket') {
        $value = '<a target="_blank" href="' . admin_url('edit.php?post_type=tc_tickets_instances&tc_preview&ticket_instance_id=' . $field_value) . '">' . __('View', 'tc') . '</a> | <a target="_top" href="' . admin_url('edit.php?post_type=tc_tickets_instances&tc_download&ticket_instance_id=' . $field_value) . '">' . __('Download', 'tc') . '</a>';
    }

    if ($field_id == 'checkins') {
        $ticket_instance = new TC_Ticket_Instance($field_value);
        $checkins_pass = $ticket_instance->get_number_of_checkins('pass');
        $checkins_fail = $ticket_instance->get_number_of_checkins('fail');
        $value = '<a href="' . admin_url('post.php?post=' . $field_value . '&action=edit') . '">';
        $value .= '<span class="' . ($checkins_pass > 0 ? 'status_green' : '') . '">' . $checkins_pass . '</span>';

        if ($checkins_fail > 0) {
            $value .= ' | <span class="status_red">' . $checkins_fail . '</span>';
        }

        $value .= __(' Details', 'tc');
        $value .= '</a>';
    }

    if ($field_id == 'owner_name') {
        $owner_name = get_post_meta($value, 'first_name', true) . ' ' . get_post_meta($value, 'last_name', true);
        if (trim($owner_name) == '') {
            $parent_post = get_post_ancestors($value);
            $parent_post = isset($parent_post[0]) ? $parent_post[0] : 0;
            $order = new TC_Order($parent_post);
            $buyer_full_name = isset($order->details->tc_cart_info['buyer_data']) ? (isset($order->details->tc_cart_info['buyer_data']['first_name_post_meta']) ? $order->details->tc_cart_info['buyer_data']['first_name_post_meta'] : '') . ' ' . (isset($order->details->tc_cart_info['buyer_data']['last_name_post_meta']) ? $order->details->tc_cart_info['buyer_data']['last_name_post_meta'] : '') : __('N/A', 'tc');
            //$buyer_info		 = get_post_meta( $value, 'tc_cart_info', false );
            if (trim($buyer_full_name) !== '') {
                $value = apply_filters('tc_ticket_buyer_name_element', $buyer_full_name, $parent_post);
                $value = trim($value);
                if (empty($value)) {
                    $value = '-';
                }
            } else {
                $value = apply_filters('tc_ticket_buyer_name_element', '-', $parent_post);
                $value = trim($value);
                if (empty($value)) {
                    $value = '-';
                }
            }
        } else {
            $value = $owner_name;
        }
    }
    return $value;
}

add_filter('tc_api_key_field_value', 'tc_api_key_field_value', 10, 3);

function tc_api_key_field_value($value, $post_field_type, $var_name) {
    if ($var_name == 'event_name') {
        if ($value == 'all') {
            $value = __('All Events', 'tc');
        } else {
            $event_obj = new TC_Event($value);
            $event_object = $event_obj->details;
            $value = $event_object->post_title;
        }
    }

    if ($var_name == 'api_username') {
        if (trim($value) == '') {
            $value = __('Administrator', 'tc');
        } else {
            $args = array(
                'blog_id' => $GLOBALS['blog_id'],
                'search' => $value
            );
            $users = get_users($args);
            if (isset($users[0])) {
                $user = $users[0];
            }

            if (isset($user)) {
                $value = '<a target="_blank" href="' . admin_url('user-edit.php?user_id=' . $user->ID) . '">' . $user->user_login . ' ' . (isset($user->display_name) && $user->display_name != '' ? '(' . $user->display_name . ')' : '') . '</a>';
            } else {
                $value = __('Wrong user. API will be available to the administrators only.', 'tc');
            }
        }
    }

    return $value;
}

add_filter('tc_ticket_field_value', 'tc_ticket_field_value', 10, 3);

function tc_ticket_field_value($value, $post_field_type, $var_name) {

    $quantity_available = 0;

    if ($var_name == 'event_name') {
        $event_obj = new TC_Event($value);
        $event_object = $event_obj->details;
        $value = $event_object->post_title;
    }

    if ($var_name == 'quantity_available') {
        $quantity_available = $value;
        if ($value == 0 || $value == '') {
            $value = __('Unlimited', 'tc');
        }
    }


    if ($var_name == 'min_tickets_per_order') {
        if ($value == 0 || $value == '') {
            $value = __('No minimum', 'tc');
        }
    }

    if ($var_name == 'max_tickets_per_order') {
        if ($value == 0 || $value == '') {
            $value = __('No maximum', 'tc');
        }
    }

    if ($var_name == 'ticket_fee') {
        if ($value == 0 || $value == '') {
            $value = __('-', 'tc');
        } else {
            $value = $value;
        }
    }

    if ($var_name == 'ticket_fee_type') {
        if ($value == 'fixed') {
            $value = 'Fixed';
        } else {
            $value = 'Percentage';
        }
    }


    return $value;
}

add_filter('tc_discount_field_value', 'my_custom_tc_discount_values', 10, 3);

function my_custom_tc_discount_values($value, $post_field_type, $var_name) {
    global $tc_last_discount_code;
    if ($var_name == 'post_title') {
        //$discount_code = $value;
        $tc_last_discount_code = $value;
    }

    if ($var_name == 'discount_availability') {
        if ($value == '') {
            $value = __('All', 'tc');
        } else {
            $value_combined = '';

            $values = !is_array($value) ? explode(',', $value) : $value;

            foreach ($values as $val) {
                if ($val !== '') {
                    $ticket_obj = new TC_Ticket($val);
                    $ticket_object = $ticket_obj->details;

                    $event_id = $ticket_object->event_name;
                    $event_obj = new TC_Event($event_id);
                    $event_object = $event_obj->details;
                    $value_combined = $value_combined . '' . $ticket_object->post_title . ' (' . $event_object->post_title . ')<br />';
                }
            }

            $value = $value_combined;

            if ($value == '') {
                $value = __('All', 'tc');
            }
        }
    }

    if ($var_name == 'discount_type') {
        if ($value == '1') {
            $value = __('Fixed Amount (per item)', 'tc');
        } else if($value == '2') {
            $value = __('Percentage (%)', 'tc');
        }else{
            $value = __('Fixed Amount (per order)', 'tc');
        }
    }

    if ($var_name == 'usage_limit') {
        if ($value == '') {
            $value = /* TC_Discounts::discount_used_times( $tc_last_discount_code ) . ' / ' . */__('Unlimited', 'tc');
        } else {
            $value = /* TC_Discounts::discount_used_times( $tc_last_discount_code ) . ' / ' . */$value;
        }
    }

    return $value;
}
?>