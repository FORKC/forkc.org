<?php
/**
 * Simple add pages or posts.
 *
 * @category      WordPress Plugins
 * @package       Plugins
 * @author        Simon Dirlik, Ramon Fincken
 * @copyright     Yes, Open source
 */
if (!defined('ABSPATH'))
die("Aren't you supposed to come here via WP-Admin?");

// We need DB connection
global $wpdb;

// Init
$separator = ( ( isset( $_POST['separator'] ) ) ? sanitize_text_field( $_POST['separator'] ) :  '-');

/**
 * If submiting the form
 */
if ( isset ( $_POST['submitbutton'] ) && isset ( $_POST['postorpage'] ) ) {

	if (!isset( $_POST['nonce_check'] ) || ! wp_verify_nonce( $_POST['nonce_check'], 'mp_sapop' ) ) {
		$message = $title = __('Sorry, your nonce did not verify.', 'mp-simpleaddpagesorposts');
		wp_die( $message, $title);
	}

	if (!isset ($_POST['titles']) || !$_POST['titles']) {
		echo '<div id="message" class="error">'.__('No titles given', 'mp-simpleaddpagesorposts').'</div>';
	} else {
		//Is magic quotes on?
		if (get_magic_quotes_gpc()) {
			// Yes? Strip the added slashes
			$_POST = array_map('stripslashes', $_POST);
		}

		//logic
		switch ($_POST['postorpage']) {
			case 'post' :
				$page_or_post = 'post';
				break;
			case 'page' :
				$page_or_post = 'page';
				break;
		}


		$author_id = 1; // Default admin user_id
		// Check user input
		$user_query = "SELECT ID, user_login, display_name, user_email FROM $wpdb->users WHERE ID = ".intval($_POST['author_id']) . " LIMIT 1";
		$users = $wpdb->get_results($user_query);
		foreach ($users AS $row) {
			// User found, replace value of $author
			$author_id = $row->ID;
		}


		// Foreach line
		$titles = explode("\n", $_POST['titles']);

		// Page?
		if($page_or_post == 'page')
		{
			$i = 0;
			$post_parent_org =	intval($_POST['post_parent']);
				
			$newarray = array();
			$lastlevel = $post_parent_org;

			foreach ($titles as $title) {
				// Remove whitespaces left and right			
				$title = trim( $title );

				// Now remove minus'ses at left position
				$title_ltrim = ltrim( $title, $separator );
				
				// The level is the difference between trim and ltrim
				$level = strlen( $title )-strlen( $title_ltrim );
				
				// Fix for minus within title like: "Some-title"
				// $level = substr_count($title, '-');
				
				// Now store title
				$title = $title_ltrim;

				// Init
				$newarray[$i] = array( 
					'level' => $level,
					'lastlevel' => $lastlevel,
					'title' => $title,
					'child_of_page_id' => $post_parent_org,
					'page_id' => NULL,
				);

				// First child?
				if( $level > $lastlevel )
				{
					$newarray[$i]['child_of_page_id'] = $newarray[$i-1]['page_id'];
					$newarray[$i]['child_of_page_title'] = $newarray[$i-1]['title'];
				}

				// Same sub as previous?
				if( $level == $lastlevel )
				{
					// Go back to find sub
					$j = $i;$continue = true;
					while( $j >= 0 && $continue )
					{
						if( $level > $newarray[$j]['level'] )
						{
							$newarray[$i]['child_of_page_id'] = $newarray[$j]['page_id'];
							$newarray[$i]['child_of_page_title'] = $newarray[$j]['title'];
							$continue = false;
						}
						$j--;
					}
				}

				// Second child, but after a child-child?
				if( $level < $lastlevel )
				{
					// Go back to find sub
					$j = $i;$continue = true;
					while( $j >= 0 && $continue )
					{
						if( $level > $newarray[$j]['level'] )
						{
							$newarray[$i]['child_of_page_id'] = $newarray[$j]['page_id'];
							$newarray[$i]['child_of_page_title'] = $newarray[$j]['title'];
							$continue = false;
						}
						$j--;
					}
				}

				// Now insert
				// Create post object
				$post = array ();
				$post['post_title'] = $title;
				$post['post_type'] = $page_or_post;
				$post['post_content'] = '';
				$post['post_status'] = 'publish';
				$post['post_author'] = $author_id;
				if( $page_or_post == 'page' )
				{
					// Do hierarchy
					$post['post_parent'] = $newarray[$i]['child_of_page_id'];
				}

				// GOGOGO
				$this_page_id = wp_insert_post( $post );

				// Update
				$newarray[$i]['page_id'] = $this_page_id;

				$lastlevel = $level;
				$i++;
			}
		}
		else
		{
			// Post
			$i = 0;
			foreach ( $titles as $title ) {

				// Remove spaces before and after titles
				$title = trim( $title );
				// No empty title?
				if ( !empty ( $title ) ) {
					$i++;
					// Create post object
					$post = array ();
					$post['post_title'] = $title;
					$post['post_type'] = $page_or_post;
					$post['post_content'] = '';
					$post['post_status'] = 'publish';
					$post['post_author'] = $author_id;
					// http://www.ramonfincken.com/permalink/topic184.html
					// Insert the post into the database
					wp_insert_post($post);
				}
			}
		}
		echo '<div id="message" class="updated fade">';
		printf(
		    /* translators: Page or post */
		    __( '%s new %2$s(s) were created.', 'mp-simpleaddpagesorposts' ),
		    number_format_i18n($i),
		    $page_or_post
		);
		echo '</div>';
	}
}

?>

<br />
<h3>Custom Post Type support</h3>
<p><a href="https://webshop.mijnpress.nl/product-category/plugins">Buy our premium plugin to add Custom Post Type (CPT) support!</a></p> 
<br/>

<form id="mp_simpleaddpagesorposts" name="form1" method="post" action="" onsubmit="return confirm('<?php _e('Are you sure?', 'mp-simpleaddpagesorposts'); ?>')">

<?php wp_nonce_field( 'mp_sapop', 'nonce_check' ); ?>
<table class="widefat">
	<thead>
		<tr>
			<th class="manage-column" style="width: 250px;"><?php _e('Option', 'mp-simpleaddpagesorposts'); ?></th>
			<th colspan="2" class="manage-column"><?php _e('Setting', 'mp-simpleaddpagesorposts'); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr class="alternate iedit">
			<td>Post or page:</td>
			<td colspan="2"><select name="postorpage">
				<option value="page"><?php _e('Page'); ?></option>
				<option value="post"><?php _e('Post'); ?></option>
			</select></td>
		</tr>
		<tr class="iedit">
         	<td></td>
			<td colspan="2"><?php wp_dropdown_pages(array('exclude_tree' => 0, 'selected' => 0, 'name' => 'post_parent', 'show_option_none' => __('No, do not use parent'), 'sort_column'=> 'menu_order, post_title')); ?></td>
		</tr>
		<tr class="alternate iedit">
			<td valign="top"><?php _e('Titles:', 'mp-simpleaddpagesorposts'); ?><br />
			<small><?php _e('Each new post/page on a new line', 'mp-simpleaddpagesorposts'); ?></small></td>
			<td><textarea name="titles" rows="8" cols="30"></textarea></td>
			<td><?php _e('Advanced multi-parent example', 'mp-simpleaddpagesorposts'); ?><br/>
			<textarea name="titles_disabled" disabled="disabled" rows="4" cols="30">Toplevel item1
-Sub of toplevel item1
-Sub of toplevel item1
Toplevel item2
-Sublevel 2.1
--Sub of Sublevel 2.1
--Sub of Sublevel 2.1
-Sublevel 2.2
Toplevel item3</textarea></td>
		</tr>
		<tr class="iedit">
			<td valign="top"><?php _e('Hierarchy separator:', 'mp-simpleaddpagesorposts'); ?><br/>
			<small><?php _e('Change this to (for instance) * when your title list is indented by * instead of -', 'mp-simpleaddpagesorposts'); ?></small></td>
			<td><input type="text" name="separator" value="<?php echo $separator; ?>" />
</td>
			<td></td>
		</tr>
		<tr class="alternate iedit">
			<td valign="top"><?php _e('Author of post/page:', 'mp-simpleaddpagesorposts'); ?></td>
			<td colspan="2"><select name="author_id">
			<?php
			$user_query = "SELECT ID, user_login, display_name, user_email FROM $wpdb->users ORDER BY ID ASC";
			$users = $wpdb->get_results($user_query);
			foreach ($users AS $row) {
				echo '<option value="'.$row->ID.'">'.$row->display_name. '</option>';
			}
			?>
			</select></td>
		</tr>
	</tbody>
</table>
<input type="submit" name="submitbutton" value="<?php _e('Add', 'mp-simpleaddpagesorposts'); ?>"
	class="button-primary"></form>


<h3><?php _e('How to use?', 'mp-simpleaddpagesorposts'); ?></h3>
<p class="updated">
* <?php _e('Choose what you want to add: posts or pages', 'mp-simpleaddpagesorposts'); ?><br/>
* <?php _e('Type the title of each post or page on a seperate line in the textarea', 'mp-simpleaddpagesorposts'); ?><br/><br/>

<strong><?php _e('Optional:', 'mp-simpleaddpagesorposts'); ?></strong><br/>
* <?php _e('If it is a page or any other hierachical CPT, select the parent page. It will show automatically. (Default: none)', 'mp-simpleaddpagesorposts'); ?></p>





<style>
form#mp_simpleaddpagesorposts {
	margin-top: 15px;
}
#mp_simpleaddpagesorposts input[type="text"], 
#mp_simpleaddpagesorposts input[type="password"],
#mp_simpleaddpagesorposts input[type="file"],
#mp_simpleaddpagesorposts select {
	min-width: 250px;
}

#mp_simpleaddpagesorposts textarea {
	min-width: 300px;
	min-height: 200px;
}
</style>
