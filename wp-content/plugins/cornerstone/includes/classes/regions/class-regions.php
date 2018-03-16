<?php

class Cornerstone_Regions extends Cornerstone_Plugin_Component {

  public $header_styles = '';
  public $dependencies = array( 'Styling' );
  public $modules = array();
  public $modules_registered = false;
  public $counters = array();

  public function setup() {
    $this->register_post_types();
  }

  public function register_header_styles( $header ) {

    if ( false === $header ) {
      return;
    }

    $styling = $this->plugin->component( 'Styling' );
    $styling->add_styles( 'header', $this->get_region_styles( 'header', $header ) );

    if ( isset( $header['settings']['customCSS'] ) && $header['settings']['customCSS'] ) {
      $styling->add_styles( 'header-custom', $header['settings']['customCSS'] );
    }

  }

  public function register_footer_styles( $footer ) {

    if ( false === $footer ) {
      return;
    }

    $styling = $this->plugin->component( 'Styling' );

    $styling->add_styles( 'footer', $this->get_region_styles( 'footer', $footer ) );

    if ( isset( $footer['settings']['customCSS'] ) && $footer['settings']['customCSS'] ) {
      $styling->add_styles( 'footer-custom', $footer['settings']['customCSS'] );
    }
  }

  public function get_content_elements( $id ) {
    $this->plugin->loadComponent( 'Element_Manager' );
    $elements = cs_get_serialized_post_meta( $id, '_cornerstone_data', true );

    if ( ! $elements ) {
      return null;
    }

    return $this->populate_modules( 'content', $elements, 'content');
  }

  public function get_content_styles( $id, $elements ) {

    $generated = get_post_meta( $id, '_cs_generated_styles', true );

    if ( ! $generated ) {
      if ( ! $elements ) {
        return '';
      }
      $generated = $this->plugin->loadComponent( 'Element_Manager' )->generate_styles( 'content', $elements );
      update_post_meta( $id, '_cs_generated_styles', $generated );
    }

    return $generated;
  }


  public function get_region_styles( $mode, $entity ) {

    $element_manager = $this->plugin->loadComponent( 'Element_Manager' );

    if ( ! isset( $entity['id'] ) ) {
      return $element_manager->generate_styles( $mode, $entity['modules'] );
    }

    $generated = get_post_meta( $entity['id'], '_cs_generated_styles', true );
    if ( ! $generated ) {
      $generated = $element_manager->generate_styles( $mode, $entity['modules'] );
      update_post_meta( $entity['id'], '_cs_generated_styles', $generated );
    }

    return $generated;
  }

  public function reset_region_styles( $mode, $entity ) {
    delete_post_meta( $entity->get_id(), '_cs_generated_styles');
    $this->get_region_styles( $mode, $this->prepare_region_data( $mode, $entity ) );
  }





  public function load_element_style( $type ) {
    return $this->plugin->loadComponent('Element_Manager')->get_element( $type )->get_style_template();
  }

  public function preprocess_element_style( $type, $data ) {
    return $this->plugin->loadComponent('Element_Manager')->get_element( $type )->preprocess_style( $data );
  }

  public function get_fallback_header_data() {
    return apply_filters( 'cornerstone_fallback_header_data', array(
      'modules' => array(),
      'settings' => array(),
    ) );
  }

  public function get_fallback_footer_data() {
    return apply_filters( 'cornerstone_fallback_footer_data', array(
      'modules' => array(),
      'settings' => array(),
    ) );
  }

  public function get_active_header_data( $fallback = false ) {

    $assignment = has_filter('cornerstone_header_preview_data') ?
                  apply_filters('cornerstone_header_preview_data', array() ) :
                  $this->plugin->loadComponent('Header_Assignments')->locate_assignment( $fallback );


    if ( is_null( $assignment ) && ! $fallback ) {
      return null;
    }

    try {
      $header = new Cornerstone_Header( $assignment );
    } catch( Exception $e ) {
      $header = new Cornerstone_Header( $this->get_fallback_header_data() );
    }

    return $this->prepare_region_data( 'header', $header );
  }

  public function prepare_region_data( $mode, $entity ) {

    $modules = array();
    $regions = $entity->get_regions();

    foreach ($regions as $name => $region ) {

      $new = array(
        '_type' => 'region',
        '_region' => $name,
        '_modules' => $this->populate_modules( $mode, $region, $name )
      );

      $modules[] = $new;
    }

    return array(
      'id'      => $entity->get_id(),
      'modules' => $this->flatten_regions( $modules ),
      'settings' => $entity->get_settings(),
    );
  }

  public function get_active_footer_data( $fallback = false ) {

    $assignment = has_filter('cornerstone_footer_preview_data') ?
                  apply_filters('cornerstone_footer_preview_data', array() ) :
                  $this->plugin->loadComponent('Footer_Assignments')->locate_assignment( $fallback);

    if ( is_null( $assignment ) && ! $fallback ) {
      return null;
    }

    try {
      $footer = new Cornerstone_Footer( $assignment );
    } catch( Exception $e ) {
      $footer = new Cornerstone_Footer( $this->get_fallback_footer_data() );
    }

    return $this->prepare_region_data( 'footer', $footer );

  }

  public function flatten_regions( $regions ) {
    $modules = array();

    foreach ( $regions as $region ) {
      foreach ( $region['_modules'] as $module ) {
        $modules[] = $module;
      }
    }

    return $modules;
  }

  public function flatten_elements( $elements ) {
    $this->flatten_elements_buffer = array();
    foreach ($elements as $element ) {

      $this->flatten_element($element);
    }

    $buffer = $this->flatten_elements_buffer;
    $this->flatten_elements_buffer = array();
    return $buffer;

  }

  public function flatten_element( $element ) {

    if ( isset( $element['_modules']) ) {
      foreach ($element['_modules'] as $child ) {
        $this->flatten_element($child);
      }
    }

    if ( isset($element['_id']) ) {
      unset($element['_modules']);
      $this->flatten_elements_buffer['el' . $element['_id']] = $element;
    }

  }

  public function sanitize_regions( $regions ) {

    $element_manager = $this->plugin->loadComponent('Element_Manager');
    $sanitized = array();

    foreach ($regions as $region_name => $bars) {
      if ( is_array( $bars ) ) {
        $sanitized[$region_name] = $element_manager->sanitize_elements( $bars );
      }
    }

    return $sanitized;
  }

  public function populate_modules( $mode, $modules, $region, $recursive = false ) {

    if ( ! isset( $this->counters[ $mode ] ) ) { // || false === $recursive <--- y u no werk?
      $this->counters[ $mode ] = 0;
    }

    foreach ( $modules as $index => $module ) {

      $modules[$index]['_id'] = ++$this->counters[ $mode ];
      $modules[$index]['_region'] = $region;

      if ( isset( $module['_modules'] ) ) {
        $modules[$index]['_modules'] = $this->populate_modules( $mode, $module['_modules'], $region, true );
      }

    }

    return $modules;

  }

  public function register_post_types() {

    register_post_type( 'cs_header', array(
      'public'          => false,
      'capability_type' => 'page',
      'supports'        => false
    ) );

    register_post_type( 'cs_footer', array(
      'public'          => false,
      'capability_type' => 'page',
      'supports'        => false
    ) );

  }

  public function blank_template() {
    return array(
      'id' => 'blank',
      'title' => csi18n('common.blank'),
      'regions' => array(),
      'settings' => array()
    );
  }

  public function get_header_templates() {
    $templates = apply_filters( 'cornerstone_header_templates', array() );
    $templates[] = $this->blank_template();
    return $templates;
  }

  public function get_footer_templates() {
    $templates = apply_filters( 'cornerstone_footer_templates', array() );
    $templates[] = $this->blank_template();
    return $templates;
  }



}
