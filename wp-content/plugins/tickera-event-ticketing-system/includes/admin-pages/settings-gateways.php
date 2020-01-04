<?php
global $tc_gateway_plugins, $tc;
$settings = get_option('tc_settings');

if (isset($_POST['gateway_settings'])) {
    if (current_user_can('manage_options') || current_user_can('save_settings_cap')) {
        if (isset($_POST['tc'])) {
            $filtered_settings = apply_filters('tc_gateway_settings_filter', tc_sanitize_array($_POST['tc']));
            $settings = array_merge($settings, $filtered_settings);

            update_option('tc_settings', $settings);
        }
        echo '<div class="updated fade"><p>' . __('Settings saved.', 'tc') . '</p></div>';
    } else {
        echo '<div class="updated fade"><p>' . __('You do not have required permissions for this action.', 'tc') . '</p></div>';
    }
}
?>

<div class="wrap tc_wrap" id="tc_delete_info">
    <div id="poststuff" class="metabox-holder tc-settings">
        <?php
        $current_tab_url = add_query_arg(array(
            'post_type' => 'tc_events',
            'page' => $_GET['page'],
            'tab' => isset($_GET['tab']) ? $_GET['tab'] : '',
                ), admin_url('edit.php'));
        ?>
        <form id="tc-gateways-form" method="post" action="<?php echo $current_tab_url; ?>">
            <input type="hidden" name="gateway_settings" value="1" />

            <div id="tc_gateways" class="postbox">
                <h3><span><?php _e('Select Payment Gateway(s)', 'tc') ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>                            
                            <td>
                                <?php
                                foreach ((array) $tc_gateway_plugins as $code => $plugin) {

                                    if ($tc->gateway_is_network_allowed($code)) {
                                        $gateway = new $plugin[0];
                                        ?>

                                        <div class="image-check-wrap">
                                            <label>
                                                <input type="checkbox" class="tc_active_gateways" name="tc[gateways][active][]" value="<?php echo esc_attr($code); ?>"<?php echo (in_array($code, $this->get_setting('gateways->active', array()))) ? ' checked="checked"' : ((isset($gateway->automatically_activated) && $gateway->automatically_activated)) ? ' checked="checked"' : ''; ?> <?php echo ((isset($gateway->automatically_activated) && $gateway->automatically_activated)) ? 'disabled' : ''; ?> /> 

                                                <div class="check-image check-image-<?php echo in_array($code, $this->get_setting('gateways->active', array())) ?>">
                                                    <img src="<?php echo esc_attr($gateway->admin_img_url); ?>" />
                                                </div>

                                            </label>
                                        </div><!-- image-check-wrap -->

                                        <?php
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                    <?php
                    if (!tc_iw_is_pr() || tets_fs()->is_free_plan()) {
                        ?>
                        <a class="tc_link" target="_blank" href="https://tickera.com/?utm_source=plugin&utm_medium=upsell&utm_campaign=gateways"><?php _e('Get premium support, more payment gateways and unlock additional features'); ?></a>
                    <?php } ?>
                </div>

            </div>

            <?php
            foreach ((array) $tc_gateway_plugins as $code => $plugin) {
                if ($tc->gateway_is_network_allowed($code)) {
                    $gateway = new $plugin[0];
                    if (isset($settings['gateways']['active'])) {
                        if (in_array($code, $settings['gateways']['active']) || (isset($gateway->automatically_activated) && $gateway->automatically_activated)) {
                            $visible = true;
                        } else {
                            $visible = false;
                        }
                    } else if (isset($gateway->automatically_activated) && $gateway->automatically_activated) {
                        $visible = true;
                    } else {
                        $visible = false;
                    }
                    $gateway->gateway_admin_settings($settings, $visible);
                }
            }
            ?>

            <p class="submit">
                <input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'tc') ?>" />
            </p>
        </form>
    </div>
</div>