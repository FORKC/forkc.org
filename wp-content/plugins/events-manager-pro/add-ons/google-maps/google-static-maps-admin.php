<?php
class EM_Google_Static_Maps_Admin {
	public static function init(){
		add_filter('em_settings_google_maps_options', 'EM_Google_Static_Maps_Admin::em_settings_google_maps_options');
		add_action('em_settings_google_maps_general', 'EM_Google_Static_Maps_Admin::em_settings_google_maps_general');
		add_action('em_settings_google_maps_keys', 'EM_Google_Static_Maps_Admin::em_settings_google_maps_keys');
	}
	
	public static function em_settings_google_maps_options( $options ){
		$options['static'] = __('Static', 'events-manager-pro');
		return $options;
	}
	
	public static function em_settings_google_maps_general(){
		/*
 * dbem_gmap_static_cache
 * dbem_gmap_static_cache_expiry
 * dbem_gmap_static
 * dbem_gmap_server_key
 * dbem_gmap_static_secret
 * dbem_gmap_embed
 * location_google_placeid
		 * dbem_gmap_static_link
*/
		?>
		<tbody class="emp-static-map-options">
			<tr class="em-header">
				<td colspan="2"><h4><?php echo esc_html_e('Static Map Options'); ?></h4></td>
			</tr>
			<?php
			$static_link_options = array(
				'none' => __('No Action', 'events-manager-pro'),
				'link' => __('Link to maps.google.com and center on coordinates', 'events-manager-pro'),
				'link-place' => __('Link to maps.google.com based on location name and address', 'events-manager-pro'),
				'link-address' => __('Link to maps.google.com based on only address', 'events-manager-pro'),
				'map' => __('Load dynamic map', 'events-manager-pro'),
				'embed' => __('Load embedded map', 'events-manager-pro'),
			);
			//dbem_gmap_static_link
			em_options_select( __('Map click action'), 'dbem_gmap_static_link', $static_link_options, esc_html__('Choose what happens when a visitor clicks on a static map.', 'events-manager-pro'));
			em_options_input_text( __('Static hover text'), 'dbem_gmap_static_link_hover', esc_html__('If your static maps have a click action, hovering over a static map will show this text to the visitor.', 'events-manager-pro'), __('Click for interactive map','events-manager-pro'));
			$docs_link = '<a href="https://wp-events-plugin.com/documentation/google-maps/static-maps/?utm_source=plugin&utm_medium=pro-settings&utm_campaign=gmaps-static-keys">'.__('documentation', 'events-manager').'</a>';
			em_options_radio_binary(__('Enable static map caching?', 'events-manager-pro'), 'dbem_gmap_static_cache', sprintf(esc_html__('Store a temporary copy of each map image on your own server. We strongly recommend you read our %s page about this so you ensure you are withing Google Terms of Service', 'events-manager-pro'),$docs_link), '', '#dbem_gmap_static_cache_expiry_row');
			em_options_input_text(__('Static map cache expiry days', 'events-manager-pro'), 'dbem_gmap_static_cache_expiry', esc_html__('Enter the amount of days a cached map image should remain on your server, we recommend a maximum of 30 days so it adheres to Google\'s Terms of Service', 'events-manager-pro'), 28);
			?>
			<tr>
				<td colspan="2">
					<?php echo sprintf(__('Static cached maps require a server API and secret key, please see our %s page for instructions on obtaining one.', 'events-manager-pro'), '<a href="https://wp-events-plugin.com/documentation/google-maps/static-maps/?utm_source=plugin&utm_medium=pro-settings&utm_campaign=gmaps-static-keys#api-keys">'.esc_html__('documentation','events-manager').'</a>'); ?>
				</td>
			</tr>
			<?php
			em_options_input_text(__('Google Maps API Server Key','events-manager'), 'dbem_gmap_server_key');
			em_options_input_text(__('Google Maps API Secret Key','events-manager'), 'dbem_gmap_static_secret');
			?>
		</tbody>
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('select[name="dbem_gmap_type"]').change(function(){
					if( $(this).val() == 'static' ){
						$('tbody.emp-static-map-options').show();
					}else{
						$('tbody.emp-static-map-options').hide();
					}
				}).trigger('change');
			});
		</script>
		<?php
	}
	
	public static function em_settings_google_maps_keys(){
	}
}
EM_Google_Static_Maps_Admin::init();