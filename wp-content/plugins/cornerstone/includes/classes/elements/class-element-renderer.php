<?php

class Cornerstone_Element_Renderer extends Cornerstone_Plugin_Component {

  public $dependencies = array( 'Front_End' );
  public $zones = array();
  public $zone_output = array();

  public function start( $context ) {

    $this->zones = $this->plugin->loadComponent('Common')->get_preview_zones();

    $this->setup_context_all();
    do_action('cs_element_rendering');

    if ( isset( $context['mode'] ) ) {
      $data = isset( $context['data'] ) ? $context['data'] : array();
      do_action('cs_element_rendering_' . $context['mode'], $data );
      $setup_context_method = array( $this, 'setup_context_' . $context['mode'] );
      if ( is_callable( $setup_context_method ) ) {
        call_user_func( $setup_context_method, $data );
      }
    }

    $this->enqueue_extractor = $this->plugin->loadComponent( 'Enqueue_Extractor' );
    $this->enqueue_extractor->start();

  }

  public function zone_siphen_start() {
    ob_start();
  }

  public function zone_siphen_end() {

    $content = ob_get_clean();

    if ( $content ) {
      $this->zone_output[current_action()] = $this->restore_html( $content );
    }

  }

  public function end() {

  }

  public function get_extractions() {
    return array(
      'scripts' => $this->enqueue_extractor->get_scripts(),
      'styles'  => $this->enqueue_extractor->get_styles()
    );
  }

  public function render_element( $data ) {

    $response = '';
    $this->zone_output = array();

    if ( 'markup' === $data['action'] ) {

      $module = array();

      $definition = CS()->loadComponent('Element_Manager')->get_element( $data['model']['_type'] );

      /**
       * Attach zone output siphens
       */

      foreach ( $this->zones as $zone ) {
        remove_all_actions( $zone );
        add_action( $zone, array( $this, 'zone_siphen_start' ), 0 );
      }

      $attr_keys = $definition->get_designated_keys( 'attr' );
      $html_keys = $definition->get_designated_keys('markup', 'html' );

      if ( 0 === strpos($definition->get_type(), 'classic:' ) ) {
        $html_keys = array();
      }


      /**
       * Replace keys designated as attributes with {{atts.key_name}}
       */

      foreach ($attr_keys as $key) {
        $module[$key] = "{{model.atts.$key}}"; //"{{model.{{camelize::attr_$key}}}}";
      }

      $this->html_cache = array();
      foreach ($data['model'] as $key => $value) {

        if ( in_array($key, $attr_keys, true) ) {
          continue;
        }

        if ( in_array($key, $html_keys, true) ) {
          $module[$key] = $this->isolate_html( $key, $value );
          continue;
        }

        $module[$key] = $value;

      }

      if ( isset($module['_id'])) {
        $module['_id'] = '{{model.id}}';
      }

      /**
       * Render the module using a registered filter
       */
      ob_start();
      $definition->render( $module );
      $response = ob_get_clean();

      /**
       * Restore Isolated HTML
       */

      $response = $this->restore_html( $response );



      /**
       * Add htmlSafe helper to atts inside style attributes
       */
      $response = preg_replace_callback('/style="(.+?)"/', array( $this, 'add_htmlsafe_helper' ), $response);

      /**
       * Add data-cs-observeable on root element if not supplied by view
       */
      if ( -1 !== strpos($response, 'data-cs-observeable' ) ) {
        $response = preg_replace('/<\s*?\w+\s?/', "$0 data-cs-observeable=\"{{observer}}\" ", $response, 1 );
      }

      /**
       * Capture output that was deffered into any registered zones
       */

      foreach ( $this->zones as $zone ) {
        add_action( $zone, array( $this, 'zone_siphen_end' ), 9999999 );
        do_action( $zone );
      }

      foreach ($this->zone_output as $key => $value) {
        $html = preg_replace('/<!--(.|\n)*?-->/', '', $value);
        $encoded = json_encode( array( 'markup' => base64_encode($html) ) );
        $response .= "{{#preview/zone-pipe model=model zone=\"$key\"}}$encoded{{/preview/zone-pipe}}";
      }

    }

    //sleep(1);
    return array(
      'template' => $response,
      'extractions' => array(
        'scripts' => $this->enqueue_extractor->extract_scripts(),
        'styles' => $this->enqueue_extractor->extract_styles()
      )
    );
  }

  public function isolate_html( $key, $content ) {

    $content = do_shortcode( $content );
    if ( ! $content ) {
      return '';
    }

    $this->html_cache[$key]  = '{{base64content "' . base64_encode( $content ) . '" }}';
    return "{{isolated_html $key}}";
  }

  public function restore_html( $content ) {
    return preg_replace_callback( "/{{isolated_html (\w+)}}/s", array( $this, 'restore_html_callback' ), $content );
  }

  public function restore_html_callback($matches) {
    return $this->html_cache[$matches[1]];
  }

  public function setup_context_all() {
    add_filter( 'x_breadcrumbs_data', 'x_bars_sample_breadcrumbs', 10, 2 );
    add_filter('cornerstone_css_post_process_color', array( $this, 'post_process_attr' ) );
    add_filter('cornerstone_css_post_process_font-family', array( $this, 'post_process_attr') );
    add_filter('cornerstone_css_post_process_font-weight', array( $this, 'post_process_attr') );

  }

  public function setup_context_content( $data ) {

    if ( ! isset($data['post_id']) ) {
      return;
    }

    $this->class_prefix = 'el';
    add_filter('cs_element_class_prefix', array( $this, 'set_class_prefix' ) );

    add_action( 'x_section', 'cornerstone_preview_container_output' );
    add_action( 'x_row', 'cornerstone_preview_container_output' );
    add_action( 'x_column', 'cornerstone_preview_container_output' );

    global $post;

    $post = get_post( (int) $data['post_id'] );

    if ( ! is_null( $post ) ) {
      setup_postdata( $post );
    }

  }


  public function set_class_prefix( $prefix ) {
    return $this->class_prefix;
  }

  public function add_htmlsafe_helper( $matches ) {
    return str_replace('{{model.atts', '{{hs model.atts', $matches[0]);
  }

  public function post_process_attr( $value ) {

    if ( preg_match('/{{(model\.atts.*?)}}/', $value, $matches ) ) {
      if ( isset($matches[1]) ) {
        $attr = $matches[1];
        return "{{post-process-attr $attr processer=model.definition}}";
      }
    };

    return $value;
  }

}
