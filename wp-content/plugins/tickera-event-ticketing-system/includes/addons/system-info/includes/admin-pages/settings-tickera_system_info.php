<?php
global $tc;

if (!defined('ABSPATH')) {
    exit;
}

function tc_let_to_num($size) {
    $l = substr($size, -1);
    $ret = substr($size, 0, -1);
    switch (strtoupper($l)) {
        case 'P':
            $ret *= 1024;
        case 'T':
            $ret *= 1024;
        case 'G':
            $ret *= 1024;
        case 'M':
            $ret *= 1024;
        case 'K':
            $ret *= 1024;
    }
    return $ret;
}

$tc_general_settings = get_option('tc_general_setting', false);
?>

<div class="wrap tc_wrap" id="tc_system_info">
    <div id="poststuff" class="metabox-holder tc-settings">
        <form id="tc-system-info">
            <div class="postbox">
                <h3><span><?php _e('WordPress Environment', 'tc'); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>

                    <table class="form-table" cellspacing="0" id="status">
                        <tbody>
                            <tr>
                                <td><?php _e('Home URL', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The URL of your site\'s homepage.', 'tc')); ?></td>
                                <td><?php form_option('home'); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Site URL', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The root URL of your site.', 'tc')); ?></td>
                                <td><?php form_option('siteurl'); ?></td>
                            </tr>



                            <tr>
                                <td><?php _e('Permalink Structure', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(sprintf(__('Website permalink structure', 'tc'), $tc->title)); ?></td>
                                <td><?php echo esc_html(get_option('permalink_structure')); ?></td>
                            </tr>

                            <tr>
                                <td><?php printf(__('%s Version', 'tc'), $tc->title); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(sprintf(__('The version of %s installed on your site.', 'tc'), $tc->title)); ?></td>
                                <td><?php echo esc_html($tc->version); ?></td>
                            </tr>

                            <tr>
                                <td><?php _e('WordPress Version', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The version of WordPress installed on your site.', 'tc')); ?></td>
                                <td><?php bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('WP Multisite', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('Whether or not you have WordPress Multisite.', 'tc')); ?></td>
                                <td><?php
                                    if (is_multisite())
                                        echo '<span class="dashicons dashicons-yes"></span>';
                                    else
                                        echo '&ndash;';
                                    ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('WP Memory Limit', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The maximum amount of memory (RAM) that your site can use at one time.', 'tc')); ?></td>
                                <td><?php
                                    $memory = tc_let_to_num(WP_MEMORY_LIMIT);

                                    if (function_exists('memory_get_usage')) {
                                        $system_memory = tc_let_to_num(@ini_get('memory_limit'));
                                        $memory = max($memory, $system_memory);
                                    }

                                    if ($memory < 134217728) {
                                        echo '<mark class="error"><span class="dashicons dashicons-info"></span> ' . sprintf(__('%s - We recommend setting memory to at least 128MB. See: %s', 'tc'), size_format($memory), '<a href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">' . __('Increasing memory allocated to PHP', 'tc') . '</a>') . '</mark>';
                                    } else {
                                        echo '<mark class="yes">' . size_format($memory) . '</mark>';
                                    }
                                    ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('WP Debug Mode', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('Displays whether or not WordPress is in Debug Mode.', 'tc')); ?></td>
                                <td>
                                    <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
                                        <?php
                                        echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __('It\'s recommended to turn off WP_DEBUG (set it to false in the wp-config.php file) on production site.', 'tc') . '</mark>';
                                        ?>
                                    <?php else : ?>
                                        <mark class="no">&ndash;</mark>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('TC Debug Mode', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(sprintf(__('Displays whether or not %s is in special-case debug mode.', 'tc'), $tc->title)); ?></td>
                                <td>
                                    <?php if (defined('TC_DEBUG') && TC_DEBUG) : ?>
                                        <?php
                                        echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __('It\'s recommended to turn off TC_DEBUG on production site (delete the value from the wp-config.php)', 'tc') . '</mark>';
                                        ?>
                                    <?php else : ?>
                                        <mark class="no">&ndash;</mark>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('Caching Plugin', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(sprintf(__('Whether or not you have a caching plugin installed.', 'tc'), $tc->title)); ?></td>
                                <td>
                                    <?php if (defined('WP_CACHE') && WP_CACHE) : ?>
                                        <?php
                                        echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf(__('It seems that you have a caching plugin installed. In order to avoid potential issues, you should exclude all the pages which contains one of %s shortcodes from caching as well as all the cookies. Read more %shere%s', 'tc'), $tc->title, '<a href="https://tickera.com/tickera-documentation/configuring-caching-plugins/">', '</a>') . '</mark>';
                                        ?>
                                    <?php else : ?>
                                        <mark class="no">&ndash;</mark>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><?php _e('Cookie Hash', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('COOKIEHASH constant used for naming cookies.', 'tc')); ?></td>
                                <td><?php echo COOKIEHASH; ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Cookie Path', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('Path where cookies are accessible. If your cart is empty after adding a ticket to it, you may try to change a cookie path in the wp-config.php - just add this line of code: define( "COOKIEPATH", "/" );', 'tc')); ?></td>
                                <td><?php echo COOKIEPATH; ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Language', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The current language used by WordPress.', 'tc')); ?></td>
                                <td><?php echo get_locale(); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h3><span><?php _e('Server Environment', 'tc'); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0">
                        <tbody>
                            <tr>
                                <td><?php _e('Server Info', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('Info about the server where your website is hosted.', 'tc')); ?></td>
                                <td><?php echo esc_html($_SERVER['SERVER_SOFTWARE']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('PHP Version', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The version of PHP installed on your server.', 'tc')); ?></td>
                                <td><?php
                                    // Check if phpversion function exists.
                                    if (function_exists('phpversion')) {
                                        $php_version = phpversion();

                                        if (version_compare($php_version, '5.6', '<')) {
                                            echo '<mark class="error"><span class="dashicons dashicons-info"></span> ' . sprintf(__('%s - We recommend a minimum PHP version of 5.6', 'tc'), esc_html($php_version)) . '</mark>';
                                        } else {
                                            echo '<mark class="yes">' . esc_html($php_version) . '</mark>';
                                        }
                                    } else {
                                        _e("Couldn't determine PHP version because phpversion() doesn't exist.", 'tc');
                                    }
                                    ?></td>
                            </tr>
                            <?php if (function_exists('ini_get')) : ?>
                                <tr>
                                    <td><?php _e('PHP Post Max Size', 'tc'); ?>:</td>
                                    <td class="help"><?php echo tc_tooltip(__('The largest filesize that can be contained in one post.', 'tc')); ?></td>
                                    <td><?php echo size_format(tc_let_to_num(ini_get('post_max_size'))); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('PHP Time Limit', 'tc'); ?>:</td>
                                    <td class="help"><?php echo tc_tooltip(__('Maximum execution time of a single operation before timing out', 'tc')); ?></td>
                                    <td><?php echo ini_get('max_execution_time'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('PHP Max Input Vars', 'tc'); ?>:</td>
                                    <td class="help"><?php echo tc_tooltip(__('The maximum number of input variables your server can use for a single function to avoid overloads.', 'tc')); ?></td>
                                    <td><?php echo ini_get('max_input_vars') >= 1000 ? ini_get('max_input_vars') : '<mark class="error"><span class="dashicons dashicons-info"></span> ' . ini_get('max_input_vars') . ' - ' . __('If you expect to sell many tickets at once (per order) and use custom forms, you should increase this value to 2000 or more.', 'tc') . '</mark>'; ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('cURL Version', 'tc'); ?>:</td>
                                    <td class="help"><?php echo tc_tooltip(__('The version of cURL installed on your server.', 'tc')); ?></td>
                                    <td><?php
                                        if (function_exists('curl_version')) {
                                            $curl_version = curl_version();
                                            echo $curl_version['version'] . ', ' . $curl_version['ssl_version'];
                                        } else {
                                            _e('N/A', 'tc');
                                        }
                                        ?></td>
                                </tr>

                            <?php endif; ?>
                            <tr>
                                <td><?php _e('Max Upload Size', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The largest filesize that can be uploaded to your WP installation.', 'tc')); ?></td>
                                <td><?php echo size_format(wp_max_upload_size()); ?></td>
                            </tr>

                            <tr>
                                <?php
                                //allow_url_include 
                                $mark = @ini_get('allow_url_fopen') ? 'yes' : 'error';
                                ?>
                                <td><?php _e('allow_url_fopen', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('Ticket template might need allow_url_fopen to be allowed on your server in order to retrieve images.', 'tc')); ?></td>
                                <td>
                                    <mark class="<?php echo $mark; ?>">
                                        <?php echo ini_get('allow_url_fopen') ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-info"></span> ' . __('Ask your hosting provider to turn on allow_url_fopen option if you\'re experiencing issues with opening / downloading PDF tickets.', 'tc'); ?>
                                    </mark>
                                </td>
                            </tr>
                            
                         
                            <?php
                            
                            $mark = (!extension_loaded('imagick') && !extension_loaded('gd')) ? 'error' : 'yes';
                            ?>
                            <tr>
                                <td><?php echo esc_html(__('GD or Imagick extension')); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('GD or Imagick PHP extension is required for ticket template images', 'tc')); ?></td>
                                <td>
                                    <mark class="<?php echo $mark; ?>">
                                        <?php echo $mark == 'yes' ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>'; ?> <?php echo $mark == 'error' ? __('GD or Imagick PHP extension is required for ticket template images. We suggest you to contact your hosting provider and ask them to enable one of these extensions on the server.', 'tc') : ''; ?>
                                    </mark>
                                </td>
                            </tr>

                            <?php
                            do_action('tc_system_info_server_environment_options');
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="postbox">
                <h3><span><?php _e('Active Plugins', 'tc'); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0">
                        <tbody>
                            <?php
                            $active_plugins = (array) get_option('active_plugins', array());

                            if (is_multisite()) {
                                $network_activated_plugins = array_keys(get_site_option('active_sitewide_plugins', array()));
                                $active_plugins = array_merge($active_plugins, $network_activated_plugins);
                            }

                            foreach ($active_plugins as $plugin) {

                                $plugin_data = @get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                                $dirname = dirname($plugin);
                                $version_string = '';
                                $network_string = '';

                                if (!empty($plugin_data['Name'])) {

                                    // Link the plugin name to the plugin url if available.
                                    $plugin_name = esc_html($plugin_data['Name']);
                                    ?>
                                    <tr>
                                        <td><?php echo $plugin_name; ?></td>
                                        <td class="help">&nbsp;</td>
                                        <td><?php echo esc_html($plugin_data['Version']) . $version_string . $network_string; ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if (!is_plugin_active('bridge-for-woocommerce/bridge-for-woocommerce.php')) {
                ?>
                <div class="postbox">
                    <h3><span><?php printf(__('%s Pages', 'tc'), $tc->title); ?></span></h3>
                    <div class="inside">
                        <span class="description"></span>
                        <table class="form-table" cellspacing="0">

                            <tbody>
                                <?php
                                //if bridge is not activated
                                $check_pages = array(
                                    _x('Cart Page', 'Page setting', 'tc') => array(
                                        'option' => 'tc_cart_page_id',
                                        'shortcode' => '[tc_cart]',
                                        'help' => __('The URL of your ticketing cart page', 'tc'),
                                    ),
                                    _x('Payment Page', 'Page setting', 'tc') => array(
                                        'option' => 'tc_payment_page_id',
                                        'shortcode' => '[tc_payment]',
                                        'help' => __('The URL of your payment page', 'tc'),
                                    ),
                                    _x('Payment Confirmation Page', 'Page setting', 'tc') => array(
                                        'option' => 'tc_confirmation_page_id',
                                        'shortcode' => '[tc_order_confirmation]',
                                        'help' => __('The URL of your payment / order confirmation page', 'tc'),
                                    ),
                                    _x('Order Details Page', 'Page setting', 'tc') => array(
                                        'option' => 'tc_order_page_id',
                                        'shortcode' => '[tc_order_details]',
                                        'help' => __('The URL of your order details page', 'tc'),
                                    ),
                                    _x('Process Payment Page', 'Page setting', 'tc') => array(
                                        'option' => 'tc_process_payment_page_id',
                                        'shortcode' => '[tc_process_payment]',
                                        'help' => __('The URL of process payment page', 'tc'),
                                    ),
                                    _x('IPN Page', 'Page setting', 'tc') => array(
                                        'option' => 'tc_ipn_page_id',
                                        'shortcode' => '[tc_ipn]',
                                        'help' => __('The URL of IPN (instant payment notification) page used by some payment gateways', 'tc'),
                                    ),
                                );

                                $alt = 1;

                                foreach ($check_pages as $page_name => $values) {
                                    $error = false;
                                    $page_id = get_option($values['option']);

                                    $page_name = esc_html($page_name);

                                    echo '<tr><td>' . $page_name . ':</td>';
                                    echo '<td class="help">' . tc_tooltip($values['help'], false) . '</td><td>';

                                    // Page ID check.
                                    if (!$page_id) {
                                        echo '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . __('Page not set', 'tc') . '</mark>';
                                        $error = true;
                                    } else if (!get_post($page_id)) {
                                        echo '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . __('Page ID is saved, but the page does not exist', 'tc') . '</mark>';
                                        $error = true;
                                    } else if (get_post_status($page_id) !== 'publish') {
                                        echo '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . __('Page should have Publish status', 'tc') . '</mark>';
                                        $error = true;
                                    } else {

                                        // Shortcode check
                                        if ($values['shortcode']) {
                                            $page = get_post($page_id);

                                            if (empty($page)) {

                                                echo '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . sprintf(__('Page does not exist', 'tc')) . '</mark>';
                                                $error = true;
                                            } else if (!strstr($page->post_content, $values['shortcode'])) {

                                                echo '<mark class="error"><span class="dashicons dashicons-no-alt"></span> ' . sprintf(__('Page does not contain required shortcode: %s', 'tc'), $values['shortcode']) . '</mark>';
                                                $error = true;
                                            }
                                        }
                                    }

                                    if (!$error)
                                        echo '<mark class="yes">' . str_replace(home_url(), '', get_permalink($page_id)) . '</mark>';

                                    echo '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

            <div class="postbox">
                <h3><span><?php _e('Theme', 'tc'); ?></span></h3>
                <div class="inside">
                    <span class="description"></span>
                    <table class="form-table" cellspacing="0">
                        <?php
                        include_once( ABSPATH . 'wp-admin/includes/theme-install.php' );

                        $active_theme = wp_get_theme();
                        $theme_version = $active_theme->Version;
                        ?>
                        <tbody>
                            <tr>
                                <td><?php _e('Name', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The name of the current active theme.', 'tc')); ?></td>
                                <td><?php echo esc_html($active_theme->Name); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Version', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The installed version of the current active theme.', 'tc')); ?></td>
                                <td><?php
                                    echo esc_html($theme_version);
                                    ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Author URL', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('The theme developers URL.', 'tc')); ?></td>
                                <td><?php echo $active_theme->{'Author URI'}; ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Child Theme', 'tc'); ?>:</td>
                                <td class="help"><?php echo tc_tooltip(__('Displays whether or not the current theme is a child theme.', 'tc')); ?></td>
                                <td><?php
                                    echo is_child_theme() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<span class="dashicons dashicons-no-alt"></span>';
                                    ?></td>
                            </tr>
                            <?php
                            if (is_child_theme()) :
                                $parent_theme = wp_get_theme($active_theme->Template);
                                ?>
                                <tr>
                                    <td><?php _e('Parent Theme Name', 'tc'); ?>:</td>
                                    <td class="help"><?php echo tc_tooltip(__('The name of the parent theme.', 'tc')); ?></td>
                                    <td><?php echo esc_html($parent_theme->Name); ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Parent Theme Version', 'tc'); ?>:</td>
                                    <td class="help"><?php echo tc_tooltip(__('The installed version of the parent theme.', 'tc')); ?></td>
                                    <td><?php
                                        echo esc_html($parent_theme->Version);
                                        ?></td>
                                </tr>
                                <tr>
                                    <td><?php _e('Parent Theme Author URL', 'tc'); ?>:</td>
                                    <td class="help"><?php echo tc_tooltip(__('The parent theme developers URL.', 'tc')); ?></td>
                                    <td><?php echo $parent_theme->{'Author URI'}; ?></td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                    </form>
                </div>
            </div>

            <div class="postbox">
                <h3><span><?php _e('Full Report', 'tc'); ?></span></h3>
                <div class="inside">
                    <span class="description"><?php _e('You can copy and paste this report when contacting support.', 'tc'); ?></span>
                    <textarea id="tc_system_info_text" style="width: 100%; height: 200px;"></textarea>
                    <input type="submit" name="tc_system_info_button" id="tc_system_info_button" class="button button-primary" style="display: none;" value="Show Report">
                </div>
            </div>

            <?php do_action('tc_after_system'); ?>


    </div>
</div>

<script type="text/javascript">

    jQuery(document).ready(function ($) {

        jQuery('#tc_system_info_button').click(function () {
            var report = '';
            var section_title = '';
            var value_title = '';
            var value = '';

            jQuery('#tc-system-info .postbox').each(function () {
                section_title = jQuery(this).find('h3.hndle span').html();
                report = report + '\n### ' + section_title + ' ###\n\n';

                jQuery(this).find('.form-table tr').each(function ( ) {
                    value_title = jQuery(this).find('td:eq(0)').html();
                    value_title = value_title.replace(":", "");

                    var $value_html = jQuery(this).find('td:eq(2)').clone();
                    $value_html.find('.dashicons-yes').replaceWith('&#10004;');
                    $value_html.find('.dashicons-no-alt').replaceWith('&#10060;');//.dashicons-warning

                    var value = jQuery.trim($value_html.text());

                    report = report + '' + value_title + ': ' + value + '\n';
                });
            });

            jQuery('#tc_system_info_text').val(report);
        });
        jQuery('#tc_system_info_button').click();
    });



</script>
