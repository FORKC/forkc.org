<?php

class Tribe__Tickets_Plus__Meta__Fieldset {
	const POSTTYPE = 'ticket-meta-fieldset';
	const META_KEY = '_tribe_tickets_meta_template';

	/**
	 * Label for the Meta Fieldsets
	 *
	 * @var string
	 */
	public $plural_label;

	public function __construct() {
		$this->plural_label = __( 'Ticket Fieldsets', 'event-tickets-plus' );

		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 11 );
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 3 );
		$this->register_posttype();

		add_filter( 'wp_insert_post_data', array( $this, 'maybe_add_default_title' ), 10, 2 );
	}

	public function add_menu_item() {
		add_submenu_page(
			Tribe__Settings::instance()->get_parent_slug(),
			$this->plural_label,
			$this->plural_label,
			'edit_posts',
			'edit.php?post_type=' . self::POSTTYPE
		);
	}

	public function save_meta( $post_id, $post, $update ) {
		// Autosave? bail
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if the post type isn't a fieldset, bail
		if ( self::POSTTYPE !== $post->post_type ) {
			return;
		}

		// if this is a post revision, bail
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['tribe-tickets-input'] ) ) {
			return;
		}

		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();
		$meta = $meta_object->build_field_array( null, $_POST );

		update_post_meta( $post_id, self::META_KEY, $meta );
	}

	public function register_posttype() {
		$args = array(
			'label' => $this->plural_label,
			'labels' => array(
				'name' => $this->plural_label,
				'singular_name' => __( 'Ticket Fieldset', 'event-tickets-plus' ),
				'add_new_item' => __( 'Add New Ticket Fieldset', 'event-tickets-plus' ),
				'edit_item' => __( 'Edit Ticket Fieldset', 'event-tickets-plus' ),
				'new_item' => __( 'New Ticket Fieldset', 'event-tickets-plus' ),
				'view_item' => __( 'View Ticket Fieldset', 'event-tickets-plus' ),
				'search_items' => __( 'Search Ticket Fieldsets', 'event-tickets-plus' ),
				'not_found' => __( 'No ticket fieldsets found', 'event-tickets-plus' ),
				'not_found_in_trash' => __( 'No ticket fieldsets found in Trash', 'event-tickets-plus' ),
				'all_items' => __( 'All Ticket Fieldsets', 'event-tickets-plus' ),
				'archives' => __( 'Ticket Fieldset Archives', 'event-tickets-plus' ),
				'insert_into_item' => __( 'Insert into ticket fieldset', 'event-tickets-plus' ),
				'uploaded_to_this_item' => __( 'Uploaded to this ticket fieldset', 'event-tickets-plus' ),
			),
			'description' => 'Saved fieldsets for ticket custom meta',
			'exclude_from_search' => true,
			'menu_icon' => 'dashicons-tickets-alt',
			'supports' => array(
				'title',
			),
			'show_ui' => true,
			'show_in_menu' => false,
			'register_meta_box_cb' => array( $this, 'register_metabox' ),
		);

		register_post_type( self::POSTTYPE, $args );
	}

	public function register_metabox( $fieldset ) {
		add_meta_box(
			self::POSTTYPE . '-metabox',
			__( 'Custom Ticket Fields', 'event-tickets-plus' ),
			array( $this, 'metabox' ),
			null
		);
	}

	public function metabox( $fieldset ) {
		$templates = array();
		$meta = get_post_meta( $fieldset->ID, self::META_KEY, true );
		$ticket_id = null;
		$fieldset_form = true;

		$meta_object = Tribe__Tickets_Plus__Main::instance()->meta();

		$active_meta = array();

		if ( $meta ) {
			foreach ( $meta as $field ) {
				$active_meta[] = $meta_object->generate_field( null, $field['type'], $field );
			}
		}

		?>
		<div id="tribetickets" class="event-tickets-plus-fieldset-table tribe-tickets-plus-fieldset-page">
			<?php include Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/meta.php'; ?>
		</div>
		<?php
	}

	/**
	 * Fetch fieldsets
	 *
	 * @return array
	 */
	public function get_fieldsets() {
		$templates = get_posts( array(
			'post_type' => self::POSTTYPE,
			'orderby' => 'title',
			'order' => 'ASC',
			'posts_per_page' => -1,
		) );

		return $templates;
	}

	/**
	 * Add a default title to the Ticket Fieldset if none set
	 *
	 * @since 4.7.3
	 *
	 * @param $data array an array of post data
	 * @param $postarr array An array of elements that make up a post to update or insert
	 *
	 * @return array
	 */
	public function maybe_add_default_title( $data, $postarr ) {

		if (
			self::POSTTYPE === $data['post_type'] &&
			empty( $data['post_title'] )
		) {
			$id                 = empty( $postarr['ID'] ) ? $data['post_date'] : $postarr['ID'];
			$data['post_title'] = __( 'Ticket Fieldset', 'event-tickets-plus' ) . ' - ' . $id;
		}

		return $data;
	}

}
