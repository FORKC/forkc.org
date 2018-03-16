<?php
/**
Plugin Name: Simple add pages or posts
Plugin URI: https://www.mijnpress.nl
Description: Lets you add multiple pages or posts
Version: 1.8.2
Text Domain: mp-simpleaddpagesorposts
Author: Simon Dirlik, Ramon Fincken
Author URI: https://www.mijnpress.nl
Based on: https://www.mijnpress.nl/blog/plugin-framework/
*/

if (!defined('ABSPATH')) die("Aren't you supposed to come here via WP-Admin?");

if(!class_exists('mijnpress_plugin_framework'))
{
	include('mijnpress_plugin_framework.php');
}

function mp_plugin_simpleaddpagesorposts_load_plugin_textdomain() {
    load_plugin_textdomain( 'mp-simpleaddpagesorposts', FALSE, basename( dirname( __FILE__ ) ) ); // Translations in current folder
}
add_action( 'plugins_loaded', 'mp_plugin_simpleaddpagesorposts_load_plugin_textdomain' );

class plugin_simple_add_pages_or_posts extends mijnpress_plugin_framework
{
	function __construct()
	{
		$this->showcredits = true;
		$this->showcredits_fordevelopers = true;
		$this->plugin_title = 'Simple add pages or posts';
		$this->plugin_class = 'plugin_simple_add_pages_or_posts';
		$this->plugin_filename = 'simple-add-pages-or-posts/simple_add_pages_or_posts.php';
		$this->plugin_config_url = 'plugins.php?page='.$this->plugin_filename;
	}

	function plugin_simple_add_pages_or_posts()
	{
		$args= func_get_args();
		call_user_func_array
		(
		    array(&$this, '__construct'),
		    $args
		);
	}

	function addPluginSubMenu_($title)
	{
		$plugin = new plugin_simple_add_pages_or_posts();
		parent::addPluginSubMenu($plugin->plugin_title, array($plugin->plugin_class, 'admin_menu'), __FILE__);
	}

	/**
	 * Additional links on the plugin page
	 */
	function addPluginContent_($links, $file) {
		$plugin = new plugin_simple_add_pages_or_posts();
		$links = parent::addPluginContent($plugin->plugin_filename, $links, $file, $plugin->plugin_config_url);
		return $links;
	}

	/**
	 * Shows the admin plugin page
	 */
	public function admin_menu()
	{
		$plugin = new plugin_simple_add_pages_or_posts();		
		$plugin->content_start();		
		
		include('form.php');

		$plugin->content_end();
	}
}

// Admin only
if(mijnpress_plugin_framework::is_admin())
{
	add_action('admin_menu',  array('plugin_simple_add_pages_or_posts', 'addPluginSubMenu_'));
	add_filter('plugin_row_meta',array('plugin_simple_add_pages_or_posts', 'addPluginContent_'), 10, 2);
}
?>
