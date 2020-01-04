<?php
global $tc_template_elements, $tc_gateway_plugins,$wpdb;

$templates = new TC_Ticket_Templates();
$template_elements = new TC_Ticket_Template_Elements();
$template_elements_set = array();
$page = $_GET['page'];


if (isset($_POST['add_new_template'])) {
    if (check_admin_referer('save_template')) {
        if (current_user_can('manage_options') || current_user_can('save_template_cap')) {
            $templates->add_new_template();
            $message = __('Template data has been successfully saved.', 'tc');
        } else {
            $message = __('You do not have required permissions for this action.', 'tc');
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'edit') {// && check_admin_referer('save_template')
    $post_id = (int) $_GET['ID'];
    $template = new TC_Template($post_id);
    $template_elements = new TC_Ticket_Template_Elements($post_id);
    $template_elements_set = $template_elements->get_all_set_elements();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    if (!isset($_POST['_wpnonce'])) {
        check_admin_referer('delete_' . (int) $_GET['ID']);
        if (current_user_can('manage_options') || current_user_can('delete_template_cap')) {
            $template = new TC_Template((int) $_GET['ID']);
            $template->delete_template();
            $message = __('Template has been successfully deleted.', 'tc');
        } else {
            $message = __('You do not have required permissions for this action.', 'tc');
        }
    }
}

/*
**Click to duplicate ticket template start
*/
if ((isset($_GET['action'])) && $_GET['action'] == 'tc_duplicate') {// && check_admin_referer('save_template')
    /*
    ** Get the original post ID
    */
    $post_id = (int) $_GET['ID'];
    /*
    ** fetch all post data by ID
    */
    $post = get_post($post_id);
    /*
    ** if you don't want current user to be the new post author,
    ** then change next couple of lines to this: $new_post_author = $post->post_author;
    */
    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;
    /*
    ** if post data exists, create the post duplicate
    */
    if (isset($post) && $post != null) {

        /*
        ** new post data array
        */
        $new_post_author = wp_get_current_user();
        $new_post_date = current_time('mysql');
        $new_post_date_gmt = get_gmt_from_date($new_post_date);
        $duplicate_title_extension = ' [duplicate]';

        $args = apply_filters('tc_duplicate_template_args', array(
            'post_author' => $new_post_author->ID,
            'post_date' => $new_post_date,
            'post_date_gmt' => $new_post_date_gmt,
            'post_content' => $post->post_content,
            'post_content_filtered' => $post->post_content_filtered,
            'post_title' => $post->post_title . $duplicate_title_extension,
            'post_excerpt' => $post->post_excerpt,
            'post_status' => 'draft',
            'post_type' => $post->post_type,
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_password' => $post->post_password,
            'to_ping' => $post->to_ping,
            'pinged' => $post->pinged,
            'post_modified' => $new_post_date,
            'post_modified_gmt' => $new_post_date_gmt,
            'menu_order' => $post->menu_order,
            'post_mime_type' => $post->post_mime_type,
                ), $post_id);

        /*
        ** insert the post by wp_insert_post() function
        */
        $new_post_id = wp_insert_post($args);
        /*
        ** get all current post terms ad set them to the new post draft
        */
        $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");

        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        /*
        ** duplicate all post meta just in two SQL queries
        */        
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
        if (count($post_meta_infos) != 0) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }
            $sql_query .= implode(" UNION ALL ", $sql_query_sel);
            $wpdb->query($sql_query);
        }

        /*
        ** finally, redirect to the edit post screen for the new draft
        */
        $new_post_url = add_query_arg(array(
            'post_type' => $_GET['post_type'],
            'page'      =>  $page,
            'action'    => 'edit',
            'ID'        => $new_post_id
                ), admin_url('edit.php'));
        /*
        ** Redirect ticket template to new url
        */
        wp_redirect($new_post_url);
        ?>
        <script type="text/javascript">
            window.location = "<?php echo $new_post_url; ?>";
        </script>
        <?php

    }else {
        wp_die('Post creation failed, could not find original post: ' . $post_id);
    }
}
//click to duplicate ticket template end

if (isset($_GET['page_num'])) {
    $page_num = (int) $_GET['page_num'];
} else {
    $page_num = 1;
}

if (isset($_GET['s'])) {
    $templatessearch = sanitize_text_field($_GET['s']);
} else {
    $templatessearch = '';
}

$wp_templates_search = new TC_Templates_Search($templatessearch, $page_num);
$fields = $templates->get_template_col_fields();
$columns = $templates->get_columns();

$templates_url = add_query_arg(array(
    'post_type' => 'tc_events',
    'page' => $_GET['page'],
        ), admin_url('edit.php'));

$templates_add_new_url = add_query_arg(array(
    'post_type' => 'tc_events',
    'page' => $_GET['page'],
    'action' => 'add_new'
        ), admin_url('edit.php'));

?>

<div class="wrap tc_wrap">
    <h2><?php _e('Ticket Templates', 'tc'); ?><?php if (isset($_GET['action']) && ($_GET['action'] == 'edit' || $_GET['action'] == 'add_new')) { ?><a href="<?php echo $templates_url; ?>" class="add-new-h2"><?php _e('Back', 'tc'); ?></a><?php
        } else {
            if (tc_iw_is_pr() && !tets_fs()->is_free_plan()) {
                ?><a href="<?php echo $templates_add_new_url; ?>" class="add-new-h2"><?php _e('Add New', 'tc'); ?></a><?php
            }
        }
        ?></h2>

    <?php
    if (isset($message)) {
        ?>
        <div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
        <?php
    }
    ?>

    <?php if (!isset($_GET['action']) || (isset($_GET['action']) && $_GET['action'] == 'delete') || (isset($_GET['action']) && $_GET['action'] == 'add_new' && isset($_POST['add_new_template']))) { ?>


        <div class="tablenav">
            <div class="alignright actions new-actions">
                <form method="get" action="edit.php?post_type=tc_events&page=<?php echo esc_attr($page); ?>" class="search-form">
                    <p class="search-box">
                        <input type='hidden' name='page' value='<?php echo esc_attr($page); ?>' />
                        <input type="hidden" name="post_type" value="tc_events" />
                        <label class="screen-reader-text"><?php _e('Search Templates', 'tc'); ?>:</label>
                        <input type="text" value="<?php echo esc_attr($templatessearch); ?>" name="s">
                        <input type="submit" class="button" value="<?php _e('Search Templates', 'tc'); ?>">
                    </p>
                </form>
            </div><!--/alignright-->

        </div><!--/tablenav-->


        <table cellspacing="0" class="widefat shadow-table">
            <thead>
                <tr>
                    <?php
                    $n = 1;
                    foreach ($columns as $key => $col) {
                        
                        ?>
                        <th style="" class="manage-column column-<?php echo esc_attr($key); ?>" width="<?php echo (isset($col_sizes[$n]) ? esc_attr($col_sizes[$n] . '%') : ''); ?>" id="<?php echo esc_attr($key); ?>" scope="col"><?php echo $col; ?></th>
                        <?php
                        $n++;
                    }
                    ?>
                </tr>
            </thead>

            <tbody>
                <?php
                $style = '';

                foreach ($wp_templates_search->get_results() as $template) {
                    if ($template->post_status !== 'trash') {
                        $template_obj = new TC_Template($template->ID);
                        $template_object = apply_filters('tc_template_object_details', $template_obj->details);
                        
                        $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
                        ?>
                        <tr id='user-<?php echo $template_object->ID; ?>' <?php echo $style; ?>>
                            <?php
                            $n = 1;
                            foreach ($columns as $key => $col) {
                                
                                if ($key == 'edit') {
                                    ?>
                                    <td>                    
                                        <a class="templates_edit_link" href="<?php echo admin_url('edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'save_template'); ?>"><?php _e('Edit', 'tc'); ?></a>
                                    </td>
                                <?php } elseif ($key == 'delete') {
                                    ?>
                                    <td>
                                        <a class="templates_edit_link tc_delete_link" href="<?php echo wp_nonce_url('edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'delete_' . $template_object->ID); ?>"><?php _e('Delete', 'tc'); ?></a>
                                    </td>
                                    <?php
                                }elseif ($key == 'tc_duplicate') {//Add Duplicate field
                                    ?>
                                    <td>
                                        <a class="templates_edit_link" id="tc_template_duplicate" title="<?php esc_attr(__('Duplicate this ticket templatet', 'tc'));?>" href="<?php echo wp_nonce_url('edit.php?post_type=tc_events&page=' . $page . '&action=' . $key . '&ID=' . $template_object->ID, 'tc_duplicate' . $template_object->ID); ?>" rel="permalink"><?php _e('Duplicate', 'tc'); ?></a>
                                    </td>
                                    <?php
								} else {
                                    ?>
                                    <td>
                                        <?php echo apply_filters('tc_template_field_value', $template_object->{$key}); ?>
                                    </td>
                                    <?php
                                }
                            }
                            ?>
                        </tr>
                        <?php
                    }//if($template['post_status'] !== 'trash')
                }//$wp_templates_search->get_results() as $template
                ?>

                <?php
                if (count($wp_templates_search->get_results()) == 0) {
                    ?>
                    <tr>
                        <td colspan="6"><div class="zero-records"><?php _e('No templates found.', 'tc') ?></div></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table><!--/widefat shadow-table-->

        <p>
            <?php if (!tc_iw_is_pr() || tets_fs()->is_free_plan()) { ?>
                <a class="tc_link" target="_blank" href="https://tickera.com/?utm_source=plugin&utm_medium=upsell&utm_campaign=templates"><?php _e('Create unlimited number of ticket templates, get premium support and unlock additional features.'); ?></a>
            <?php }
            ?>
        </p>

        <div class="tablenav">
            <div class="tablenav-pages"><?php $wp_templates_search->page_links(); ?></div>
        </div><!--/tablenav-->

    <?php } else { ?>

        <form action="" method="post" enctype = "multipart/form-data">
            <div class="left-holder">
                <?php wp_nonce_field('save_template'); ?>
                <?php
                if (isset($post_id)) {
                    ?>
                    <input type="hidden" name="post_id" value="<?php echo (int) $post_id; ?>" />
                    <?php
                }
                ?>

                <h4><?php _e('Template Title', 'tc'); ?></h4>
                <input type="text" name="template_title" value="<?php echo esc_attr(isset($template->details->post_title) ? $template->details->post_title : '' ); ?>">

                <h4><?php _e('Ticket Elements', 'tc'); ?></h4>

                <input type="hidden" name="template_id" value="<?php echo esc_attr(isset($_GET['ID']) ? (int) $_GET['ID'] : '' ); ?>" />

                <ul class="sortables droptrue" id="ticket_elements">
                    <?php
                    foreach ($tc_template_elements as $element) {
                        $element_class = new $element[0];
                        if (!in_array($element[0], $template_elements_set)) {
                            ?>
                            <li class="ui-state-default" data-class="<?php echo $element[0]; ?>">

                                <div class="elements-wrap">
                                    <div class="element_title"><?php echo $element[1]; ?><a class="close-this" href="#"><i class="fa fa-times"></i></a></div>


                                    <div class="element-icon">
                                        <?php if (empty($element_class->font_awesome_icon)) { ?>
                                            <i class="fa fa-plus-circle"></i>
                                        <?php } else { ?>
                                            <?php echo $element_class->font_awesome_icon; ?>
                                        <?php } ?>
                                    </div><!-- .element-icon -->
                                </div><!-- .elements-wrap -->


                                <div class="element_content">                                                                           
                                    <?php echo $element_class->admin_content(); ?>
                                </div>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>

                <br clear="all" />
                <h4><?php _e('Ticket', 'tc'); ?></h4>
                <div class="rows ticket-elements-drop-area">
                    <?php for ($i = 1; $i <= apply_filters('tc_ticket_template_row_number', 10); $i++) { ?>
                        <ul id="row_<?php echo $i; ?>" class="sortables droptrue"><span class="row_num_info"><?php _e('Row', 'tc'); ?> <?php echo $i; ?></span><input type="hidden" class="rows_classes" name="rows_<?php echo $i; ?>_post_meta" value="" />
                            <?php
                            if (isset($post_id)) {
                                $rows_elements = get_post_meta($post_id, 'rows_' . $i, true);
                                if (isset($rows_elements) && $rows_elements !== '') {
                                    $element_class_names = explode(',', $rows_elements);
                                    foreach ($element_class_names as $element_class_name) {
                                        if (class_exists($element_class_name)) {
                                            if (isset($post_id)) {
                                                $element = new $element_class_name($post_id);
                                            } else {
                                                $element = new $element_class_name;
                                            }
                                            ?>
                                            <li class="ui-state-default cols" data-class="<?php echo $element_class_name; ?>">


                                                <div class="elements-wrap">
                                                    <div class="element_title"><?php echo $element->element_title; ?><a class="close-this" href="#"><i class="fa fa-times"></i></a></div>



                                                    <div class="element-icon">
                                                        <?php if (empty($element->font_awesome_icon)) { ?>
                                                            <i class="fa fa-plus-circle"></i>
                                                        <?php } else { ?>
                                                            <?php echo $element->font_awesome_icon; ?>
                                                        <?php } ?>
                                                    </div><!-- .element-icon -->
                                                </div><!-- .elements-wrap -->

                                                <div class="element_content"><?php $element->admin_content(); ?></div>
                                            </li>
                                            <?php
                                        }
                                    }
                                }
                            }
                            ?>
                        </ul>
                    <?php } ?>

                    <br style="clear:both">
                </div>
                <input type="hidden" name="rows_number_post_meta" value="<?php echo (int) apply_filters('tc_ticket_template_row_number', 10); ?>" />
                <br clear="all" /> 
                <?php submit_button(__('Save', 'tc'), 'primary', 'add_new_template', true); ?>
            </div>
            <div class="right-holder">
                <h4><?php _e('Ticket PDF Settings', 'tc'); ?></h4>
                <div id="template_document_settings">
                    <?php
                    do_action('tc_template_elements_side_bar_before_fonts', $template_elements);
                    $template_elements->tcpdf_get_fonts();
                    do_action('tc_template_elements_side_bar_before_document_sizes', $template_elements);
                    $template_elements->get_document_sizes();
                    do_action('tc_template_elements_side_bar_before_orientation', $template_elements);
                    $template_elements->get_document_orientation();
                    do_action('tc_template_elements_side_bar_before_margins', $template_elements);
                    $template_elements->get_document_margins();
                    do_action('tc_template_elements_side_bar_before_background_image', $template_elements);
                    $template_elements->get_full_background_image();
                    do_action('tc_template_document_settings', $template_elements);
                    ?>
                    <br />
                    <?php submit_button(__('Save', 'tc'), 'primary', 'add_new_template', false); ?>
                    <div class="clear"></div>
                </div>
            </div>
            <div class="right-holder right-holder-second">
                <?php if (!isset($_GET['ID'])) { ?>
                    <p><?php _e('NOTE: After saving, you will have an option to see a preview of the ticket.', 'tc'); ?></p>
                <?php } else { ?>
                    <p><?php _e('NOTE: Save changes first, then check the preview.</br></br><strong>Important:</strong> Once done with creating a ticket template, make a test purchase of a ticket that is using this template and test ticket scanning functionality prior to going live with the ticket sales.', 'tc'); ?></p>
                    <a href="<?php echo admin_url('edit.php?post_type=tc_events&page=' . $_GET['page'] . '&action=preview&ID=' . (int) $_GET['ID']); ?>" class="button button-secondary" target="_blank"><?php _e('Preview', 'tc'); ?></a>
                <?php } ?>
            </div>
        </form>

    </div>
<?php } ?>