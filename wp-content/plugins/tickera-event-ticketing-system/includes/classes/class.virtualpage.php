<?php

if ( !defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if ( !class_exists( 'Virtual_Page' ) ) {

	class Virtual_Page {

		var $slug	 = null;
		var $title	 = null;
		var $content	 = null;
		var $author	 = null;
		var $date	 = null;
		var $type	 = null;

		function __construct( $args ) {
			if ( !isset( $args[ 'slug' ] ) )
				throw new Exception( 'No slug given for the virtual page' );

			$this->show_title	 = isset( $args[ 'show_title' ] ) ? $args[ 'show_title' ] : true;
			$this->slug			 = $args[ 'slug' ];
			$this->title		 = isset( $args[ 'title' ] ) ? $args[ 'title' ] : '';
			$this->content		 = isset( $args[ 'content' ] ) ? $args[ 'content' ] : '';
			$this->author		 = isset( $args[ 'author' ] ) ? $args[ 'author' ] : 1;
			$this->date			 = isset( $args[ 'date' ] ) ? $args[ 'date' ] : current_time( 'mysql' );
			$this->dategmt		 = isset( $args[ 'date' ] ) ? $args[ 'date' ] : current_time( 'mysql', 1 );
			$this->type			 = isset( $args[ 'type' ] ) ? $args[ 'type' ] : 'page';

			$this->is_page			 = isset( $args[ 'is_page' ] ) ? $args[ 'is_page' ] : TRUE;
			$this->is_singular		 = isset( $args[ 'is_singular' ] ) ? $args[ 'is_singular' ] : TRUE;
			$this->is_archive		 = isset( $args[ 'is_archive' ] ) ? $args[ 'is_archive' ] : FALSE;
			$this->comment_status	 = isset( $args[ 'comment_status' ] ) ? $args[ 'comment_status' ] : 'closed';
			$this->post_type		 = 'public';

			add_filter( 'the_posts', array( &$this, 'virtualPage' ) );
			add_filter( 'the_title', array( &$this, 'hide_title' ), 10, 2 );
		}

		// filter to create virtual page content
		function virtualPage( $posts ) {
			global $wp, $wp_query, $wpdb, $comment;

			$old_post_slug_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s", $wp->request ) ); //check if slug already exists

			if ( $old_post_slug_id == '' ) {

				$post = new stdClass;

				$post->ID					 = '';
				$post->post_author			 = $this->author;
				$post->post_date			 = $this->date;
				$post->post_date_gmt		 = $this->dategmt;
				$post->post_content			 = $this->content;
				$post->post_title			 = $this->title;
				$post->post_excerpt			 = '';
				$post->post_status			 = 'publish';
				$post->comment_status		 = $this->comment_status;
				$post->ping_status			 = 'closed';
				$post->post_password		 = '';
				$post->post_name			 = $this->slug;
				$post->to_ping				 = '';
				$post->pinged				 = '';
				$post->modified				 = $post->post_date;
				$post->modified_gmt			 = $post->post_date_gmt;
				$post->post_content_filtered = '';
				$post->post_parent			 = 0;
				$post->guid					 = get_home_url( '/' . $this->slug );
				$post->menu_order			 = 0;
				$post->post_type			 = $this->type;
				$post->post_mime_type		 = '';

				// setting this to -1 lets wordpress load comment 1... it uses the absolute value.
				$post->comment_count = 0;

				//$posts = array_merge( $posts, array( $post ) );

				$posts = array( $post );

				$wp_query->is_page				 = $this->is_page;
				$wp_query->is_singular			 = $this->is_singular;
				$wp_query->is_home				 = FALSE;
				$wp_query->is_archive			 = FALSE;
				$wp_query->is_category			 = FALSE;
				unset( $wp_query->query[ 'error' ] );
				$wp_query->query_vars[ 'error' ] = '';
				$wp_query->is_404				 = FALSE;
			} else {
				//Slug already exists
			}

			return ( $posts );
		}

		function hide_title( $title, $id ) {
			if ( $this->show_title ) {
				return $title;
			} else {
				return '';
			}
		}

	}

}