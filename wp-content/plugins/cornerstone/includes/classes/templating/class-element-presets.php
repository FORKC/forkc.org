<?php

class Cornerstone_Element_Presets extends Cornerstone_Plugin_Component {

  public $presets = array();

  public function setup() {

    $presets = array();

    $filename = CS()->path('includes/elements/presets/presets.php');
    if ( file_exists( $filename ) ) {
      $presets = include( $filename);
    }

    $presets = apply_filters('cornerstone_element_presets', $presets );

    foreach ($presets as $preset) {
      $this->add_preset( $preset );
    }

  }

  public function get_presets() {
    $presets = array();
    foreach ($this->presets as $id => $preset) {
      $preset['id'] = $id;
      $presets[] = $preset;
    }
    return $presets;
  }

  public function add_preset( $data ) {

    $name = sanitize_title_with_dashes( $data['element'] . '-' . $data['title'] );
    $id = $name;
    $count = 0;
    while( isset( $this->presets[ $id ] )  ) {
      $id = $name . '-' . $count++;
    }

    $this->presets[ $id ] = $data;
  }

}
