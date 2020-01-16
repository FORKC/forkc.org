<?php

// =============================================================================
// CORNERSTONE/INCLUDES/ELEMENTS/CONTROL-PARTIALS/OMEGA.PHP
// -----------------------------------------------------------------------------
// Element Controls
// =============================================================================

// =============================================================================
// TABLE OF CONTENTS
// -----------------------------------------------------------------------------
//   01. Controls
// =============================================================================

// Controls
// =============================================================================

function x_control_partial_omega( $settings ) {

  // Setup
  // -----

  $conditions            = ( isset( $settings['conditions'] )            ) ? $settings['conditions']            : array();
  $title                 = ( isset( $settings['title'] )                 ) ? $settings['title']                 : false;
  $add_custom_atts       = ( isset( $settings['add_custom_atts'] )       ) ? $settings['add_custom_atts']       : false;
  $add_style             = ( isset( $settings['add_style'] )             ) ? $settings['add_style']             : false;
  $add_toggle_hash       = ( isset( $settings['add_toggle_hash'] )       ) ? $settings['add_toggle_hash']       : false;
  $toggle_hash_condition = ( isset( $settings['toggle_hash_condition'] ) ) ? $settings['toggle_hash_condition'] : false;


  // Data
  // ----

  $control_setup = array(
    'type'       => 'omega',
    'group'      => 'omega:setup',
    'conditions' => $conditions,
    'options'    => array(),
    'priority'   => 0
  );

  if ( ! empty( $title ) ) {
    $control['label'] = $title;
  }


  // Keys
  // ----

  $keys = array(
    'id'    => 'id',
    'class' => 'class',
    'css'   => 'css',
    'bp'    => 'hide_bp'
  );

  if ( $add_style ) {
    $keys['style'] = 'style';
  }

  if ( $add_toggle_hash ) {
    $keys['toggle_hash'] = 'toggle_hash';
  }

  if ( $toggle_hash_condition ) {
    $control_setup['options']['toggle_hash_condition'] = $toggle_hash_condition;
  }

  $control_setup['keys'] = $keys;

  $controls = array( $control_setup );

  if ( $add_custom_atts ) {
    $controls[] = array(
      'key'        => 'custom_atts',
      'type'       => 'attributes',
      'group'      => 'omega:setup',
      'label'      => __( 'Custom Attributes', '__x__' ),
    );
  }

  return array(
    'controls' => $controls,
    'controls_std_customize' => array( $control_setup ),
    'control_nav' => array(
      'omega'       => __( 'Customize', '__x__' ),
      'omega:setup' => __( 'Setup', '__x__' ),
    )
  );
}

cs_register_control_partial( 'omega', 'x_control_partial_omega' );
