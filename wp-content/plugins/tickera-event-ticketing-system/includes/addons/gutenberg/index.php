<?php

/*
  Plugin Name: Gutenberg blocks for Tickera
  Plugin URI: https://tickera.com
  Description: Adds Tickera Gutenberg blocks
  Author: Tickera
  Author URI: https://tickera.com
  Version: 0.1
  TextDomain: tcg
  Domain Path: /languages/

  Copyright 2018 Tickera (https://tickera.com)
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('TC_tc_gutentick')) {

    class TC_tc_gutentick {

        var $version = '0.1';
        var $title = 'Gutentick';
        var $name = 'tc_gutentick';
        var $dir_name = 'tc-gutentick';
        var $location = 'plugins';
        var $plugin_dir = '';
        var $plugin_url = '';

        function __construct() {
            if (function_exists('register_block_type')) {
                add_action('init', array($this, 'register_gutenberg_blocks'));
                add_action('enqueue_block_editor_assets', array($this, 'register_extra_scripts'));
            }
        }

        function register_gutenberg_blocks() {

            //only if Bridge is not active
            if (apply_filters('tc_bridge_for_woocommerce_is_active', false) == false) {
                register_block_type('tickera/add-to-cart', array(
                    'editor_script' => 'tc_add_to_cart_block_editor',
                    'editor_style' => 'tc_add_to_cart_block_editor',
                    'render_callback' => array($this, 'render_add_to_cart_shortcode'),
                    'attributes' => array(
                        'ticket_type_id' => array('type' => 'string'),
                        'souldout_message' => array('type' => 'string'),
                        'show_price' => array('type' => 'string'),
                        'price_position' => array('type' => 'string'),
                        'quantity' => array('type' => 'boolean'),
                        'link_type' => array('type' => 'string'),
                        'className' => array('type' => 'string')
                    )
                ));

                register_block_type('tickera/event-add-to-cart', array(
                    'editor_script' => 'tc_event_add_to_cart_block_editor',
                    'editor_style' => 'tc_event_add_to_cart_block_editor',
                    'render_callback' => array($this, 'render_event_add_to_cart_shortcode'),
                    'attributes' => array(
                        'event_id' => array('type' => 'string'),
                        'button_title' => array('type' => 'string'),
                        'link_type' => array('type' => 'string'),
                        'ticket_type_title' => array('type' => 'string'),
                        'price_title' => array('type' => 'string'),
                        'cart_title' => array('type' => 'string'),
                        'quantity' => array('type' => 'boolean'),
                        'quantity_title' => array('type' => 'string'),
                        'soldout_message' => array('type' => 'string'),
                        'className' => array('type' => 'string')
                    )
                ));

                register_block_type('tickera/event-tickets-sold', array(
                    'editor_script' => 'tc_event_tickets_sold_block_editor',
                    'editor_style' => 'tc_event_tickets_sold_block_editor',
                    'render_callback' => array($this, 'render_event_tickets_sold_shortcode'),
                    'attributes' => array(
                        'event_id' => array('type' => 'string'),
                        'className' => array('type' => 'string')
                    )
                ));

                register_block_type('tickera/event-tickets-left', array(
                    'editor_script' => 'tc_event_tickets_left_block_editor',
                    'editor_style' => 'tc_event_tickets_left_block_editor',
                    'render_callback' => array($this, 'render_event_tickets_left_shortcode'),
                    'attributes' => array(
                        'event_id' => array('type' => 'string'),
                        'className' => array('type' => 'string')
                    )
                ));

                register_block_type('tickera/tickets-sold', array(
                    'editor_script' => 'tc_tickets_sold_block_editor',
                    'editor_style' => 'tc_tickets_sold_block_editor',
                    'render_callback' => array($this, 'render_tickets_sold_shortcode'),
                    'attributes' => array(
                        'ticket_type_id' => array('type' => 'string'),
                        'className' => array('type' => 'string')
                    )
                ));

                register_block_type('tickera/tickets-left', array(
                    'editor_script' => 'tc_tickets_left_block_editor',
                    'editor_style' => 'tc_tickets_left_block_editor',
                    'render_callback' => array($this, 'render_tickets_left_shortcode'),
                    'attributes' => array(
                        'ticket_type_id' => array('type' => 'string'),
                        'className' => array('type' => 'string')
                    )
                ));

                register_block_type('tickera/order-history', array(
                    'editor_script' => 'tc_order_history_block_editor',
                    'editor_style' => 'tc_order_history_block_editor',
                    'render_callback' => array($this, 'render_order_history_shortcode'),
                    'attributes' => array(
                        'className' => array('type' => 'string')
                    )
                ));
            } else {//when bridge is active
                register_block_type('tickera/woo-add-to-cart', array(
                    'editor_script' => 'tc_woo_add_to_cart_block_editor',
                    'editor_style' => 'tc_woo_add_to_cart_block_editor',
                    'render_callback' => array($this, 'render_woo_add_to_cart_shortcode'),
                    'attributes' => array(
                        'id' => array('type' => 'string'),
                        'show_price' => array('type' => 'boolean'),
                        'className' => array('type' => 'string')
                    )
                ));

                register_block_type('tickera/woo-event-add-to-cart', array(
                    'editor_script' => 'tc_woo_event_add_to_cart_block_editor',
                    'editor_style' => 'tc_woo_event_add_to_cart_block_editor',
                    'render_callback' => array($this, 'render_woo_event_add_to_cart_shortcode'),
                    'attributes' => array(
                        'id' => array('type' => 'string'),
                        'ticket_type_title' => array('type' => 'string'),
                        'price_title' => array('type' => 'string'),
                        'cart_title' => array('type' => 'string'),
                        'className' => array('type' => 'string')
                    )
                ));
            }

            register_block_type('tickera/event-date', array(
                'editor_script' => 'tc_event_date_block_editor',
                'editor_style' => 'tc_event_date_block_editor',
                'render_callback' => array($this, 'render_event_date_shortcode'),
                'attributes' => array(
                    'event_id' => array('type' => 'string'),
                    'className' => array('type' => 'string')
                )
            ));

            register_block_type('tickera/event-location', array(
                'editor_script' => 'tc_event_location_block_editor',
                'editor_style' => 'tc_event_location_block_editor',
                'render_callback' => array($this, 'render_event_location_shortcode'),
                'attributes' => array(
                    'event_id' => array('type' => 'string'),
                    'className' => array('type' => 'string')
                )
            ));

            register_block_type('tickera/event-terms', array(
                'editor_script' => 'tc_event_terms_block_editor',
                'editor_style' => 'tc_event_terms_block_editor',
                'render_callback' => array($this, 'render_event_terms_shortcode'),
                'attributes' => array(
                    'event_id' => array('type' => 'string'),
                    'className' => array('type' => 'string')
                )
            ));

            register_block_type('tickera/event-logo', array(
                'editor_script' => 'tc_event_logo_block_editor',
                'editor_style' => 'tc_event_logo_block_editor',
                'render_callback' => array($this, 'render_event_logo_shortcode'),
                'attributes' => array(
                    'event_id' => array('type' => 'string'),
                    'className' => array('type' => 'string')
                )
            ));

            register_block_type('tickera/event-sponsors-logo', array(
                'editor_script' => 'tc_event_sponsors_logo_block_editor',
                'editor_style' => 'tc_event_sponsors_logo_block_editor',
                'render_callback' => array($this, 'render_event_sponsors_logo_shortcode'),
                'attributes' => array(
                    'event_id' => array('type' => 'string'),
                    'className' => array('type' => 'string')
                )
            ));

            if (class_exists('TC_Seat_Chart')) {
                register_block_type('tickera/seating-charts', array(
                    'editor_script' => 'tc_seating_chart_block_editor',
                    'editor_style' => 'tc_seating_chart_block_editor',
                    'render_callback' => array($this, 'render_seating_charts_shortcode'),
                    'attributes' => array(
                        'id' => array('type' => 'string'),
                        'show_legend' => array('type' => 'string'),
                        'button_title' => array('type' => 'string'),
                        'subtotal_title' => array('type' => 'string'),
                        'cart_title' => array('type' => 'string')
                    )
                ));
            }

            /* if (class_exists('TC_Event_Calendar')) {
              register_block_type('tickera/event-calendar', array(
              'editor_script' => 'tc_event_calendar_block_editor',
              'editor_style' => 'tc_event_calendar_block_editor',
              'render_callback' => array($this, 'render_event_calendar_shortcode'),
              'attributes' => array(
              'color_scheme' => array('type' => 'string'),
              'lang' => array('type' => 'string'),
              'show_past_events' => array('type' => 'string'),
              )
              ));
              } */
        }

        /**
         * Register extra scripts needed.
         */
        function register_extra_scripts() {

            $wp_tickets_search = new TC_Tickets_Search('', '', -1);
            $ticket_types = array();
            $ticket_types[] = array(0, '');

            foreach ($wp_tickets_search->get_results() as $ticket_type) {
                $ticket = new TC_Ticket($ticket_type->ID);
                $ticket_types[] = array($ticket_type->ID, $ticket->details->post_title);
            }

            $wp_events_search = new TC_Events_Search('', '', -1);
            $events = array();
            $events[] = array(0, '');

            foreach ($wp_events_search->get_results() as $event_item) {
                $event = new TC_Ticket($event_item->ID);
                $events[] = array($event_item->ID, $event->details->post_title);
            }

            if (apply_filters('tc_bridge_for_woocommerce_is_active', false) == false) {
                //Ticket add to cart block
                wp_register_script(
                        'tc_add_to_cart_block_editor', plugins_url('blocks-assets/add_to_cart/tc_add_to_cart_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_add_to_cart_block_editor', 'tc_add_to_cart_block_editor_ticket_types', array(
                    'ticket_types' => json_encode($ticket_types),
                        )
                );

                wp_enqueue_script('tc_add_to_cart_block_editor');

                wp_enqueue_style(
                        'tc_add_to_cart_block_editor', plugins_url('blocks-assets/add_to_cart/tc_add_to_cart_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );

                //Event tickets cart block
                wp_register_script(
                        'tc_event_add_to_cart_block_editor', plugins_url('blocks-assets/event_add_to_cart/tc_event_add_to_cart_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_event_add_to_cart_block_editor', 'tc_event_add_to_cart_block_editor_events', array(
                    'events' => json_encode($events),
                        )
                );

                wp_enqueue_script('tc_event_add_to_cart_block_editor');

                wp_enqueue_style(
                        'tc_event_add_to_cart_block_editor', plugins_url('blocks-assets/event_add_to_cart/tc_event_add_to_cart_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );

                //Event Tickets Sold block

                wp_register_script(
                        'tc_event_tickets_sold_block_editor', plugins_url('blocks-assets/event_tickets_sold/tc_event_tickets_sold_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_event_tickets_sold_block_editor', 'tc_event_tickets_sold_block_editor_events', array(
                    'events' => json_encode($events),
                        )
                );

                wp_enqueue_script('tc_event_tickets_sold_block_editor');

                wp_enqueue_style(
                        'tc_event_event_tickets_sold_block_editor', plugins_url('blocks-assets/event_tickets_sold/tc_event_tickets_sold_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );

                //Event Tickets Left block

                wp_register_script(
                        'tc_event_tickets_left_block_editor', plugins_url('blocks-assets/event_tickets_left/tc_event_tickets_left_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_event_tickets_left_block_editor', 'tc_event_tickets_left_block_editor_events', array(
                    'events' => json_encode($events),
                        )
                );

                wp_enqueue_script('tc_event_tickets_left_block_editor');

                wp_enqueue_style(
                        'tc_event_event_tickets_left_block_editor', plugins_url('blocks-assets/event_tickets_left/tc_event_tickets_left_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );

                //Tickets Sold block

                wp_register_script(
                        'tc_tickets_sold_block_editor', plugins_url('blocks-assets/tickets_sold/tc_tickets_sold_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_tickets_sold_block_editor', 'tc_tickets_sold_block_editor_events', array(
                    'ticket_types' => json_encode($ticket_types),
                        )
                );

                wp_enqueue_script('tc_tickets_sold_block_editor');

                wp_enqueue_style(
                        'tc_tickets_sold_block_editor', plugins_url('blocks-assets/tickets_sold/tc_tickets_sold_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );

                //Tickets Left block

                wp_register_script(
                        'tc_tickets_left_block_editor', plugins_url('blocks-assets/tickets_left/tc_tickets_left_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_tickets_left_block_editor', 'tc_tickets_left_block_editor_events', array(
                    'ticket_types' => json_encode($ticket_types),
                        )
                );

                wp_enqueue_script('tc_tickets_left_block_editor');

                wp_enqueue_style(
                        'tc_tickets_left_block_editor', plugins_url('blocks-assets/tickets_left/tc_tickets_left_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );

                //Order History block

                wp_register_script(
                        'tc_order_history_block_editor', plugins_url('blocks-assets/order_history/tc_order_history_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_enqueue_script('tc_order_history_block_editor');

                wp_enqueue_style(
                        'tc_order_history_block_editor', plugins_url('blocks-assets/order_history/tc_order_history_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );
            } else {//show bridge blocks
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => '_tc_is_ticket',
                            'compare' => '=',
                            'value' => 'yes'
                        ),
                    ),
                    'fields' => 'ids'
                );

                $product_ids = array();
                $product_ids[] = array(0, '');

                $products = get_posts($args);

                foreach ($products as $ticket_type_key => $ticket_type_id) {
                    $post_title = get_the_title($ticket_type_id);
                    $product_ids[] = array($ticket_type_id, $post_title);
                }

                wp_register_script(
                        'tc_woo_add_to_cart_block_editor', plugins_url('blocks-assets/woo_add_to_cart/tc_woo_add_to_cart_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_woo_add_to_cart_block_editor', 'tc_woo_add_to_cart_block_editor_ticket_types', array(
                    'ticket_types' => json_encode($product_ids),
                        )
                );

                wp_enqueue_script('tc_woo_add_to_cart_block_editor');

                wp_enqueue_style(
                        'tc_woo_add_to_cart_block_editor', plugins_url('blocks-assets/woo_add_to_cart/tc_woo_add_to_cart_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );

                //Event tickets cart block
                wp_register_script(
                        'tc_woo_event_add_to_cart_block_editor', plugins_url('blocks-assets/woo_event_add_to_cart/tc_woo_event_add_to_cart_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_woo_event_add_to_cart_block_editor', 'tc_woo_event_add_to_cart_block_editor_events', array(
                    'events' => json_encode($events),
                        )
                );

                wp_enqueue_script('tc_woo_event_add_to_cart_block_editor');

                wp_enqueue_style(
                        'tc_woo_event_add_to_cart_block_editor', plugins_url('blocks-assets/woo_event_add_to_cart/tc_woo_event_add_to_cart_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );
            }

            //Event Date block

            wp_register_script(
                    'tc_event_date_block_editor', plugins_url('blocks-assets/event_date/tc_event_date_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
            );

            wp_localize_script('tc_event_date_block_editor', 'tc_event_date_block_editor_events', array(
                'events' => json_encode($events),
                    )
            );

            wp_enqueue_script('tc_event_date_block_editor');

            wp_enqueue_style(
                    'tc_event_event_date_block_editor', plugins_url('blocks-assets/event_date/tc_event_date_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
            );

            //Event Location block

            wp_register_script(
                    'tc_event_location_block_editor', plugins_url('blocks-assets/event_location/tc_event_location_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
            );

            wp_localize_script('tc_event_location_block_editor', 'tc_event_location_block_editor_events', array(
                'events' => json_encode($events),
                    )
            );

            wp_enqueue_script('tc_event_location_block_editor');

            wp_enqueue_style(
                    'tc_event_event_location_block_editor', plugins_url('blocks-assets/event_location/tc_event_location_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
            );

            //Event Terms & Conditions block

            wp_register_script(
                    'tc_event_terms_block_editor', plugins_url('blocks-assets/event_terms/tc_event_terms_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
            );

            wp_localize_script('tc_event_terms_block_editor', 'tc_event_terms_block_editor_events', array(
                'events' => json_encode($events),
                    )
            );

            wp_enqueue_script('tc_event_terms_block_editor');

            wp_enqueue_style(
                    'tc_event_event_terms_block_editor', plugins_url('blocks-assets/event_terms/tc_event_terms_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
            );

            //Event Logo block

            wp_register_script(
                    'tc_event_logo_block_editor', plugins_url('blocks-assets/event_logo/tc_event_logo_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
            );

            wp_localize_script('tc_event_logo_block_editor', 'tc_event_logo_block_editor_events', array(
                'events' => json_encode($events),
                    )
            );

            wp_enqueue_script('tc_event_logo_block_editor');

            wp_enqueue_style(
                    'tc_event_event_logo_block_editor', plugins_url('blocks-assets/event_logo/tc_event_logo_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
            );

            //Event Sponsors Logo block

            wp_register_script(
                    'tc_event_sponsors_logo_block_editor', plugins_url('blocks-assets/event_sponsors_logo/tc_event_sponsors_logo_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
            );

            wp_localize_script('tc_event_sponsors_logo_block_editor', 'tc_event_sponsors_logo_block_editor_events', array(
                'events' => json_encode($events),
                    )
            );

            wp_enqueue_script('tc_event_sponsors_logo_block_editor');

            wp_enqueue_style(
                    'tc_event_event_sponsors_logo_block_editor', plugins_url('blocks-assets/event_sponsors_logo/tc_event_sponsors_logo_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
            );

            if (class_exists('TC_Seat_Chart')) {
                //Seating chart block

                $seating_charts_ids = array();
                $seating_charts_ids[] = array(0, '');

                $args = array(
                    'post_type' => 'tc_seat_charts',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'no_found_rows' => true
                );

                $seat_charts = get_posts($args);

                foreach ($seat_charts as $seat_chart) {
                    $seating_charts_ids[] = array($seat_chart->ID, $seat_chart->post_title);
                }

                wp_register_script(
                        'tc_seating_charts_block_editor', plugins_url('blocks-assets/seating_charts/tc_seating_charts_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
                );

                wp_localize_script('tc_seating_charts_block_editor', 'tc_seating_charts_block_editor', array(
                    'seating_charts' => json_encode($seating_charts_ids),
                        )
                );

                wp_enqueue_script('tc_seating_charts_block_editor');

                wp_enqueue_style(
                        'tc_seating_charts_block_editor', plugins_url('blocks-assets/seating_charts/tc_seating_charts_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
                );
            }

            /* if (class_exists('TC_Event_Calendar')) {
              $schemes_ids = array();

              $schemes_ids = array(
              array('default' => __('Default', 'tc')),
              array('blue' => __('Blue', 'tc')),
              array('dark' => __('Dark', 'tc')),
              array('flat' => __('Flat', 'tc')),
              array('orange' => __('Orange', 'tc')),
              array('red' => __('Red', 'tc')),
              );

              $lang_ids = array();

              $lang_ids = array(
              array('en' => __('English', 'tc')),
              array('ar-ma' => __('Arabic (Morocco)', 'tc')),
              array('ar-sa' => __('Arabic (Saudi Arabia)', 'tc')),
              array('ar-tn' => __('Arabic (Tunisia)', 'tc')),
              array('ar' => __('Arabic', 'tc')),
              array('bg' => __('Bulgarian', 'tc')),
              array('ca' => __('Catalan', 'tc')),
              array('cs' => __('Czech', 'tc')),
              array('da' => __('Danish', 'tc')),
              array('de-at' => __('German (Austria)', 'tc')),
              array('de' => __('German', 'tc')),
              array('el' => __('Greek', 'tc')),
              array('en-au' => __('English (Australia)', 'tc')),
              array('en-ca' => __('English (Canada)', 'tc')),
              array('en-gb' => __('English (United Kingdom)', 'tc')),
              array('es' => __('Spanish', 'tc')),
              array('fi' => __('Finnish', 'tc')),
              array('fr-ca' => __('French (Canada)', 'tc')),
              array('fr' => __('French', 'tc')),
              array('he' => __('Hebrew', 'tc')),
              array('hi' => __('Hindi (India)', 'tc')),
              array('hr' => __('Croatian', 'tc')),
              array('hu' => __('Hungarian', 'tc')),
              array('id' => __('Indonesian', 'tc')),
              array('is' => __('Icelandic', 'tc')),
              array('it' => __('Italian', 'tc')),
              array('ja' => __('Japanese', 'tc')),
              array('ko' => __('Korean', 'tc')),
              array('lt' => __('Lithuanian', 'tc')),
              array('lv' => __('Latvian', 'tc')),
              array('nb' => __('Norwegian Bokmål (Norway)', 'tc')),
              array('nl' => __('Dutch', 'tc')),
              array('pl' => __('Polish', 'tc')),
              array('pt-br' => __('Portuguese (Brazil)', 'tc')),
              array('pt' => __('Portuguese', 'tc')),
              array('ro' => __('Romanian', 'tc')),
              array('ru' => __('Russian', 'tc')),
              array('sk' => __('Slovak', 'tc')),
              array('sl' => __('Slovenian', 'tc')),
              array('sr-cyrl' => __('Serbian Cyrillic', 'tc')),
              array('sr' => __('Serbian', 'tc')),
              array('sv' => __('Swedish', 'tc')),
              array('th' => __('Thai', 'tc')),
              array('tr' => __('Turkish', 'tc')),
              array('uk' => __('Ukrainian', 'tc')),
              array('vi' => __('Vietnamese', 'tc')),
              array('zh-cn' => __('Chinese (China)', 'tc')),
              array('zh-tw' => __('Chinese (Taiwan)', 'tc')),
              );

              wp_register_script(
              'tc_event_calendar_block_editor', plugins_url('blocks-assets/event_calendar/tc_event_calendar_block_editor.js', __FILE__), array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'), $this->version
              );

              wp_localize_script('tc_event_calendar_block_editor', 'tc_event_calendar_block_editor', array(
              'color_schemes' => json_encode($schemes_ids),
              'languages' => json_encode($lang_ids),
              )
              );

              wp_enqueue_script('tc_event_calendar_block_editor');

              wp_enqueue_style(
              'tc_event_calendar_block_editor', plugins_url('blocks-assets/event_calendar/tc_event_calendar_block_editor.css', __FILE__), array('wp-edit-blocks'), $this->version
              );
              } */
        }

        function render_add_to_cart_shortcode($attributes) {
            ob_start();
            $show_price = ($attributes['show_price'] == true || $attributes['show_price'] == 1) ? 'show_price="true"' : '';
            $quantity = ($attributes['quantity'] == true || $attributes['quantity'] == 1) ? 'quantity="true"' : '';
            $price_position = ($attributes['price_position'] == 'after') ? 'price_position="after"' : 'price_position="before"';
            
            if (empty($attributes['ticket_type_id']) || $attributes['ticket_type_id'] == '0') {
                return __('Please select a ticket type in the block settings box', 'tc');
            }
            echo do_shortcode('[tc_ticket id="' . (int) $attributes['ticket_type_id'] . '" souldout_message="' . $attributes['souldout_message'] . '" ' . $show_price . ' ' . $price_position . ' ' . $quantity . ' type="' . $attributes['link_type'] . '"]');
            $content = ob_get_clean();
            return $content;
        }

        function render_event_add_to_cart_shortcode($attributes) {
            ob_start();
            $quantity = ($attributes['quantity'] == true || $attributes['quantity'] == 1) ? 'quantity="true"' : '';

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            if (!empty($attributes['button_title'])) {
                $button_title = 'title="' . $attributes['button_title'] . '"';
            }

            if (!empty($attributes['ticket_type_title'])) {
                $ticket_type_title = 'ticket_type_title="' . $attributes['ticket_type_title'] . '"';
            }

            if (!empty($attributes['price_title'])) {
                $price_title = 'price_title="' . $attributes['price_title'] . '"';
            }

            if (!empty($attributes['cart_title'])) {
                $cart_title = 'cart_title="' . $attributes['cart_title'] . '"';
            }

            if (!empty($attributes['quantity_title'])) {
                $quantity_title = 'quantity_title="' . $attributes['quantity_title'] . '"';
            }

            if (!empty($attributes['soldout_message'])) {
                $soldout_message = 'soldout_message="' . $attributes['soldout_message'] . '"';
            }

            if (!empty($attributes['link_type'])) {
                $type = 'type="' . $attributes['link_type'] . '"';
            }

            echo do_shortcode('[tc_event id="' . (int) $attributes['event_id'] . '" ' . $button_title . ' ' . $ticket_type_title . ' ' . $price_title . ' ' . $cart_title . ' ' . $quantity_title . ' ' . $soldout_message . '  ' . $type . ' ' . $quantity . ']'); //
            $content = ob_get_clean();

            
            $content = trim($content);
            if (empty($content)) {
                echo __('No assiciated ticket types found. Try selecting another event.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_date_shortcode($attributes) {
            ob_start();

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            echo do_shortcode('[tc_event_date event_id="' . (int) $attributes['event_id'] . '"]');

            $content = ob_get_clean();
            
            $content = trim($content);
            if (empty($content)) {
                echo __('Event date is not set.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_location_shortcode($attributes) {
            ob_start();

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            echo do_shortcode('[tc_event_location id="' . (int) $attributes['event_id'] . '"]');

            $content = ob_get_clean();
            
            $content = trim($content);
            if (empty($content)) {
                echo __('Event location is not set.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_terms_shortcode($attributes) {
            ob_start();

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            echo do_shortcode('[tc_event_terms id="' . (int) $attributes['event_id'] . '"]');

            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo __('Event Terms & Conditions are not set.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_logo_shortcode($attributes) {
            ob_start();

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            echo do_shortcode('[tc_event_logo id="' . (int) $attributes['event_id'] . '"]');

            $content = ob_get_clean();
            
            $content = trim($content);
            if (empty($content)) {
                echo __('Logo is not set for this event.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_sponsors_logo_shortcode($attributes) {
            ob_start();

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            echo do_shortcode('[tc_event_sponsors_logo id="' . (int) $attributes['event_id'] . '"]');

            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo __('Sponsors logo / image is not set for this event.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_tickets_sold_shortcode($attributes) {
            ob_start();

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            echo do_shortcode('[event_tickets_sold event_id="' . (int) $attributes['event_id'] . '"]');

            $content = ob_get_clean();

            
            $content = trim($content);
            if (empty($content)) {
                echo '0';
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_tickets_left_shortcode($attributes) {
            ob_start();

            if (empty($attributes['event_id']) || $attributes['event_id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            echo do_shortcode('[event_tickets_left event_id="' . (int) $attributes['event_id'] . '"]');

            $content = ob_get_clean();

            
            $content = trim($content);
            if (empty($content)) {
                echo '0';
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_tickets_sold_shortcode($attributes) {
            ob_start();

            if (empty($attributes['ticket_type_id']) || $attributes['ticket_type_id'] == '0') {
                return __('Please select a ticket type in the block settings box', 'tc');
            }

            echo do_shortcode('[tickets_sold ticket_type_id="' . (int) $attributes['ticket_type_id'] . '"]');

            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo '0';
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_tickets_left_shortcode($attributes) {
            ob_start();

            if (empty($attributes['ticket_type_id']) || $attributes['ticket_type_id'] == '0') {
                return __('Please select a ticket type in the block settings box', 'tc');
            }

            echo do_shortcode('[tickets_left ticket_type_id="' . (int) $attributes['ticket_type_id'] . '"]');

            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo '0';
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_order_history_shortcode($attributes) {
            ob_start();

            echo do_shortcode('[tc_order_history]');

            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo __('Oops! You personally don\'t have anything in the order history so we can\'t show a preview here. Sorry :/');
                $content = ob_get_clean();
            }

            return $content;
        }

        //Bridge shortcodes
        function render_woo_add_to_cart_shortcode($attributes) {
            ob_start();
            $show_price = ($attributes['show_price'] == true || $attributes['show_price'] == 1) ? 'show_price="true"' : 'show_price=" false"';

            if (empty($attributes['id']) || $attributes['id'] == '0') {
                return __('Please select a ticket type (product) in the block settings box', 'tc');
            }
            echo do_shortcode('[add_to_cart id="' . $attributes['id'] . '" ' . $show_price . ' style="border:none;"]');
            $content = ob_get_clean();
            return $content;
        }

        function render_woo_event_add_to_cart_shortcode($attributes) {
            ob_start();

            if (empty($attributes['id']) || $attributes['id'] == '0') {
                return __('Please select an event in the block settings box', 'tc');
            }

            if (!empty($attributes['ticket_type_title'])) {
                $ticket_type_title = 'ticket_type_title="' . $attributes['ticket_type_title'] . '"';
            }

            if (!empty($attributes['price_title'])) {
                $price_title = 'price_title="' . $attributes['price_title'] . '"';
            }

            if (!empty($attributes['cart_title'])) {
                $cart_title = 'cart_title="' . $attributes['cart_title'] . '"';
            }

            echo do_shortcode('[tc_wb_event  id="' . (int) $attributes['id'] . '" ' . $ticket_type_title . ' ' . $price_title . ' ' . $cart_title . ']'); //
            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo __('No assiciated ticket types (products) found. Try selecting another event.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_seating_charts_shortcode($attributes) {
            ob_start();

            if (empty($attributes['id']) || $attributes['id'] == '0') {
                return __('Please select a seating chart block settings box', 'tc');
            }

            $show_legend = ($attributes['show_legend'] == true || $attributes['show_legend'] == 1) ? 'show_legend="true"' : '';

            if (!empty($attributes['button_title'])) {
                $button_title = 'button_title="' . $attributes['button_title'] . '"';
            }

            if (!empty($attributes['subtotal_title'])) {
                $subtotal_title = 'subtotal_title="' . $attributes['subtotal_title'] . '"';
            }

            if (!empty($attributes['cart_title'])) {
                $cart_title = 'cart_title="' . $attributes['cart_title'] . '"';
            }

            echo do_shortcode('[tc_seat_chart id="' . (int) $attributes['id'] . '" ' . $button_title . ' ' . $subtotal_title . ' ' . $cart_title . ' ' . $show_legend . ']'); //
            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo __('No seating charts found. Sorry :(', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

        function render_event_calendar_shortcode($attributes) {
            ob_start();

            $show_past_events = ($attributes['show_past_events'] == true || $attributes['show_past_events'] == 1) ? 'show_past_events="true"' : '';

            if (!empty($attributes['color_scheme'])) {
                $color_scheme = 'color_scheme="' . $attributes['color_scheme'] . '"';
            } else {
                $color_scheme = 'color_scheme="default"';
            }

            if (!empty($attributes['lang'])) {
                $lang = 'lang="' . $attributes['lang'] . '"';
            } else {
                $lang = 'lang="en"';
            }

            echo do_shortcode('[tc_calendar color_scheme="' . $color_scheme . '" lang="' . $lang . '" ' . $show_past_events . ']'); //
            $content = ob_get_clean();

            $content = trim($content);
            if (empty($content)) {
                echo __('Something went wrong and we don\'t have a clue why the content is not shown :( Please contact support for futher assistance.', 'tc');
                $content = ob_get_clean();
            }

            return $content;
        }

    }

}

$TC_tc_gutentick = new TC_tc_gutentick();
?>
