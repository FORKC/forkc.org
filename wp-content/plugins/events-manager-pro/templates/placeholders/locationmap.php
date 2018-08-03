<?php
/*
 * This files overrides the #_LOCATIONMAP placeholder template in events-manager/templates/placeholders/locationmap.php if google static maps are enabled
 */
/* @var $EM_Location EM_Location */
if ( get_option('dbem_gmap_is_active') && ( is_object($EM_Location) && $EM_Location->location_latitude != 0 && $EM_Location->location_longitude != 0 ) ) {
	//assign random number for element id reference
	$rand = substr(md5(rand().rand()),0,5);
	//get dimensions with px or % added in
	$width = (!empty($args['width'])) ? $args['width']:get_option('dbem_map_default_width','400px');
	$height = (!empty($args['height'])) ? $args['height']:get_option('dbem_map_default_height','300px');
	$width = preg_match('/(px)|%/', $width) ? $width:$width.'px';
	$height = preg_match('/(px)|%/', $height) ? $height:$height.'px';
	//figure out the width and height based on default maps width, especially if we're dealing with a % height
	$relative_dims = false;
	$dimensions = array('width'=>'', 'height'=>'');
	foreach( $dimensions as $dim => $v ){
		$dimensions[$dim] = str_replace('px', '', ${$dim});
		if( preg_match('/%/', ${$dim}) ){
			$relative_dims = true;
		}
	}
	//generate the map url
	$args = array();
	if( get_option('dbem_gmap_static_cache') | $relative_dims ){
		$image_directory = EM_Google_Static_Maps::get_image_directory_url();
		if( $relative_dims ){ //since we need to sign the url, we need to go via the server once we know the size we're after
			$filename = "gmap-{$EM_Location->location_id}-SIZE.png";
		}else{
			$filename = "gmap-{$EM_Location->location_id}-{$dimensions['width']}x{$dimensions['height']}.png";
		}
		$map_url = $image_directory.$filename;
	}else{
		$map_url = EM_Google_Static_Maps::get_map_url( $EM_Location, $dimensions['width'].'x'.$dimensions['height'] );
	}
	$map_link = $map_class = $embed_data = '';
	switch( get_option('dbem_gmap_static_link') ){
		case 'link':
		case 'link-place':
		case 'link-address':
			$args = EM_Google_Static_Maps::get_map_args( $EM_Location, $args );
			$map_link = "https://www.google.com/maps/search/?api=1&map_action=map&basemap=roadmap&zoom={$args['zoom']}&query=";
			if( get_option('dbem_gmap_static_link') == 'link' ) $map_link .= $args['center'];
			elseif( get_option('dbem_gmap_static_link') == 'link-place' ) $map_link .= urlencode($EM_Location->location_name.','.$EM_Location->get_full_address());
			elseif( get_option('dbem_gmap_static_link') == 'link-address' ) $map_link .= urlencode($EM_Location->get_full_address());
			break;
		case 'embed':
		case 'map':
			$map_class = ' em-map-static-load';
			if( get_option('dbem_gmap_static_link') == 'embed' ) $embed_data = 'data-gmap-embed="'.esc_url($EM_Location->get_google_maps_embed_url()).'"';
			break;
	}
	//output map if not relative, otherwise we pass on the right JS
	$alt = sprintf(esc_attr__('Map of %s', 'events-manager'), $EM_Location->location_name);
	?>
	<div class="em-location-map-container<?php echo $map_class ?>"  style='position:relative; background: #CDCDCD; width: <?php echo $width ?>; height: <?php echo $height ?>;' <?php echo $embed_data; ?>>
		<?php if( !empty($map_link) ): ?><a href="<?php echo esc_url($map_link); ?>" target="_blank"><?php endif; ?>
		<?php if( $relative_dims ): ?>
			<div class='em-location-map-static em-map-static-relative' id='em-location-map-<?php echo $rand ?>' style="width: 100%; height: 100%;" data-gmap-url="<?php echo esc_url($map_url); ?>" data-gmap-title="<?php echo esc_attr($alt); ?>">
				<?php _e('Loading Map....', 'events-manager'); ?>
			</div>
		<?php else: ?>
			<div class='em-location-map-static' id='em-location-map-<?php echo $rand ?>' style="width: 100%; height: 100%;">
				<img src="<?php echo $map_url; ?>" alt="<?php echo $alt; ?>" class="em-location-static-map">
			</div>
		<?php endif; ?>
		<?php if( get_option('dbem_gmap_static_link') != 'none' ): ?>
			<div class="em-map-overlay">
				<div><?php echo esc_html(get_option('dbem_gmap_static_link_hover')); ?></div>
			</div>
		<?php endif; ?>
		<?php if( !empty($map_link) ): ?></a><?php endif; ?>
	</div>
	<?php if( get_option('dbem_gmap_static_link') == 'map'): ?>
		<div class='em-location-map-info' id='em-location-map-info-<?php echo $rand ?>' style="display:none; visibility:hidden;">
			<div class="em-map-balloon" style="font-size:12px;">
				<div class="em-map-balloon-content" ><?php echo $EM_Location->output(get_option('dbem_location_baloon_format')); ?></div>
			</div>
		</div>
		<div class='em-location-map-coords' id='em-location-map-coords-<?php echo $rand ?>' style="display:none; visibility:hidden;">
			<span class="lat"><?php echo $EM_Location->location_latitude; ?></span>
			<span class="lng"><?php echo $EM_Location->location_longitude; ?></span>
		</div>
	<?php endif; ?>
	<?php
}elseif( is_object($EM_Location) && $EM_Location->location_latitude == 0 && $EM_Location->location_longitude == 0 ){
	echo '<i>'. __('Map Unavailable', 'events-manager') .'</i>';
}