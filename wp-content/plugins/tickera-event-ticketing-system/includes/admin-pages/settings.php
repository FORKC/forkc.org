<?php
global $action, $page, $tc;
wp_reset_vars(array('action', 'page'));

$page = $_GET['page'];

$tab = (isset($_GET['tab'])) ? $_GET['tab'] : '';
if (empty($tab)) {
    $tab = 'general';
}
?>

<div class="wrap tc_outside_wrap nosubsub">
    <div class="icon32 icon32-posts-page" id="icon-options-general"><br></div>
    <h2>
        <?php _e('Settings', 'tc'); ?>
        <?php if($tab == 'general'){ ?>
        <div class="tc_options_search">
            <input type="text" id="tc_options_search_val" placeholder="Search for options" />
        </div>
        <?php } ?>
    </h2>

    <?php
    if (isset($_POST['submit'])) {
        ?>
        <div id="message" class="updated fade"><p><?php _e('Settings saved successfully.', 'tc'); ?></p></div>
        <?php
    }
    if (version_compare(phpversion(), '5.3', '<')) {
        ?>
        <div id="tc_php_53_version_error" class="error" style=""><p><?php echo sprintf(__('Your current version of PHP is %s and recommended version is at least 5.3. You should contact your hosting company and %sask for upgrade%s.', 'tc'), phpversion(), '<a href="https://wordpress.org/about/requirements/">', '</a>') ?></p></div>
        <?php
    }
    ?>

    <?php
    $menus = array();
    $menus['general'] = __('General', 'tc');
    $menus['gateways'] = __('Payment Gateways', 'tc');
    $menus['email'] = __('E-mail', 'tc');
    $menus['api'] = __('API Access', 'tc');

    $menus = apply_filters('tc_settings_new_menus', $menus);
    ?>

    <div class="nav-tab-wrapper">
        <ul><?php
            foreach ($menus as $key => $menu) {
                $tab_url = add_query_arg(array(
                    'post_type' => 'tc_events',
                    'page' => $page,
                    'tab' => $key,
                        ), admin_url('edit.php'));
                ?>
                <li>
                    <a class="nav-tab<?php if ($tab == $key) echo ' nav-tab-active'; ?>" href="<?php echo $tab_url; ?>"><?php echo $menu; ?></a>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>

    <?php
    switch ($tab) {

        case 'general':
            $tc->show_page_tab('general');
            break;

        case 'gateways':
            $tc->show_page_tab('gateways');
            break;

        case 'email':
            $tc->show_page_tab('email');
            break;

        case 'api':
            $tc->show_page_tab('api');
            break;

        case 'permissions':
            $tc->show_page_tab('permissions');
            break;

        case 'social':
            $tc->show_page_tab('social');
            break;

        default: do_action('tc_settings_menu_' . $tab);
            break;
    }
    ?>

</div>