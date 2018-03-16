<?php

class Cornerstone_Content {

  protected $id = null;
  protected $title;
  protected $post_type = 'post';
  protected $permalink = '';
  protected $data = array();
  // protected $new;
  // protected $dirty;
  // protected $modified;

  public function __construct( $post ) {

    if ( is_array( $post ) ) {
      if ( isset( $post['id'] ) ) {
        $post = $post['id'];
      } else {
        $this->create_new( $post );
      }
    } else {
      $this->load_from_post( $post );
    }

  }

  protected function create_new( $data ) {

    $this->set_title( isset( $data['title'] ) ? $data['title'] : false );
    // TODO: Populate from incoming data.

    $this->set_data( array(
      'elements' => isset( $data['elements'] ) ? $data['elements'] : array(),
      'settings' => isset( $data['settings'] ) ? $data['settings'] : array()
    ) );

  }

  protected function load_from_post( $post ) {

    if ( is_int( $post ) ) {
      $post = get_post( $post );
    }

    if ( ! is_a( $post, 'WP_POST' ) ) {
      throw new Exception( 'Unable to load header from post.' );
    }

    $this->id = $post->ID;
    $this->set_title( $post->post_title ? $post->post_title : '' );
    $this->post_type = $post->post_type;

    $wpml = CS()->loadComponent('Wpml');
    $wpml->before_get_permalink();
    $this->permalink = apply_filters( 'wpml_permalink', get_permalink( $post ), apply_filters('cs_locate_wpml_language', null, $post ) );
    $wpml->after_get_permalink();


    $elements = cs_get_serialized_post_meta( $this->id, '_cornerstone_data', true );

    if ( ! is_array( $elements ) ) {
      $elements = array( 'data' => '' );
    }

    // TODO: Load Settings from manager
    $settings = cs_get_serialized_post_meta( $this->id, '_cornerstone_settings', true );

    if ( ! is_array( $settings ) ) {
      $settings = array();
    }

    $this->set_data( array(
      'elements' => $this->normalize_elements( $elements ),
      'settings' => $this->normalize_settings( $settings )
    ) );

  }

  protected function normalize_elements( $elements ) {

    if ( ! isset( $elements['data'] ) ) {

      $migrations = CS()->loadComponent('Element_Migrations');
      $elements = array(
        'data' => $migrations->migrate_classic( $elements )
      );

    }

    return $elements;
  }

  protected function normalize_settings( $settings ) {
    return $settings;
  }

  protected function set_data( $data ) {

    $data = wp_parse_args( is_array( $data ) ? $data : array(), array(
      'elements' => array(),
      'settings' => array(),
    ) );

    $this->set_elements( $data['elements'] );
    $this->set_settings( $data['settings'] );

  }

  public function save() {

    $settings = $this->update_settings( $this->get_settings() );

    if ( is_wp_error( $settings ) ) {
      throw new Exception( 'Error saving content settings: ' . $settings->get_error_message() );
    }

    $elements = $this->update_elements( $this->get_elements() );

    if ( is_wp_error( $elements ) ) {
      throw new Exception( 'Error saving content elements: ' . $elements->get_error_message() );
    }

    return $this->serialize();

  }

  public function get_id() {
    return $this->id;
  }

  public function get_title() {
    return $this->title;
  }

  public function get_elements() {
    if ( ! isset( $this->data['elements'] ) ) {
      $this->data['elements'] = array( 'data' => '');
    }
    return $this->data['elements'];
  }

  public function get_settings() {
    if ( ! isset( $this->data['settings'] ) ) {
      $this->data['settings'] = array();
    }
    return $this->data['settings'];
  }

  public function serialize() {
    return array(
      'id' => $this->id,
      'title' => $this->get_title(),
      'post_type' => $this->post_type,
      'permalink' => $this->permalink,
      'elements'  => $this->get_elements(),
      'settings' => $this->get_settings()
    );
  }

  public function set_title( $title ) {
    return $this->title = sanitize_text_field( $title, sprintf( csi18n('common.untitled-entity'), csi18n('common.entity-content') ) );
  }

  public function set_settings( $settings ) {
    $this->data['settings'] = $settings;
  }

  public function set_elements( $elements ) {
    $this->data['elements'] = $elements;
  }

  public function delete() {
    do_action('cornerstone_delete_content', $this->id );
    return wp_delete_post( $this->id, true );
  }


  public function update_elements( $elements ) {

    CS()->loadComponent( 'Element_Orchestrator' )->load_elements();
    $output = $this->build_output( $elements );

		if ( is_wp_error( $output ) ) {
			return $output;
		}

    do_action( 'cornerstone_before_save_content', $this->id );

		$old_version = get_post_meta( $this->id, '_cornerstone_version', true );
    update_post_meta( $this->id, '_cornerstone_version', CS()->version() );

		cs_update_serialized_post_meta( $this->id, '_cornerstone_data', $output['data'] );
		delete_post_meta( $this->id, '_cornerstone_override' );
    delete_post_meta( $this->id, '_cs_generated_styles');

		$post_content = '[cs_content]' . apply_filters( 'cornerstone_save_post_content', $output['content'] ) . '[/cs_content]';

		$id = wp_update_post( array(
			'ID'           => $this->id,
      'title'        => $this->get_title(),
			'post_content' => wp_slash( $post_content ),
		) );

    if ( is_wp_error( $id ) ) {
      return $id;
    }

    if ( 0 === $id ) {
      return new WP_Error('cs-content', "Unable to save content: $id");
    }

		$post_type = get_post_type( $this->id );

		if ( false !== $post_type && post_type_supports( $post_type, 'excerpt' ) ) {
			update_post_meta( $this->id, '_cornerstone_excerpt', cs_derive_excerpt( $post_content, true ) );
		}

    do_action( 'cornerstone_after_save_content', $this->id );

    return true;
  }

  public function build_output( $elements ) {

    // Generate shortcodes
    $buffer = '';
    $sanitized = array();

    $elements = CS()->loadComponent('Regions')->populate_modules('content', $elements, 'content');

    foreach ( $elements as $element ) {
      $output = $this->build_element_output( $element );
      if ( is_wp_error( $output ) ) {
        return $output;
      }
      $buffer .= $output['content'];
      $sanitized[] = $output['data'];
    }


    return array(
      'content' => $buffer,
      'data' => $sanitized
    );
  }

  public function build_element_output( $element, $parent = null ) {

    if ( ! isset( $element['_type'] ) ) {
      return new WP_Error( 'Cornerstone_Save_Handler', 'Element _type not set: ' . maybe_serialize( $element ) );
    }

    if ( 0 === strpos( $element['_type'], 'classic:' ) ) {
      return $this->build_classic_element_output( $element, $parent );
    }

    //
    // Build V2 element
    //

    $buffer = '';

    if ( isset( $element['_modules'] ) ) {
      $sanitized = array();
      foreach ( $element['_modules'] as $child ) {
        $output = $this->build_element_output( $child, $element );
        if ( is_wp_error( $output ) ) {
          return $output;
        }
        $buffer .= $output['content'];
        $sanitized[] = $output['data'];
      }
      $element['_modules'] = $sanitized;
    }


    $content = '';
    if ( ! isset( $element['_active'] ) || $element['_active'] ) {
      $content = CS()->loadComponent( 'Element_Manager' )->get_element( $element['_type'] )->save( $element, $buffer );
    }

    unset($element['_id']);
    unset($element['_region']);

    return array(
      'content' => $content,
      'data' => $element
    );

  }

  public function build_classic_element_output( $element, $parent = null ) {

    $element['_type'] = str_replace('classic:', '', $element['_type'] );
    $definition = CS()->component( 'Element_Orchestrator' )->get( $element['_type'] );
    $element = $definition->sanitize( $element );

    if ( 'mk1' === $definition->version() ) {
      return CS()->loadComponent( 'Legacy_Renderer' )->save_element( $element );
    }

    $flags = $definition->flags();

    if ( ! isset( $flags['child'] ) || ! $flags['child'] ) {
      $parent = null;
    }

    $buffer = '';

    if ( isset( $element['_modules'] ) ) {
      $sanitized = array();
      foreach ( $element['_modules'] as $child ) {
        $output = $this->build_element_output( $child, $definition->compose( $element ) );
        if ( is_wp_error( $output ) ) {
          return $output;
        }
        $buffer .= $output['content'];
        $sanitized[] = $output['data'];
      }
      $element['_modules'] = $sanitized;
    }


    $content = '';
    if ( ! isset( $element['_active']) || $element['_active'] ) {
      if ( isset($element['_modules'] ) ) {
        $element['elements'] = $element['_modules'];
      }
      $content = $definition->build_shortcode( $element, $buffer, $parent );
      unset($element['elements']);
    }

    $element['_type'] = 'classic:' . $element['_type'];
    unset($element['_id']);
    unset($element['_region']);

    return array(
      'content' => $content,
      'data' => $element
    );

  }

  public function update_settings( $settings ) {

    global $post;

    $post = get_post( $this->id );
    setup_postdata( $post );

    CS()->loadComponent( 'Settings_Manager' )->load();

		foreach ( $settings as $section => $data ) {

			$result = $this->save_setting( $section, $data );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

    wp_reset_postdata();

		return true;
	}

	protected function save_setting( $section, $data ) {


		$sectionManager = CS()->loadComponent( 'Settings_Manager' )->get( $section );

		if ( is_null( $sectionManager ) ) {
			return null;
		}

		return $sectionManager->save( $data );

	}


}
