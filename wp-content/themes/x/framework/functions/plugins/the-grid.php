<?php

// =============================================================================
// FUNCTIONS/GLOBAL/PLUGINS/THE-GRID.PHP
// -----------------------------------------------------------------------------
// Plugin setup for theme compatibility.
// =============================================================================

// =============================================================================
// TABLE OF CONTENTS
// -----------------------------------------------------------------------------
//   01. Filter for The Grid plugin compatibility
//   02. Filter to remove The Grid registration Panel
// =============================================================================

// Filter For Grid Compatibility
// =============================================================================

add_filter( 'tg_grid_un13306812', '__return_true' );


// Filter to remove The Grid registration Panel
// =============================================================================

function x_tg_grid_unregister() {
  return __( 'This is an exclusive deal for X and Pro users. Your validated theme license unlocks all features of The Grid including the skin builder. Updates to The Grid and support are also included as part of your theme purchase.', '__x__' );
}

add_filter( 'tg_grid_unregister', 'x_tg_grid_unregister' );

// Remove Upsell
// =============================================================================

function x_tg_remove_notices( $hook_name, $class_name, $method_name, $priority ) {
  global $wp_filter;
  if (
      ! isset( $wp_filter[ $hook_name ][ $priority ] ) ||
      ! is_array( $wp_filter[ $hook_name ][ $priority ] )
  ) {
      return;
  }
  foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
      if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
          if (
              is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) == $class_name &&
              $filter_array['function'][1] == $method_name
           ) {
              unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
          }
      }
  }
  return;
}

x_tg_remove_notices( 'admin_notices', 'The_Grid_Admin', 'remove_notices_start', 10 );
x_tg_remove_notices( 'admin_notices', 'The_Grid_Admin', 'remove_notices_end', 999 );
