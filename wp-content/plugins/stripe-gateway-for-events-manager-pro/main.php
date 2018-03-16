<?php
/*
Plugin Name: Stripe Gateway - Events Manager Pro
Plugin URI: http://oliveconcepts.com/downloads
Description: Stripe Payment Gateway for Events Manager Pro plugin. Its give credit card option to process payment in your site.
Version: 1.3
Author: Oliveconcepts
Author URI: http://oliveconcepts.com/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * events manager pro is a pre-requirements
 */
function em_stripe_prereq() {
    ?> <div class="error"><p><?php _e('Please ensure you have <a href="http://eventsmanagerpro.com/">Events Manager Pro</a> installed, as this is a requirement for the Stripe add-on.','em-stripe'); ?></p>
       </div>
    <?php
}

/**
 * Set meta links in the plugins page 
 */
function em_stripe_metalinks( $actions, $file, $plugin_data ) {
    $new_actions = array();
    $new_actions[] = sprintf( '<a href="'.EM_ADMIN_URL.'&amp;page=events-manager-gateways&amp;action=edit&amp;gateway=emp_stripe">%s</a>', __('Settings', 'em-stripe') );
    $new_actions = array_merge( $new_actions, $actions );
    return $new_actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'em_stripe_metalinks', 10, 3 );


/**
 * initialise plugin once other plugins are loaded 
 */
function em_stripe_register() {
	//check that EM Pro is installed
	if( ! defined( 'EMP_VERSION' ) ) {
		add_action( 'admin_notices', 'em_stripe_prereq' );
		return false; //don't load plugin further
	}
	
	if (class_exists('EM_Gateways')) {
		require_once( plugin_dir_path( __FILE__ ) . 'gateway.stripe.php' );
		EM_Gateways::register_gateway('emp_stripe', 'EM_Gateway_Stripe');
	}
	// LOCALIZATION
	load_plugin_textdomain('em-stripe', false, dirname( plugin_basename( __FILE__ ) ).'/languages');	
}
add_action( 'plugins_loaded', 'em_stripe_register', 1000);
?>