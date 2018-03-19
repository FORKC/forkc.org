<?php
class EM_Gateway_ML {
    
    public static function init(){
        add_action('em_updated_gateway_options', 'EM_Gateway_ML::em_updated_gateway_options', 10, 2);        
    }
    
    public static function em_updated_gateway_options($options, $EM_Gateway){
    	//multilingual, same as above, should be triggered by action above
    	foreach( $options as $option_name ){
    	    if( !empty($_REQUEST[$option_name.'_ml']) && is_array($_REQUEST[$option_name.'_ml']) ){
    		    $option_ml_value = array();
    		    foreach( $_REQUEST[$option_name.'_ml'] as $lang => $option_value_raw ){
    		        if( !empty($option_value_raw) ){
    		            $option_ml_value[$lang] = apply_filters('gateway_update_'.$option_name, stripslashes($option_value_raw));
    		        }
    		    }
    		    update_option($option_name.'_ml', $option_ml_value);			    
    		}
    	}
    }
}
EM_Gateway_ML::init();