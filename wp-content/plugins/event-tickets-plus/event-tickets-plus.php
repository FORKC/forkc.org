<?php
/*
Plugin Name: Event Tickets Plus
Description: Event Tickets Plus lets you sell tickets to events, collect custom attendee information, and more! Includes advanced options like shared capacity between tickets, ticket QR codes, and integrations with your favorite ecommerce provider.
Version: 4.9.1
Author: Modern Tribe, Inc.
Author URI: http://m.tri.be/28
License: GPLv2 or later
Text Domain: event-tickets-plus
Domain Path: /lang/
 */

/*
 Copyright 2010-2012 by Modern Tribe Inc and the contributors

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without seven the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( ! defined( 'ABSPATH' ) ) die( '-1' );

define( 'EVENT_TICKETS_PLUS_DIR', dirname( __FILE__ ) );
define( 'EVENT_TICKETS_PLUS_FILE', __FILE__ );

/* Defer loading Event Tickets Plus until we know that Event Tickets itself has
 * completed setup (which may not happen in some situations, such as if an
 * incompatible version of The Events Calendar is active).
 */
add_action( 'tribe_tickets_plugin_loaded', 'event_tickets_plus_init' );
add_action( 'tribe_tickets_plugin_failed_to_load', 'event_tickets_plus_setup_fail_message' );

// If we get to this action and neither of the above two actions fired, we'll need to show an error
add_action( 'plugins_loaded', 'event_tickets_plus_check_for_init_failure', 9 );

function event_tickets_plus_init() {

	tribe_init_tickets_plus_autoloading();
	event_tickets_plus_setup_textdomain();

	if ( event_tickets_plus_is_incompatible_tickets_core_installed() ) {
		do_action( 'tribe_tickets_plugin_failed_to_load' );
		return;
	}

	tribe( 'tickets-plus.main' )->on_load();
}

/**
 * Sets up the textdomain stuff
 */
function event_tickets_plus_setup_textdomain() {
	if ( defined( 'EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED' ) && EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED ) {
		return;
	}

	$mopath = trailingslashit( basename( dirname( __FILE__ ) ) ) . 'lang/';
	$domain = 'event-tickets-plus';

	// If we don't have Common classes load the old fashioned way
	if ( ! class_exists( 'Tribe__Main' ) ) {
		load_plugin_textdomain( $domain, false, $mopath );
	} else {
		// This will load `wp-content/languages/plugins` files first
		Tribe__Main::instance()->load_text_domain( $domain, $mopath );
	}

	define( 'EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED', true );
}

/**
 * Requires the autoloader class from the main plugin class and sets up
 * autoloading.
 */
function tribe_init_tickets_plus_autoloading() {
	if ( ! class_exists( 'Tribe__Autoloader' ) ) {
		return;
	}

	$autoloader = Tribe__Autoloader::instance();
	$autoloader->register_prefix( 'Tribe__Tickets_Plus__', dirname( __FILE__ ) . '/src/Tribe', 'event-tickets-plus' );
	$autoloader->register_autoloader();

	$plugin_path = trailingslashit( EVENT_TICKETS_PLUS_DIR );

	require_once( $plugin_path . 'src/functions/template-tags.php' );

	tribe_singleton( 'tickets-plus.main', 'Tribe__Tickets_Plus__Main' );
}

/**
 * Whether the current version is incompatible with the installed and active The Events Calendar
 * @return bool
 */
function event_tickets_plus_is_incompatible_tickets_core_installed() {
	if ( ! class_exists( 'Tribe__Tickets__Main' ) ) {
		return true;
	}

	if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
		return true;
	}

	if ( ! version_compare( Tribe__Tickets__Main::VERSION, Tribe__Tickets_Plus__Main::REQUIRED_TICKETS_VERSION, '>=' ) ) {
		return true;
	}

	return false;
}

/**
 * Hooks up the failure message.
 */
function event_tickets_plus_setup_fail_message() {
	add_action( 'admin_notices', 'event_tickets_plus_show_fail_message' );
}

/**
 * Shows an admin_notices message explaining why it couldn't be activated.
 */
function event_tickets_plus_show_fail_message() {
	event_tickets_plus_setup_textdomain();

	if ( ! current_user_can( 'activate_plugins' ) )
		return;

	$url = add_query_arg( array(
		'tab'       => 'plugin-information',
		'plugin'    => 'event-tickets',
		'TB_iframe' => 'true',
	), admin_url( 'plugin-install.php' ) );

	$title = esc_html__( 'Event Tickets', 'event-tickets-plus' );

	echo '<div class="error"><p>';

	printf(
		esc_html__( 'To begin using Event Tickets Plus, please install and activate the latest version of %1$s%2$s%3$s and ensure its own requirements have been met.', 'event-tickets-plus' ),
		'<a href="' . esc_url( $url ) . '" class="thickbox" title="' . $title . '">',
		$title,
		'</a>'
	);

	echo '</p></div>';
}

/**
 * Last ditch effort to display an error message in the event that Event Tickets didn't even load
 * far enough to fire tribe_tickets_plugin_loaded or tribe_tickets_plugin_failed_to_load
 */
function event_tickets_plus_check_for_init_failure() {
	if ( defined( 'EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED' ) && EVENT_TICKETS_PLUS_TEXTDOMAIN_LOADED ) {
		return;
	}

	event_tickets_plus_setup_fail_message();
}
