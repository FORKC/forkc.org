<?php

tets_fs()->add_filter( 'show_deactivation_feedback_form', '__return_false' );
function tc_get_license_key()
{
    @($fr_license_key = tets_fs()->_get_license()->secret_key);
    
    if ( !empty($fr_license_key) ) {
        return $fr_license_key;
    } else {
        $tc_general_settings = get_option( 'tc_general_setting', false );
        $license_key = ( defined( 'TC_LCK' ) && TC_LCK !== '' ? TC_LCK : (( isset( $tc_general_settings['license_key'] ) && $tc_general_settings['license_key'] !== '' ? $tc_general_settings['license_key'] : '' )) );
        return $license_key;
    }

}

tets_fs()->add_action( 'addons/after_title', 'tc_add_fs_templates_addons_poststuff_before_bundle_message_and_link' );
function tc_add_fs_templates_addons_poststuff_before_bundle_message_and_link()
{
    
    if ( tc_iw_is_wl() == false ) {
        ?>
    <div class="updated"><p><?php 
        printf( __( 'NOTE: All add-ons are included for FREE with the <a href="%s" target="_blank">Bundle Package</a>', 'tc' ), 'https://tickera.com/pricing/?utm_source=plugin&utm_medium=upsell&utm_campaign=addons' );
        ?></p></div>
      <?php 
    }

}

function tc_members_account_url( $url )
{
    return 'https://tickera.com/members';
}

tets_fs()->add_filter( 'pricing_url', 'tc_members_account_url' );
function tc_is_pr_only()
{
    return false;
}
