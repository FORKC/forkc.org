<?php

class Cornerstone_Preview_Frame_Loader extends Cornerstone_Plugin_Component {

  protected $state = false;
  protected $zones = array();
  protected $frame = null;
  protected $prefilter_option_updates = array();
  protected $prefilter_meta_updates = array();

  public function setup() {

    if ( ! isset( $_POST['cs_preview_state'] ) || ! $_POST['cs_preview_state'] || 'off' === $_POST['cs_preview_state'] ) {
      return;
    }

    // Nonce verification
    if ( ! isset( $_POST['_cs_nonce'] ) || ! wp_verify_nonce( $_POST['_cs_nonce'], 'cornerstone_nonce' ) ) {
      echo -1;
      die();
    }

    $this->state = json_decode( base64_decode( $_POST['cs_preview_state'] ), true );

    do_action('cs_before_preview_frame', $this->state);

    add_filter( 'show_admin_bar', '__return_false' );
    add_action( 'template_redirect', array( $this, 'load' ), 0 );
    add_action( 'shutdown', array( $this, 'frame_signature' ), 1000 );
    add_filter( 'wp_die_handler', array( $this, 'remove_preview_signature' ) );

    add_filter( "get_post_metadata", array( $this, 'prefilter_meta_handler' ), 10, 4 );

    $route = ( isset( $this->state['route'] ) ) ? $this->state['route'] : 'app';
    $frame_component = cs_to_component_name( $route ) . '_Preview_Frame';
    $this->frame = $this->plugin->loadComponent( $frame_component );

    if ( ! $this->frame ) {
      throw new Exception( "Requested frame handler '$frame_component' does not exist." );
    }

    if ( isset( $this->state['noClient'] ) ) {
      return;
    }

    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
    add_action( 'wp_footer', array( $this, 'route_config' ) );

    $this->zones = $this->plugin->loadComponent('Common')->get_preview_zones();
    foreach ( $this->zones as $zone ) {
      add_action( $zone, array( $this, 'zone_output' ) );
    }

  }

  public function load() {
    nocache_headers();
  }

  public function zone_output() {
    echo '<div data-cs-zone="' . current_action() . '"></div>';
  }

  public function get_state() {
    return $this->state;
  }

  public function data() {

    if ( ! $this->state ) {
      return array( 'timestamp' => $this->state);
    }

    return array(
      'timestamp' => $this->state['timestamp']
    );

  }

  public function frame_signature() {
    echo 'CORNERSTONE_FRAME';
  }

  public function remove_preview_signature( $return = null ) {
    remove_action( 'shutdown', array( $this, 'frame_signature' ), 1000 );
    return $return;
  }

  public function enqueue() {
    $this->plugin->loadComponent( 'App' )->register_app_scripts( $this->plugin->settings(), true );
    wp_enqueue_script( 'mediaelement' );
    wp_enqueue_script( 'cs-app' );
    wp_enqueue_style( 'cs-preview', $this->plugin->css( 'preview', true ), null, $this->plugin->version() );
  }

  public function route_config() {


    if ( isset( $this->state['route'] ) ) {
      echo '<script type="application/json" data-cs-preview-route="' . $this->state['route'] . '">';
      if ( is_callable( array( $this->frame, 'config' ) ) ) {
        echo json_encode( apply_filters( 'cs_preview_frame_route_config', $this->frame->config( $this->state ), $this->state['route'] ) );
      }
      echo '</script>';
    }

  }

  public function prefilter_options( $updates ) {
    $this->prefilter_option_updates = array_merge( $this->prefilter_option_updates, $updates );
    foreach ($updates as $key => $value) {
      add_filter( "pre_option_$key", array( $this, 'prefilter_option_handler' ) );
    }
  }

  public function prefilter_option_handler($value) {

    $option_name = preg_replace( '/^pre_option_/', '', current_filter() );

    if ( isset( $this->prefilter_option_updates[ $option_name ] ) ) {
      $value = apply_filters( 'option_' . $option_name, $this->prefilter_option_updates[ $option_name ] );
    }

    return $value;
  }

  public function prefilter_meta( $id, $updates ) {

    $key = 'o' . $id;

    if ( ! isset( $this->prefilter_meta_updates[ $key ] ) ) {
      $this->prefilter_meta_updates[ $key ] = array();
    }

    $this->prefilter_meta_updates[ $key ] = array_merge( $this->prefilter_meta_updates[ $key ], $updates );

  }

  public function prefilter_meta_handler( $value, $object_id, $meta_key, $single ) {
    if ( isset( $this->prefilter_meta_updates['o' . $object_id ] ) && isset( $this->prefilter_meta_updates['o' . $object_id ][$meta_key] ) ) {
      $value = $this->prefilter_meta_updates['o' . $object_id ][$meta_key];
      if ( ! $single ) {
        $value = array( $value );
      }
    }
    return $value;
  }

}
