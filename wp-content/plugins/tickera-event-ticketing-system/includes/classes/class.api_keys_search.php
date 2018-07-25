<?php

if ( !defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

if ( !class_exists( 'TC_API_Keys_Search' ) ) {

	class TC_API_Keys_Search {

		var $per_page	 = 10;
		var $args		 = array();
		var $post_type	 = 'tc_api_keys';
		var $page_name	 = 'api';
		var $items_title	 = 'API Keys';

		function __construct( $search_term = '', $page_num = '', $valid_for_event_id = false, $per_page = 10 ) {//$per_page = false means no post limit
			global $tc;

			$this->per_page		 = $per_page;
			//$this->page_name	 = $tc->name . '_api_keys';
			$this->search_term	 = $search_term;
			$this->raw_page		 = ( '' == $page_num ) ? false : (int) $page_num;
			$this->page_num		 = (int) ( '' == $page_num ) ? 1 : $page_num;

                        
                        if (!empty($this->search_term)) {
                            $tc_meta_query = array(
                                'relation' => 'OR',
                                array(
                                    'key' => 'api_key_name',
                                    'value' => $this->search_term,
                                    'compare' => 'LIKE'
                                ),
                                array(
                                    'key' => 'api_key',
                                    'value' => $this->search_term,
                                    'compare' => 'LIKE'
                                ),
                            );
                        } else {
                            $tc_meta_query = array();
                        }

                        $args = array(
				'posts_per_page' => $this->per_page,
				'offset'		 => ( $this->page_num - 1 ) * $this->per_page,
				'category'		 => '',
				'orderby'		 => 'post_date',
				'order'			 => 'DESC',
				'include'		 => '',
				'exclude'		 => '',
				'meta_key'		 => ($valid_for_event_id ? 'event_name' : ''),
				'meta_value'	 => ($valid_for_event_id ? $valid_for_event_id : ''),
				'post_type'		 => $this->post_type,
                                'meta_query' => $tc_meta_query,
				'post_mime_type' => '',
				'post_parent'	 => '',
				'post_status'	 => 'any'
			);

			$this->args = $args;
		}

		function TC_Events_Search( $search_term = '', $page_num = '' ) {
			$this->__construct( $search_term, $page_num );
		}

		function get_args() {
			return $this->args;
		}

		function get_results() {
			return get_posts( $this->args );
		}

		function get_count_of_all() {
                    
                        if (!empty($this->search_term)) {
                            $tc_meta_query = array(
                                'relation' => 'OR',
                                array(
                                    'key' => 'api_key_name',
                                    'value' => $this->search_term,
                                    'compare' => 'LIKE'
                                ),
                                array(
                                    'key' => 'api_key',
                                    'value' => $this->search_term,
                                    'compare' => 'LIKE'
                                ),
                            );
                        } else {
                            $tc_meta_query = array();
                        }
                    
			$args = array(
				'posts_per_page' => -1,
				'category'		 => '',
				'orderby'		 => 'post_date',
				'order'			 => 'DESC',
				'include'		 => '',
				'exclude'		 => '',
				'meta_key'		 => '',
				'meta_value'	 => '',
				'post_type'		 => $this->post_type,
				'post_mime_type' => '',
                                'meta_query' => $tc_meta_query,
				'post_parent'	 => '',
				'post_status'	 => 'any'
			);
			return count( get_posts( $args ) );
		}

		function page_links() {
			$pagination					 = new TC_Pagination();
			$pagination->Items( $this->get_count_of_all() );
			$pagination->limit( $this->per_page );
			$pagination->parameterName	 = 'page_num';
			if ( $this->search_term != '' ) {
				$pagination->target( "edit.php?post_type=tc_events&page=tc_settings&tab=" . $this->page_name . "&s=" . $this->search_term );
			} else {
				$pagination->target( "edit.php?post_type=tc_events&page=tc_settings&tab=" . $this->page_name );
			}
			$pagination->currentPage( $this->page_num );
			$pagination->nextIcon( '&#9658;' );
			$pagination->prevIcon( '&#9668;' );
			$pagination->items_title = $this->items_title;
			$pagination->show();
		}

	}

}
?>