<?php
/*
 * dbem_gmap_static_cache
 * dbem_gmap_static_cache_expiry
 * dbem_gmap_static
 * dbem_gmap_server_key
 * dbem_gmap_static_secret
 */
if( get_option('dbem_gmap_type') == 'static' ){
	include('google-static-maps.php');
}
if( is_admin() && !defined('DOING_AJAX') ){
	include('google-static-maps-admin.php');
}

function em_google_static_maps_ml( $options ){
	$options[] = 'dbem_gmap_static_link_hover';
	return $options;
}
add_filter('em_ml_translatable_options', 'em_google_static_maps_ml');