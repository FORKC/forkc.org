<?php
class EM_Google_Static_Maps {
	public static function init(){
		add_filter('init', 'EM_Google_Static_Maps::output_image');
		add_filter('em_locate_template_default', 'EM_Google_Static_Maps::em_locate_template_default', 10, 2);
		if( get_option('dbem_gmap_static_cache_expiry') ) {
			//set up cron for addint to email queue
			if( !wp_next_scheduled('emp_cron_gmap_static_cleanup') ){
				$todays_time_to_run = strtotime(date('Y-m-d', current_time('timestamp')).' '.  get_option('emp_cron_gmap_static_cleanup_time','02:00'), current_time('timestamp'));
				$tomorrows_time_to_run = $todays_time_to_run + DAY_IN_SECONDS;
				$time = $todays_time_to_run > current_time('timestamp') ? $todays_time_to_run:$tomorrows_time_to_run;
				$time -= ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ); //offset time to run at UTC time for WP Cron
				wp_schedule_event( $time,'daily','emp_cron_gmap_static_cleanup');
			}
			add_action('emp_cron_gmap_static_cleanup', 'EM_Google_Static_Maps::cleanup_directory' );
		}
	}
	
	public static function em_locate_template_default( $located, $template_name ){
		if( $template_name == 'placeholders/locationmap.php' && get_option('dbem_gmap_type') == 'static' ){
			return emp_locate_template($template_name);
		}
		return $located;
	}
	
	public static function generate_map_link( $EM_Location ){
	
	}
	
	public static function output_image(){
		if ( preg_match('/events\-manager\/static\-gmaps\/gmap\-(\d+)\-(\d+)x(\d+).png?$/', $_SERVER['REQUEST_URI'], $matches) ) {
			$upload_dir = self::get_image_directory();
			//if we got here, the image doesn't exist, so we create it based on the requested location ID and size
			$location_id = $matches[1];
			$width = $matches[2];
			$height = $matches[3];
			//get url of map and output prelim headers
			$map_url = EM_Google_Static_Maps::get_map_url( $location_id, $width.'x'.$height, array('key' => get_option('dbem_gmap_server_key')) );
			header('Content-Type: image/png;');
			//if we have caching disabled, just output the image
			if( !get_option('dbem_gmap_static_cache') ){
				die( file_get_contents($map_url) );
			}
			// Create directory if necessary and generate the filename we'll be creating
			if( !file_exists($upload_dir) ) mkdir($upload_dir, ( fileperms( ABSPATH ) & 0777 | 0755 ), true);
			$filename = "gmap-{$location_id}-{$width}x{$height}.png";
			// Copy URL to new file
			if ( !copy($map_url, $upload_dir. $filename) ) {
				//handle error, show an error image if we can't just output the image itself...
				$image = file_get_contents($map_url);
				if( $image !== false ) die($image);
				die('\x38\x39\x35\x30\x34\x65\x34\x37\x30\x64\x30\x61\x31\x61\x30\x61\x30\x30\x30\x30\x30\x30\x30\x64\x34\x39\x34\x38\x34\x34\x35\x32\x30\x30\x30\x30\x30\x30\x30\x31\x30\x30\x30\x30\x30\x30\x30\x31\x30\x38\x30\x34\x30\x30\x30\x30\x30\x30\x62\x35\x31\x63\x30\x63\x30\x32\x30\x30\x30\x30\x30\x30\x30\x62\x34\x39\x34\x34\x34\x31\x35\x34\x37\x38\x64\x61\x36\x33\x62\x63\x37\x39\x30\x36\x30\x30\x30\x32\x38\x34\x30\x31\x61\x37\x33\x36\x37\x39\x65\x36\x63\x35\x30\x30\x30\x30\x30\x30\x30\x30\x34\x39\x34\x35\x34\x65\x34\x34\x61\x65\x34\x32\x36\x30\x38\x32');
			}else{
				die( file_get_contents($upload_dir. $filename) );
			}
		}
	}
	
	public static function get_map_args( $location_or_id, $args = array() ){
		$EM_Location = em_get_location( $location_or_id );
		$latlng = $EM_Location->location_latitude.','.$EM_Location->location_longitude;
		return apply_filters('em_location_placeholder_gmap_static_args', array_merge( array(
			'center' => $latlng,
			'maptype' => 'roadmap',
			'size' => 'SIZE',
			'scale' => 2,
			'zoom' => 14,
			'markers' => urlencode('color:red|size:mid|'. $EM_Location->location_latitude.','.$EM_Location->location_longitude),
			'key' => get_option('dbem_google_maps_browser_key')
		), $args), $EM_Location, $args);
	}
	
	public static function get_map_url( $location_or_id, $size, $args = array() ){
		//generate the map url
		$args['size'] = $size;
		$args = self::get_map_args($location_or_id, $args);
		$map_url = add_query_arg($args, "https://maps.googleapis.com/maps/api/staticmap");
		//add styles which we convert from snazzy maps style
		if( get_option('dbem_google_maps_styles') ){
			$map_style = json_decode(get_option('dbem_google_maps_styles'), true);
			foreach( $map_style as $style ){
				if( !empty($style['stylers']) ){
					$queryarg = array();
					if( !empty($style['featureType']) ) $queryarg[] = 'feature:'.$style['featureType'];
					if( !empty($style['elementType']) ) $queryarg[] = 'element:'.$style['elementType'];
					if( !empty($queryarg) ){
						foreach( $style['stylers'] as $styler ){
							foreach( $styler as $styler_k => $styler_v ){
								$queryarg[] = $styler_k .':'. str_replace('#', '0x', $styler_v);
							}
						}
						$map_url .= '&style='.urlencode(implode('|', $queryarg));
					}
				}
			}
		}
		//now we generate the signature for this map url
		$secret = get_option('dbem_gmap_static_secret');
		$map_url_parsed = parse_url($map_url);
		$url_to_sign = $map_url_parsed['path'].'?'.$map_url_parsed['query'];
		// Decode the private key into its binary format
		$secret_decoded = base64_decode(str_replace(array('-', '_'), array('+', '/'), $secret));
        // Create a signature using the private key and the URL-encoded string using HMAC SHA1. This signature will be binary.
		$signature = hash_hmac("sha1", $url_to_sign, $secret_decoded,  true);
		$signature_encoded = str_replace(array('+', '/'), array('-', '_'), base64_encode($signature));
		//add signature to map url
		$map_url .= '&signature='.$signature_encoded;
		return $map_url;
	}
	
	public static function get_map_link( $location_or_id, $args = array() ){
		$args = self::get_map_args( $location_or_id, $args );
		return "https://www.google.com/maps/@?api=1&map_action=map&basemap=roadmap&center={$args['center']}&zoom={$args['zoom']}";
	}
	
	public static function get_image_directory(){
		if( EM_MS_GLOBAL ){
			//If in ms recurrence mode, we are getting the default wp-content/uploads folder
			$upload_dir = WP_CONTENT_DIR.'/uploads/events-manager/static-gmaps/';
		}else{
			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['basedir'].'/events-manager/static-gmaps/';
		}
		return $upload_dir;
	}
	
	public static function get_image_directory_url(){
		if( EM_MS_GLOBAL ){
			//If in ms recurrence mode, we are getting the default wp-content/uploads folder
			$upload_dir = WP_CONTENT_URL.'/uploads/events-manager/static-gmaps/';
		}else{
			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['baseurl'].'/events-manager/static-gmaps/';
		}
		return $upload_dir;
	}
	
	public static function cleanup_directory(){
		//calculate what time is the cut-off time to delete files
		$expire = time() - (DAY_IN_SECONDS * get_option('dbem_gmap_static_cache_expiry'));
		//go through each image and verify if it should be deleted
		$d = self::get_image_directory();
		if( $handle = opendir($d) ){
		    while( false !== ($f = readdir($handle)) ){
		        if( $f != "." && $f != ".." ){
		        	$f_modified = filemtime("$d/$f");
		            if( $f_modified < $expire ){
		            	unlink("$d/$f");
		            }
		        }
		    }
		    closedir($handle);
		}
	}
}
EM_Google_Static_Maps::init();


// Encode a string to URL-safe base64
function encodeBase64UrlSafe($value)
{
	return str_replace(array('+', '/'), array('-', '_'),
		base64_encode($value));
}

// Decode a string from URL-safe base64
function decodeBase64UrlSafe($value)
{
	return base64_decode(str_replace(array('-', '_'), array('+', '/'),
		$value));
}

// Sign a URL with a given crypto key
// Note that this URL must be properly URL-encoded
function signUrl($myUrlToSign, $privateKey)
{
	// parse the url
	$url = parse_url($myUrlToSign);
	
	$urlPartToSign = $url['path'] . "?" . $url['query'];
	
	// Decode the private key into its binary format
	$decodedKey = decodeBase64UrlSafe($privateKey);
	
	// Create a signature using the private key and the URL-encoded
	// string using HMAC SHA1. This signature will be binary.
	$signature = hash_hmac("sha1",$urlPartToSign, $decodedKey,  true);
	
	$encodedSignature = encodeBase64UrlSafe($signature);
	
	return $myUrlToSign."&signature=".$encodedSignature;
}