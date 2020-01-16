<?php

// Theme Constants
// =============================================================================

define( 'X_VERSION', '7.1.2' );
define( 'X_CS_VERSION', '4.1.2' );
define( 'X_SLUG', 'x' );
define( 'X_TITLE', 'X' );
define( 'X_I18N_PATH', X_TEMPLATE_PATH . '/framework/functions/x/i18n');


// if ( ! defined('X_ASSET_REV') ) {
//   define( 'X_ASSET_REV', X_VERSION );
// }

// Require Cornerstone
// =============================================================================

function x_require_cornerstone( $tgmpa ) {

  $tgmpa->register( array(
    'name'        => 'Cornerstone',
    'slug'        => 'cornerstone',
    'source'      => X_TEMPLATE_PATH . '/framework/cornerstone.zip',
    'required'    => true,
    'version'     => X_CS_VERSION,
    'is_callable' => 'CS'
  ) );

}

add_action( 'x_tgmpa_register', 'x_require_cornerstone' );

// App Environment Data
// =============================================================================

function x_cornerstone_app_env( $env ) {
  $env['product'] = 'x';
  $env['title'] = X_TITLE;
  $env['version'] = X_VERSION;
  $env['productKey'] = esc_attr( get_option( 'x_product_validation_key', '' ) );
  return $env;
}

add_filter( '_cornerstone_app_env', 'x_cornerstone_app_env' );

// Label Replacements
// =============================================================================

function x_cornerstone_toolbar_title() {
  return __('X', '__x__');
}

add_filter( '_cornerstone_toolbar_menu_title', 'x_cornerstone_toolbar_title' );



// Version Body Class
// =============================================================================

if ( ! function_exists( 'x_body_class_version' ) ) :
  function x_body_class_version( $output ) {

    $output[] = 'x-v' . str_replace( '.', '_', X_VERSION );
    return $output;

  }
  add_filter( 'body_class', 'x_body_class_version', 10000 );
endif;



// Overview Page Modules
// =============================================================================

add_action( 'x_overview_init', 'x_validation_modules' );

function x_validation_modules() {

  require_once( X_TEMPLATE_PATH . '/framework/functions/x/validation/class-validation-cornerstone.php' );

  X_Validation_Cornerstone::instance();

}

function x_load_preinit() {
  require_once X_TEMPLATE_PATH . '/framework/functions/x/migration.php';
}

add_action('x_boot_preinit', 'x_load_preinit' );
