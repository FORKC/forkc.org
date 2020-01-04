<?php
/*
  Addon Name: Delete Pending Orders
  Description: Delete pending orders (which are not paid for 12 hours or more). Note: all pending orders will be deleted made via all payment gateways except Free Orders and Offline Payments
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly


if (defined('TC_HIDE_STATS_WIDGET')) {
    return;
}

if (!class_exists('TC_Stats_Dashboard_Widget')) {

    class TC_Stats_Dashboard_Widget {

        var $version = '1.0';
        var $title = 'TC_Stats_Dashboard_Widget';
        var $name = 'tc';
        var $dir_name = 'stats-dashboard-widget';
        var $plugin_dir = '';
        var $plugin_url = '';

        function __construct() {
            $this->title = __('Ticketing Store at a Glance', 'tc');
            add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles_scripts'));
            add_action('wp_dashboard_setup', array(&$this, 'add_tc_dashboard_widgets'));
        }

        function add_tc_dashboard_widgets() {
            if (!current_user_can(apply_filters('tc_can_view_dashboard_widgets_capability', 'manage_options'))) {
                return;
            }
            wp_add_dashboard_widget('tc_store_report', $this->title, array(&$this, 'tc_store_report_display'));
        }

        function enqueue_styles_scripts() {
            global $pagenow, $tc;

            if (!empty($pagenow) && ('index.php' === $pagenow)) {
                wp_enqueue_style('tc-dashboard-widgets', $tc->plugin_url . 'includes/addons/' . $this->dir_name . '/css/dashboard-widgets.css', false, $tc->version);
                wp_enqueue_style('tc-dashboard-widgets-font-awesome', $tc->plugin_url . '/css/font-awesome.min.css', array(), $tc->version);
                wp_enqueue_script('tc-dashboard-widgets-peity', $tc->plugin_url . '/includes/addons/' . $this->dir_name . '/js/jquery.peity.min.js', array('jquery'), $tc->version);
                wp_enqueue_script('tc-dashboard-widgets', $tc->plugin_url . '/includes/addons/' . $this->dir_name . '/js/dashboard-widgets.js', array('jquery'), $tc->version);
            }
        }

        function tc_store_report_display() {
            global $tc, $wpdb;

            $days_range = apply_filters('ticketing_glance_days', 30);
            $days = $days_range * -1;
            $total_revenue = 0;
            $todays_revenue = 0;
            $count_of_paid_tickets = 0;
            $todays_date = date("Y-m-d");
            //$date_30_days_before = date('Y-m-d', strtotime('+' . ($days_range - 1) . ' days'));
            
            $totals_30 = $wpdb->get_results("SELECT orders.post_date as order_date, order_meta.meta_value FROM ".$wpdb->prefix."posts as orders, ".$wpdb->prefix."postmeta as order_meta WHERE orders.ID = order_meta.post_id AND orders.post_status = 'order_paid' AND order_meta.meta_key = 'tc_payment_info' AND orders.post_date BETWEEN (NOW() - INTERVAL 30 DAY) AND (NOW() + INTERVAL 1 DAY)");
            foreach ($totals_30 as $total_record_30_init) {
                $total_record_30 = maybe_unserialize($total_record_30_init->meta_value);
                $total_record_val = isset($total_record_30['total']) ? $total_record_30['total'] : 0;
                $total_revenue = $total_revenue + $total_record_val;
                if (date('Y-m-d', strtotime($total_record_30_init->order_date)) == $todays_date) {
                    $todays_revenue = $todays_revenue + $total_record_val;
                }
                $count_of_paid_tickets++;
            }

            $total_revenue = round($total_revenue, 2);

            $pending_orders_count = (int) $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'tc_orders' AND post_status = 'order_received' AND post_date BETWEEN (NOW() - INTERVAL $days_range DAY) AND (NOW() + INTERVAL 1 DAY)");
            $paid_orders_count = (int) $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'tc_orders' AND post_status = 'order_paid' AND post_date BETWEEN (NOW() - INTERVAL $days_range DAY) AND (NOW() + INTERVAL 1 DAY)");
            ?>
            <ul class="tc-status-list">
                <li class="sales-this-month">
                    <a>
                        <i class="fa fa-money tc-icon tc-icon-dashboard-sales"></i> 
                        <strong><span class="amount"><?php echo $tc->get_cart_currency_and_format($total_revenue); ?></span></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php printf(_n('last %d day earnings', 'last %d days earnings', $days_range, 'tc'), $days_range); ?></span>
                    </a>
                </li>

                <li class="todays-earnings">
                    <a>
                        <i class="fa fa-money tc-icon tc-icon-dashboard-todays-earnings"></i> 
                        <strong><?php echo $tc->get_cart_currency_and_format($todays_revenue); ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php _e('today\'s earnings', 'tc'); ?></span>
                    </a>
                </li>
                <li class="sold-tickets">
                    <a>
                        <i class="fa fa-ticket tc-icon tc-icon-dashboard-sold"></i> 
                        <strong><?php printf(_n('%d ticket sold', '%d tickets sold', $count_of_paid_tickets, 'tc'), $count_of_paid_tickets); ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php printf(_n('in the last %d day', 'in the last %d days', $days_range, 'tc'), $days_range); ?></span>
                    </a>
                </li>
                <li class="completed-orders">
                    <a>
                        <i class="fa fa-shopping-cart tc-icon tc-icon-dashboard-completed"></i> 
                        <strong><?php printf(_n('%d order completed', '%d orders completed', $paid_orders_count, 'tc'), $paid_orders_count); ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php printf(_n('in the last %d day', 'in the last %d days', $days_range, 'tc'), $days_range); ?></span>
                    </a>
                </li>
                <li class="pending-orders">
                    <a>
                        <i class="fa fa-shopping-cart tc-icon tc-icon-dashboard-pending"></i> 
                        <strong><?php printf(_n('%d pending order', '%d pending orders', $pending_orders_count, 'tc'), $pending_orders_count); ?></strong>
                        <span class="tc-dashboard-widget-subtitle"><?php printf(_n('in the last %d day', 'in the last %d days', $days_range, 'tc'), $days_range); ?></span>
                    </a>
                </li>

            </ul>
            <?php
        }

    }

}

if (is_admin()) {
    $tc_stats_dashboard_widget = new TC_Stats_Dashboard_Widget();
}
?>