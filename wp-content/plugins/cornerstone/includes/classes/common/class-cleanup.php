<?php

class Cornerstone_Cleanup extends Cornerstone_Plugin_Component {

  public function clean_generated_styles() {
    global $wpdb;
    $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cs_generated_styles' ) );
  }

}
