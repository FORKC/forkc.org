<?php

class Cornerstone_Controller_Preview_Switcher extends Cornerstone_Plugin_Component {

    /**
     * Retrieve posts and terms data
     * 
     * @return {Array} $data 
     */
    public function get_data($data) {
        $terms = $this->get_terms($data);
        $posts = $this->get_posts($data);
        $data  = array_merge_recursive($posts, $terms);

        return $data;
    }

    /**
     * Fetch all items and group them by post types.
     * 
     * @param {Array} $data Array that stores multiple associative arrays.
     * @return {Array} $posts 
     */
    public function get_posts($data) {
        if ( ! $this->plugin->component('App_Permissions')->user_can('headers') ) {
            return;
        }

        $result = array();

        $post_types = get_post_types( array(
            'public' => true,
            'show_ui' => true,
            'exclude_from_search' => false
        ) , 'names' );

        unset( $post_types['attachment'] );
            
        foreach ( $post_types as $type ) {
            $posts[$type] = get_posts( array(
                'post_type' => $type,
                'post_status' => 'any',
                's' => $data['search'] ? $data['search'] : null,
                'posts_per_page' => apply_filters( 'cs_query_preview_switcher_posts_limit', $data['limit'] )
            ) );
        }

        $result = $this->get_transformed_data($posts, 'post');
       
        return $result;
    }
   
    /**
     * Get all terms and group them by taxonomies
     * 
     * @param {Array} $posts An array of posts from certain post type.
     * @return {Array} $result 
     */
    public function get_terms($data) {
        $result = array();

        $args = array(
            'public' => true,
        ); 

        $taxonomies = get_taxonomies( $args, 'objects', 'and' );

        if ( $taxonomies ) {
            foreach( $taxonomies as $taxonomy ) {
                $result[$taxonomy->name] = get_terms( array(
                    'taxonomy' => $taxonomy->name,
                    'hide_empty' => true,
                    'number' => $data['limit']
                ) );
            }
        }

        $result = $this->get_transformed_data($result, 'term');

        return $result;
    }

    /**
     * Loop through post types and return transformed data
     * 
     * @param {Array} $posts An array of posts ordered by post type.
     * @return {Array} $result 
     */
    private function get_transformed_data($data, $type = 'post') {
        $result = array();

        foreach( $data as $key => $item ) {
            $result[$key] = $this->transform_data($data[$key], $type);          
        }

        return $result;
    }

    /**
     * Leave necessary post and taxonomy term data
     * 
     * @param {Array} $data An array of posts from certain post types or taxonomy terms.
     * @return {Array} $result 
     */
    private function transform_data($items, $type = 'post') {
        $result = array();

        foreach ( $items as $key => $item ) {
            $id     = $type == 'post' ? $item->ID : $item->term_id;
            $link   = $type == 'post' ? get_the_permalink($id) : get_term_link($id);
            $title  = $type == 'post' ? $item->post_title : $item->name;
            $status = $type == 'post' ? $item->post_status : null;
            $group  = $type == 'post' ? $item->post_type : $item->taxonomy;

            $result[$key]['id'] = $id;
            $result[$key]['title'] = $title;
            $result[$key]['status'] = $status;
            $result[$key]['type'] = $group;
            $result[$key]['url'] = $link; 
            $result[$key]['slug'] = $type == 'post' ? null : $item->slug;
        }

        return $result;
    }
}
