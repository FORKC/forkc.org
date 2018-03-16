<?php

class Cornerstone_Element_Definition {

  protected $type;
  public $def = array();
  protected $style = null;
  protected $ready_for_builder = false;
  protected $html_safe_keys;

  public function __construct( $type, $definition ) {
    $this->type = $type;
    $this->update( $definition );
  }

  public function update( $update ) {

    $defaults = array(

      'title'          => '',
      'values'         => array(),
      'options'        => array(),
      'style'          => null,

      'builder'        => null,
      'controls'       => array(),
      'control_groups' => array(),
      // 'conditions'     => array(),
      // 'supports'       => array(),
      'icon'           => null,
      'active'         => true,

      'render'         => null,

      '_upgrade_data' => array()
    );

    $this->def = array_merge( $defaults, $this->def, array_intersect_key( $update, $defaults ) );

  }

  public function get_defaults() {
    $defaults = array();

    foreach ($this->def['values'] as $key => $value) {
      $defaults[$key] = $value['default'];
    }

    return $defaults;
  }

  public function apply_defaults( $data ) {
    $defaults = $this->get_defaults();

    foreach ($defaults as $key => $value) {
      if ( ! isset( $data[$key] ) ) {
        $data[$key] = $value;
      }
    }

    return $data;

  }

  public function get_designations() {
    $designations = array();

    foreach ($this->def['values'] as $key => $value) {
      $designations[$key] = $value['designation'];
    }

    return $designations;
  }

  public function get_designated_keys( $type, $sub_group = null ) {

    $designations = $this->get_designations();
    $keys = array();

    foreach ($designations as $key => $value) {
      $parts = explode(':', $value);
      $designation_type = array_shift($parts);
      $designation_sub_group = implode(':', $parts);
      $sub_group_check = is_null( $sub_group ) ? true : $sub_group === $designation_sub_group;
      if ( $type === $designation_type && $sub_group_check ) {
        $keys[] = $key;
      }
    }
    return $keys;
  }

  public function get_style_template() {

    if ( is_null( $this->style ) ) {

      if ( ! isset( $this->def['style'] ) ) {
        return '';
      }

      $this->style = is_callable( $this->def['style'] ) ? call_user_func( $this->def['style'], $this->type ) : $this->def['style'];

    }

    return $this->style;
  }

  public function get_compiled_style() {
    // $path = "/Users/rohmann/Sites/styles/{$this->type}-style.php";
    // $cached = include( $path );
    // if ( ! $cached ) {
      return CS()->loadComponent('Element_Manager')->compile_style_template( $this->get_style_template() );
      // $file = '<?php return ' . var_export($style, true) . ';';
      // file_put_contents("/Users/rohmann/Sites/styles/{$this->type}-style.php", $file );
      // $cached = $style;
    // }

    // return $cached;
  }


  // Redundant. Could be removed if all style template processing was done client side in the builder.
  public function preprocess_style( $data, $class_prefix ) {

    $data = $this->apply_defaults($data);
    $data['_el'] = $class_prefix . $data['_id'];

    $style_keys = $this->get_designations();

    $post_process_keys = array();
    foreach ($style_keys as $data_key => $style_key) {

      $pos = strpos($style_key, ':' );

      if ( false === $pos ) {
        continue;
      }

      $post_process_keys[$data_key] = substr($style_key, $pos + 1);

    }

    if ( empty( $post_process_keys ) ) {
      return $data;
    }

    // function preProcess( data ) {
    foreach ($data as $key => $value) {
      if ( isset($post_process_keys[$key])) {
        $data[$key] = '%%post ' . $post_process_keys[$key] . '%%' . $value .'%%/post%%';
      }
    }

    return $data;

  }

  public function get_title() {
    return $this->def['title'];
  }

  public function get_type() {
    return $this->type;
  }

  public function serialize() {

    $this->update_for_builder();

    $data = array(
      'id'             => $this->type,
      'title'          => $this->def['title'],
      'options'        => $this->def['options'],
      'values'         => $this->def['values'],
      'style'          => $this->get_compiled_style(),
      'controls'       => $this->def['controls'],
      'control_groups' => $this->def['control_groups'],
      'active'         => $this->def['active'],
    );

    if ( is_string( $this->def['icon'] ) ) {
      $data['icon'] = $this->def['icon'];
    }

    return $data;
  }

  public function update_for_builder() {

    if ( $this->ready_for_builder || ! is_callable( $this->def['builder'] ) ) {
      return;
    }

    $this->update( call_user_func( $this->def['builder'], $this->type ) );
    $this->ready_for_builder = true;

  }

  public function condition_check() {
    return true;
  }

  public function sanitize( $data ) {

    $sanitized = array();
    if ( ! isset( $this->html_safe_keys ) ) {
      $markup_keys = $this->get_designated_keys('markup', 'html' );
      $attr_keys = $this->get_designated_keys('attr', 'html' );
      $raw_keys = $this->get_designated_keys('markup', 'raw' );
      $this->html_safe_keys = array_merge( $markup_keys, $attr_keys, $raw_keys );
    }

    $internal_keys = array( '_id', '_type', '_region', '_modules', '_transient' );

    foreach ( $data as $key => $value ) {

      // Pass through internal data
      if ( in_array($key, $internal_keys, true ) ) {
        $sanitized[ $key ] = $value;
        continue;
      }

      // Strip undesignated values
      if ( ! isset( $this->def['values'][$key] ) ) {
        continue;
      }

      $sanitized[ $key ] = CS()->common()->sanitize_value( $value, in_array($key, $this->html_safe_keys, true ) );

    }

    return $sanitized;
  }

  public function save( $data, $content ) {

    $tag = "cs_element_" . str_replace('-', '_', $data['_type'] );
    $_id = $data['_id'];

    $shortcode = "[$tag _id=\"$_id\"]";

    if ( ! $content && isset( $this->def['options']['fallback_content'] ) ) {
      $content = $this->def['options']['fallback_content'];
    }

    if ( $content ) {

      $shortcode .= $content . "[/$tag]";
    }

    return $shortcode;
  }

  public function render( $data ) {
    return is_callable( $this->def['render'] ) ? call_user_func( $this->def['render'], $this->sanitize( $data ) ) : '';
  }

}
