<?php

/**
 * This class exists to manage a persistent index
 * of which items have been imported already.
 *
 * This ensures items are not duplicated, and that if a
 * user modifies demo content it will not be overwritten.
 */
class Cornerstone_Site_Import_Registry {

  public $namespace;
  public $registry;

  /**
   * Provision this instance with a namespace and populate
   * registry with any existing values.
   * @param string $namespace Unique namespace
   * @return none
   */
  public function setNameSpace( $namespace ) {
    $this->namespace = $namespace;
    $option = get_option('x_design_cloud_site_registry', array() );
    $this->registry = isset($option[$namespace]) ? $option[$namespace] : array();
  }

  /**
   * Persist data in our namespace to a WP option.
   * @return none
   */
  public function save() {
    $option = get_option('x_design_cloud_site_registry', array() );
    $option[$this->namespace] = $this->registry;
    update_option( 'x_design_cloud_site_registry', $option );
  }

  /**
   * Get an existing value if available
   * @param  string $group Group to check for ID
   * @param  string $key    Hash based ID provided by XDE
   * @return mixed         null if it doesn't exist.
   */
  public function get( $group, $key ) {
    return ( isset( $this->registry[$group] ) &&  isset( $this->registry[$group][$key] ) )
          ? $this->registry[$group][$key] : null;
  }

  /**
   * Returns everything stored in this registry.
   * @return array
   */
  public function all() {
    return $this->registry;
  }

  /**
   * Test if a value exists
   * @param  string $group Group to check for key
   * @param  string $key    Hash based ID provided by XDE
   * @return bool         true/false based on existence
   */
  public function exists( $group, $key ) {
    return (!is_null( $this->get( $group, $key ) ) );
  }

  /**
   * Add a value to our registry
   * @param  string  $group     Group to place the ID in the registry
   * @param  string  $key       unique ID string
   * @param  mixed   $value     Store a reference to an ID or entity on this install
   * @param  boolean $overwrite should existing values be allowed to be overwritten
   * @return boolean            true, otherwise false if id exists
   */
  public function set( $group, $key, $value, $overwrite = true ) {

    if ( !$overwrite && $this->exists( $group, $key ) )
      return false;

    if (!isset($this->registry[$group]))
      $this->registry[$group] = array();

    $this->registry[$group][$key] = $value;
    $this->save();

    return true;
  }

  /**
   * Delete a value from the registry
   * @param  string $group Group to delete a key from.
   * @param  string $key    Hash based ID provided by XDE
   * @return none
   */
  public function delete( $group, $key ) {

    if ( $this->exists( $group, $key ) )
      unset( $this->registry[$group][$key] );

    $this->save();

  }
  /**
   * Deletes a key from a group in all namespaces
   * @param  string $group Group to delete a key from.
   * @param  string $key    Hash based ID provided by XDE
   * @return none
   */
  public static function deleteAll( $group, $key ) {
    $option = get_option('x_design_cloud_site_registry', array() );
    $namespaces = array_keys( $option );
    foreach ( $namespaces as $namespace ) {
      $registry = new Cornerstone_Site_Import_Registry;
      $registry->setNameSpace( $namespace );
      $registry->delete( $group, $key );
    }
  }

}

class Cornerstone_Site_Import_Processor {

  public $batchCount = 0;
  public $maxBatchSize = 2;
  public $registry;

  public function setup( $session ) {

    $this->maxBatchSize = apply_filters( 'x_demo_batch_size', $this->maxBatchSize );

    $this->session = $session;
    $this->namespace = $this->session->get( 'namespace' );
    $this->registry = new Cornerstone_Site_Import_Registry;
    $this->registry->setNameSpace( $this->namespace );

    $this->jobs = $this->session->get('jobs');
    $this->jobs_total = $this->session->get('jobs_total');


    if ( is_null( $this->jobs ) ) {

      $this->jobs = $this->session->get('demo');

      $sliders = $this->session->get('sliders');

      foreach ($sliders as $name => $url) {
        array_unshift( $this->jobs, array(
          'task' => 'Slider',
          'data' => array(
            'name' => $name,
            'url' => $url
        ) ) );
      }

      array_unshift( $this->jobs, array( 'task' => 'Cleanup', 'data' => array() ) );
      $this->jobs_total = count( $this->jobs );

    }

  }

  /**
   * Process the next job in the queue
   * @return none
   */
  public function nextJob() {

    $this->debugMessage = '';

    if ( 0 == count( $this->jobs ) )
      return true;

    // Get the next job
    $job = array_shift( $this->jobs );

    // Ensure an import method exists for the requested task
    $methodName = 'import' . $job['task'];

    if ( !method_exists( $this, $methodName ) ) {
      return new WP_Error( 'cornerstone', 'Task does not have an import method: ' . $job['task'] );
    }

    // Run the job
    $run = call_user_func_array( array( $this, $methodName ), array( $job['data'] ) );
    if ( is_wp_error( $run ) )
      return $run;


    $this->session->set( 'registry', $this->registry->all() );
    $this->save();
    $this->registry->save();

    if ( false === $run ) {
      $this->batchCount++;
      return $this->nextJob();
    }

    if ( $run != 'next' && $this->batchCount <= $this->maxBatchSize ) {
      $this->batchCount++;
      return $this->nextJob();
    } else {
      $this->batchCount = 0;
      return $run;
    }

  }


  public function message() {
    return $this->message;
  }

  public function debugMessage() {
    return $this->debugMessage;
  }

  /**
   * Get a percentage of completion
   * @return int 0-1 value
   */
  public function completion() {

    if ( empty( $this->jobs ) )
      return true;

    return array(
      'total' => $this->jobs_total,
      'remaining' => count($this->jobs),
      'ratio' => ( $this->jobs_total - count($this->jobs) ) / $this->jobs_total
    );

  }

  /**
   * Save our progress in the session.
   * Only persists when the session itself is saved
   * @return none;
   */
  public function save() {
    $this->session->set( 'jobs_total', $this->jobs_total );
    $this->session->set( 'jobs', $this->jobs );
  }

  /**
   * Job handler for cleanup.
   * This deletes any unmodified pages imported from other demos
   * @param  array $data unused
   * @return none
   */
  public function importCleanup( $data ) {

    global $wpdb;
    $cleanup = $wpdb->get_results( "SELECT p.ID,m.meta_value FROM {$wpdb->posts} AS p INNER JOIN {$wpdb->postmeta} AS m ON m.post_id = p.ID AND m.meta_key = 'x_demo_content'" );

    foreach ($cleanup as $item) {

      $split = explode( '|', $item->meta_value );

      if ( count( $split) != 2 || $this->namespace == $split[0] )
        continue;

      Cornerstone_Site_Import_Registry::deleteAll( 'post', $split[1] );
      wp_delete_post( $item->ID, 'x_demo_content' );

    }

    $this->message = __( 'Initializing...', 'cornerstone');
    return 'next';
  }


  public function importSlider( $data ) {


    if (!class_exists('RevSliderSlider'))
      return false;



    if ( RevSliderSlider::isAliasExists( $data['name'] ) )
      return false;

    ob_start();

    try {

      require_once(RS_PLUGIN_PATH . 'admin/includes/template.class.php');
      require_once(RS_PLUGIN_PATH . 'admin/includes/import.class.php');

      $slider = new RevSliderSlider;

      if ( ! function_exists( 'request_filesystem_credentials' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
      }
      ob_start();
      $creds = request_filesystem_credentials( '', '', false, false, null );
      ob_get_clean();

      if ( ! WP_Filesystem( $creds ) ) {
        return new WP_Error( 'cornerstone', 'Unable to use file system.' );
      }

      global $wp_filesystem;

      $dir = wp_upload_dir();
      $file = trailingslashit( $dir['basedir'] )  . basename( $data['url'] );

      $response = wp_remote_get( $data['url'] );

      $wp_filesystem->put_contents(
        $file,
        $response['body'],
        FS_CHMOD_FILE // predefined mode settings for WP files
      );

      $slider->importSliderFromPost( true, true, $file );

      if ( file_exists( $file ) ) {
        wp_delete_file( $file );
      }

    } catch (Exception $e) {
      return new WP_Error( 'cornerstone', $e->getMessage() );
    }

    $this->debugMessage = ob_get_clean();

    $this->message = __('Revolution Slider downloaded...', 'cornerstone');
    return 'text';
  }

  public function importImage( $data ) {

    if ( $this->registry->exists( 'image', $data['id'] ) ) {
      return false;
    }

    if ( ! function_exists( 'download_url' ) ) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $download = download_url( $data['url'], 30 );

    if ( is_wp_error( $download ) ) {
      return new WP_Error('cornerstone', $download->get_error_message() . '|' . $data['url'] );
    }

    $file = array( 'name' => basename($data['url']), 'tmp_name' => $download );
    $results = wp_handle_sideload( $file, array( 'test_form' => false ) );

    if ( !empty( $results['error'] ) )
      return new WP_Error('cornerstone', 'Failed to sideload image: ' + $data['url'] );


    $name  = explode( '.', basename( $results['file'] ) );

    $newPost = wp_insert_attachment( array(
      'post_title' => sanitize_file_name( $name[0]),
      'post_content' => '',
      'post_type' => 'attachment',
      'post_mime_type' => $results['type'],
      'guid' => $results[ 'url' ]
    ), $results['file'] );

    if ( is_wp_error( $newPost ) )
      return $newPost;

    if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
      require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    wp_update_attachment_metadata( $newPost, wp_generate_attachment_metadata( $newPost, $results['file'] ) );

    $results['post_id'] = (int) $newPost;

    $this->registry->set( 'image', $data['id'], $results );

    $this->message = sprintf( __('Downloading images...', 'cornerstone'), basename( $results['url'] ) );
    return true;
  }

  public function updateTermMeta( $term_id, $meta ) {

    $option = 'taxonomy_' . $term_id;

    $term_meta = get_option( $option, array() );

    if ( is_array( $meta ) ) {
      foreach ($meta as $key => $value) {
        $term_meta[$key] = $value;
      }
    }

    update_option( $option, $term_meta );

  }

  public function importTerm( $term ) {

    if ( $term['taxonomy'] == 'nav_menu' )
      return new WP_Error( 'cornerstone', 'Term importer should not be used to process menus.' );

    $parent = $term['parent'];

    if ( $parent != 0 ) {
      $parent = $this->registry->get( 'term', $term['parent'] );
      if ( is_null( $parent ) )
        return new WP_Error( 'cornerstone', 'Export file has hierarchical terms out of order. ');
    }

    $this->message = sprintf( __( 'Preparing taxonomies...', 'cornerstone' ), $term['name'] );

    $existing_term = get_term_by( 'name', $term['name'], $term['taxonomy'] );

    if ( $existing_term != false ) {

      $term_id = (int) $existing_term->term_id;

      wp_update_term( $term_id, $term['taxonomy'], array(
        'description' => $term['description'],
        'slug' => $term['slug'],
        'parent' => $parent
      ));

      $this->updateTermMeta( $term_id, $term['meta'] );
      $this->registry->set( 'term', $term['term_id'], $term_id );

      return true;
    }

    $newTerm = wp_insert_term( $term['name'], $term['taxonomy'], array(
      'description' => $term['description'],
      'slug' => $term['slug'],
      'parent' => $parent
    ));

    if ( is_wp_error( $newTerm ) )
      return $newTerm;

    $this->updateTermMeta( $newTerm['term_id'], $term['meta'] );

    $this->registry->set( 'term', $term['term_id'], $newTerm['term_id'] );

    return true;
  }

  public function createAttachment( $post, $data ) {

      if ( !isset( $post['attachment_url'] ) ) {
        $this->debugMessage = "Attachment missing attachment_url: " . $post['ID'];
        return false;
      }

      $imageData = $this->registry->get( 'image', $this->findFirstImageID( $post['attachment_url'] ) );

      if ( is_null($imageData)) {
        $this->debugMessage = "Attachment not found in registry: " . $post['ID']. " | $id";
        return false;
      }

      $this->registry->set( 'post', $post['ID'], (int) $imageData['post_id'] );

      $this->message = sprintf( __( 'Attaching thumbnails...', 'cornerstone' ), $post['post_title'] );
      return true;
  }

  public function importPost( $post ) {

    if ( $post['post_type'] == 'nav_menu_item' )
      return new WP_Error( 'cornerstone', 'Post importer should not be used to process menu items.' );

    if ( $this->registry->get( 'post', $post['ID'] ) ) {

      if ( ! is_null( get_post( $post['ID'] ) ) ) {
        $this->skipPostMeta( $post['ID'] );
        return false;
      }

      $this->registry->delete( 'post', $post['ID'] );

    }

    $newPost = array();

    // Set pass-through keys. Strip anything set later.
    foreach ($post as $key => $value) {
      if (!in_array($key, array( 'ID', 'post_parent', 'post_content', 'terms', 'post_meta' ) ) )
        $newPost[$key] = $value;
    }

    if ( $post['post_type'] == 'attachment' )
      return $this->createAttachment( $post, $newPost );

    if ( $post['post_type'] == 'cs_global_block' ) {
      $post['post_type'] = 'tco-data';
    }

    // Set content
    $newPost['post_content'] = ( isset( $post['post_content'] ) ) ? $this->normalizeContent( $post['post_content'] ) : '';

    // Set Parent
    $parent = ( isset( $post['post_parent'] ) ) ? $post['post_parent'] : 0;

    if ( $parent != 0 ) {
      $parent = $this->registry->get( 'post', $post['post_parent'] );
      if ( is_null( $parent ) )
        return new WP_Error( 'cornerstone', 'Export file has hierarchical posts out of order. ');
    }

    $newPost['post_parent'] = $parent;

    // Create it!
    $newPost = wp_insert_post( $newPost, true );

    if ( is_wp_error( $newPost ) )
      return $newPost;

    // Add terms to this post
    foreach ( $post['terms'] as $taxonomy => $termIDs ) {

      $newTermIDs = array();

      foreach ($termIDs as $termID) {
        $newTermIDs[] = (int) $this->registry->get( 'term', $termID );
      }


      $taxTermIDs = wp_set_object_terms( $newPost, $newTermIDs, $taxonomy );

      if ( is_wp_error( $taxTermIDs ) )
        return $taxTermIDs;
    }

    $this->registry->set( 'post', $post['ID'], (int) $newPost );

    $this->message = sprintf( __( 'Creating posts...', 'cornerstone' ), $post['post_title'] );
    return true;

  }

  public function skipPostMeta( $id ) {
    $skipped = $this->session->get( 'skipped_post_meta' );
    if ( !is_array( $skipped ) )
      $skipped = array();
    $skipped[] = $id;
    $this->session->set( 'skipped_post_meta', $skipped );
  }

  public function isPostMetaSkipped( $id ) {
    $skipped = $this->session->get( 'skipped_post_meta' );
    return ( is_array( $skipped ) && in_array( $id, $skipped ) );
  }

  public function importPostMeta( $data ) {

    if ( $this->isPostMetaSkipped( $data['id'] ) ) {
      return false;
    }

    $postID = $this->registry->get( 'post', $data['id'] );

    $post = get_post( $postID );

    if ( is_null( $post ) ) {
      return new WP_Error('cornerstone', "Could not locate post: $postID | " . $data['id'] );
    }

    if ( is_wp_error( $post ) ) {
      return $post;
    }

    foreach ($data['meta'] as $key => $value) {

      // Specific cases
      if ( '_thumbnail_id' == $key ) {
        if ( false === set_post_thumbnail( $post->ID, $this->registry->get( 'post', $value ) ) )
          $this->debugMessage = "Could not set thumbnail ($key) to post: {$post->ID} | attachment: $value";
        continue;
      }

      // General purpose
      $newValue = $this->normalizeContent( maybe_unserialize( $value ) );
      $maybe_json = is_string( $newValue ) ? json_decode( $newValue ) : null;

      if ( ! is_null( $maybe_json ) ) {
        // JSON needs to be slashed before storing
        $newValue = wp_slash( $newValue );
      }

      // Skip existing values
      if ( get_post_meta( $post->ID, $key, $value ) == $newValue ) {
        continue;
      }

      if ( false === update_post_meta( $post->ID, $key, $newValue ) ) {
        $this->debugMessage = "Could not add meta key ($key) to post: {$post->ID}";
      }

      if ($key === '_cornerstone_data') {
        $content = new Cornerstone_Content( $post->ID );
        $content->save();
      }

    }

    // Flag this as disposable demo content.
    update_post_meta( $post->ID, 'x_demo_content', $this->namespace . '|' . $data['id'] );

    $this->message = sprintf( __( 'Adding post data...', 'cornerstone' ), $post->post_title );
    return true;

  }

  public function importMenu( $menu ) {

    wp_delete_nav_menu( $menu['menu-name'] );

    $newMenu = wp_update_nav_menu_object( 0, $menu );

    if ( is_wp_error( $newMenu ) ) {
      return $newMenu;
    }

    $this->registry->set( 'term', $menu['id'], (int) $newMenu );

    foreach ( $menu['items'] as $menuItem ) {

      $existing = $this->registry->get( 'post', $menuItem['id'] );

      if ( is_nav_menu_item( $existing ) ) {
        wp_delete_post( $existing, true );
      }

      if ( 'taxonomy' == $menuItem['menu-item-type'] ) {
        $menuItem['menu-item-object-id'] = $this->registry->get( 'term', $menuItem['menu-item-object-id'] );
      } elseif ( 'post_type' == $menuItem['menu-item-type'] ) {
        $menuItem['menu-item-object-id'] = $this->registry->get( 'post', $menuItem['menu-item-object-id'] );
      }


      if ( 0 != (int) $menuItem['menu-item-parent-id'] ) {
        $menuItem['menu-item-parent-id'] = $this->registry->get( 'post', $menuItem['menu-item-parent-id'] );
        if ( is_null( $menuItem['menu-item-parent-id'] ) ) {
          continue;
        }
      }

      $newMenuItem = wp_update_nav_menu_item( $newMenu, 0, $menuItem );

      if ( is_wp_error( $newMenuItem ) ) {
        return $newMenuItem;
      }

      $this->registry->set( 'post', $menuItem['id'], (int) $newMenuItem );

    }

    $this->message = sprintf( __( 'Creating menus...', 'cornerstone' ), $menu['menu-name'] );
    return true;
  }

  public function importSidebar( $data ) {

    $sidebars_widgets = get_option('sidebars_widgets');

    foreach ($data as $sidebar => $widgets) {

      // Move existing widgets to inactive
      if( isset( $sidebars_widgets[$sidebar] ) && !empty($sidebars_widgets[$sidebar] ) ) {

        if ( !isset( $sidebars_widgets['wp_inactive_widgets'] ) ) {
          $sidebars_widgets['wp_inactive_widgets'] = array();
        }

        foreach ( $sidebars_widgets[$sidebar] as $widget_id ) {
          $sidebars_widgets['wp_inactive_widgets'][] = $widget_id;
        }

        $sidebars_widgets[$sidebar] = array();

      }

      // Derived from https://github.com/stevengliebe/widget-importer-exporter
      foreach ($widgets as $widget) {

        if ( $widget['type'] == 'nav_menu' ) {
          $widget['meta']['nav_menu'] = $this->registry->get( 'term', $widget['meta']['nav_menu'] );
        }

        if ( $widget['type'] == 'text' ) {
          $widget['meta']['text'] = $this->normalizeContent( $widget['meta']['text'] );
        }

        $default = array( '_multiwidget' => 1 );
        $allWidgets = get_option( 'widget_' . $widget['type'], $default ); // all instances for that widget ID base, get fresh every time
        if ( empty( $allWidgets ) ) {
          $allWidgets = $default;
        }

        $allWidgets[] = $widget['meta'];

        end( $allWidgets );
        $newId = key( $allWidgets );

        if ( '0' === strval( $newId ) ) {
          $newId = 1;
          $allWidgets[$newId] = $allWidgets[0];
          unset( $allWidgets[0] );
        }

        if ( isset( $allWidgets['_multiwidget'] ) ) {
          $multiwidget = $allWidgets['_multiwidget'];
          unset( $allWidgets['_multiwidget'] );
          $allWidgets['_multiwidget'] = $multiwidget;
        }

        update_option( 'widget_' . $widget['type'], $allWidgets );

        $sidebars_widgets[$sidebar][] = $widget['type'] . '-' . $newId;

      }

    }

    update_option( 'sidebars_widgets', $sidebars_widgets );

    $this->message = 'Preparing sidebars...';
    return true;
  }


  public function importOptions( $data ) {

    if ( isset( $data['page_on_front'] ) ) {

      $postID = $key = $this->registry->get( 'post', $data['page_on_front'] );

      if ( !is_null( $postID ) ) {
        update_option('page_on_front', $postID );
      }

      unset($data['page_on_front']);
    }

    if ( isset( $data['page_for_posts'] ) ) {

      $postID = $this->registry->get( 'post', $data['page_for_posts'] );

      if ( !is_null( $postID ) ) {
        update_option('page_for_posts', $postID );
      }

      unset($data['page_for_posts']);
    }

    if ( isset( $data['cornerstone_font_items'] ) ) {

      update_option(
        'cornerstone_font_items',
        wp_slash( cs_json_encode( $this->mergeOptionModels(
          get_option('cornerstone_font_items', CS()->component('Font_Manager')->default_font_items()),
          $data['cornerstone_font_items']
        ) ) )
      );

      unset($data['cornerstone_font_items']);
    }

    if ( isset( $data['cornerstone_color_items'] ) ) {

      update_option(
        'cornerstone_color_items',
        wp_slash( cs_json_encode( $this->mergeOptionModels(
          get_option('cornerstone_color_items', CS()->component('Color_Manager')->default_color_items()),
          $data['cornerstone_color_items']
        ) ) )
      );

      unset($data['cornerstone_color_items']);
    }

    foreach ($data as $key => $value) {
      $normalized = $this->normalizeContent( maybe_unserialize( $value ) );

      if ( false === update_option( $key, $normalized ) && get_option($key) !== $normalized) {

        if ( ! $this->debugMessage ) {
          $this->debugMessage = '';
        }
        $this->debugMessage .= "Could not set option ($key): {$value} | {$normalized} ";
      }
    }

    $this->message = __( 'Setting Theme Options...', 'cornerstone' );

    do_action( 'cs_theme_options_after_save' );

    return true;
  }

  function parseOptionModel( $data ) {
    $json = is_array($data) ? $data : json_decode( wp_unslash( $data ), true );
    if (!is_array($json)) {
      return [];
    }
    $result = [];
    foreach($json as $item) {
      $result[$item['_id']] = $item;
    }
    return $result;
  }

  function mergeOptionModels() {
    $args = func_get_args();
    $decoded = array_map( array( $this, 'parseOptionModel' ), $args );
    $merged = array_reduce($decoded, 'array_merge', array());
    return array_values( $merged );
  }

  public function importThemeMods( $mods ) {

    if (isset($mods['nav_menu_locations'])) {
      remove_theme_mod('nav_menu_locations');
      $normalizedLocations = array();
      foreach ( $mods['nav_menu_locations'] as $location => $id ) {
        $normalizedLocations[$location] = $this->registry->get( 'term', $id );
      }
      $mods['nav_menu_locations'] = $normalizedLocations;
    }

    foreach ($mods as $key => $value) {
      set_theme_mod( $key, $value);
    }

    $this->message = __( 'Assigning menus...', 'cornerstone' );
    return true;
  }

  public function normalizeContent( $content ) {

    if ( is_array( $content ) ) {
      return array_map( array( $this, 'normalizeContent'), $content );
    }

    if ( !is_string( $content ) ) {
      return $content;
    }

    // Restore site URLs
    $content = str_replace( '{{xde:site:url}}', trailingslashit( home_url() ) , $content );

    // Restore Global Block IDs
    $content = preg_replace_callback( '#{{xde:global_block:([\w\d]+)}}#', array( $this, 'restoreGlobalBlockID' ), $content );

    return $this->normalizeImageURLs( $content );
  }

  public function restoreGlobalBlockID( $matches ) {
    return $this->registry->get('post', $matches[1]);
  }

  public function findImageIDs( $content ) {

    if ( !is_string( $content ) ) {
      return $content;
    }

    preg_match_all('#(?<={{img:).*?(?=:img}})#', $content, $matches);

    $ids = array();

    foreach ($matches[0] as $id) {
      $ids[] = $id;
    }

    return $ids;
  }

  public function findFirstImageID( $content ) {
    $ids = $this->findImageIDs( $content );
    return $ids[0];
  }

  public function normalizeImageURLs( $content ) {

    $ids = $this->findImageIDs( $content );

    foreach ($ids as $id) {
      $imageData = $this->registry->get( 'image', $id );
      if ( !is_null( $imageData) && isset( $imageData['url'] ) ) {
        $content = str_replace( '{{img:' . $id . ':img}}', $imageData['url'] , $content );
      }
    }

    return $content;
  }

}


class Cornerstone_Controller_Design_Cloud extends Cornerstone_Plugin_Component {

  public $session_data = null;

  public function import_site( $data ) {

    if ( ! current_user_can('manage_options') || !$this->plugin->component('App_Permissions')->user_can('templates.design_cloud') ) {
      return $this->make_error(new WP_Error( 'cornerstone', 'Unauthorized' ));
    }

    if (!isset($data['site'])) {
      return $this->make_error(new WP_Error( 'cornerstone', 'Site not specified' ));
    }

    if (!isset($data['session'])) {
      return $this->make_error(new WP_Error( 'cornerstone', 'Session not specified' ));
    }

    if ( function_exists('cs_set_curl_timeout_begin') ) {
      cs_set_curl_timeout_begin( 30 );
    }

    $this->session_id = 'xdi_' . substr( md5( $data['session'] ) , -16 );

    $session = get_transient( $this->session_id );

    if ( $session === false) {

      $this->session_data = array();
      $demo_data = $this->get_site_data( $data['site'] );

      if ( is_wp_error( $demo_data ) ) {
        return $this->make_error($demo_data);
      }

      $this->session_data['sliders'] = isset($data['sliders']) ? $data['sliders'] : null;
      $this->session_data['demo'] = $demo_data['jobs'];
      $this->session_data['namespace'] = $demo_data['namespace'];
      $this->save();

    } else {
      $this->session_data = $session;
    }

    $this->processor = new Cornerstone_Site_Import_Processor;
    $this->processor->setup( $this );

    ob_start();
    $response = $this->nextResponse();
    ob_end_clean();

    if ( is_wp_error( $response ) ) {
      return $this->make_error($response);
    }

    if ( function_exists('cs_set_curl_timeout_end') ) {
      cs_set_curl_timeout_end();
    }

    if (WP_DEBUG) {
      $response['debug'] = $this->session_data;
    }

		return array(
      'success' => true,
      'data' => $response
    );

  }


  /**
   * Make a remote request to get demo data from a URL
   * @param  string $demo_url URL to demo content JSON
   * @return array           Returns demo data as an array, or WP_Error on failure
   */
  public function get_site_data( $demo_url ) {

    $request = wp_remote_get( $demo_url );

    if ( is_wp_error( $request ) ) {
      return $request;
    }

    $data = json_decode( $request['body'], true );

    if ( !is_array( $data ) ) {
      return new WP_Error( 'cornerstone', 'Failed to download demo content from remote location.' );
    }

    if ( !isset( $data['namespace'] ) ) {
      return new WP_Error( 'cornerstone', 'Demo data missing namespace' );
    }

    if ( !isset( $data['version'] ) ) {
      return new WP_Error( 'cornerstone', 'Demo data missing version number.' );
    }

    if ( !isset( $data['jobs'] ) || !is_array( $data['jobs'] ) ) {
      return new WP_Error( 'cornerstone', 'Demo data missing job list.' );
    }

    foreach ( $data['jobs'] as $job ) {
      if ( !isset( $job['task'] ) || !isset( $job['data'] ) ) {
        return new WP_Error( 'cornerstone', 'Demo data job list is not formatted correctly.' );
      }
    }

    return $data;

  }

  public function make_error($error) {
    return array(
      'success' => false,
      'data' => array(
        'error' => true,
        'message' => csi18n('app.design-cloud.site-import.failure'),
        'debug_message' => $error->get_error_message(),
        'debug' => $this->session_data
      )
    );
  }

  public function set( $key, $value ) {
    $this->session_data[$key] = $value;
  }

  public function get( $key ) {
    return (isset($this->session_data[$key])) ? $this->session_data[$key] : null;
  }
  /**
   * Save our session to a transient
   * @return none
   */
  public function save() {
    return set_transient( $this->session_id, $this->session_data , 2 * MINUTE_IN_SECONDS );
  }

  /**
   * Delete the transient associated with this session
   * @return none
   */
  public function delete() {
    delete_transient( $this->session_id );
  }


  public function nextResponse() {

    $job = $this->processor->nextJob();

    if ( is_wp_error( $job ) ) {
      return $job;
    }

    $response = array(
      'completion' => $this->processor->completion()
    );

    if( $response['completion'] === true ) {
      $this->delete();
    } else {
      $debugMessage = $this->processor->debugMessage();
      if ($debugMessage) {
        $response['debug_message'] = $debugMessage;
      }
      $response['message'] = $this->processor->message();
      $this->save();
    }

    return $response;

  }
}
