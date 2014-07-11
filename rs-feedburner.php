<?php
/*
Plugin Name: RS Feedburner
Plugin URI: http://www.redsandmarketing.com/plugins/rs-feedburner/
Description: This plugin detects native WordPress feeds and redirects them to your FeedBurner feed so you can track your subscribers. 
Author: Scott Allen
Version: 1.1
Author URI: http://www.redsandmarketing.com/
License: GPLv2
*/

/*  Copyright 2014    Scott Allen  (email : plugins [at] redsandmarketing [dot] com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// PLUGIN - BEGIN

/* Note to any other PHP developers reading this:
My use of the end curly braces "}" is a little funky in that I indent them, I know. IMO it's easier to debug. Just know that it's on purpose even though it's not standard. One of my programming quirks, and just how I roll. :)
*/

// Make sure plugin remains secure if called directly
if ( !function_exists( 'add_action' ) ) {
	if ( !headers_sent() ) {
		header('HTTP/1.1 403 Forbidden');
		}
	die('ERROR: This plugin requires WordPress and will not function if called directly.');
	}

define( 'RSFB_VERSION', '1.1' );
define( 'RSFB_REQUIRED_WP_VERSION', '3.0' );
if ( ! defined( 'RSMP_SITE_URL' ) ) 			{ define( 'RSMP_SITE_URL', untrailingslashit( site_url() ) ); } 						// http://example.com
if ( ! defined( 'RSMP_PLUGINS_DIR_URL' ) ) 		{ define( 'RSMP_PLUGINS_DIR_URL', untrailingslashit( plugins_url() ) ); } 				// http://example.com/wp-content/plugins
if ( ! defined( 'RSMP_CONTENT_DIR_URL' ) ) 		{ define( 'RSMP_CONTENT_DIR_URL', untrailingslashit( content_url() ) ); } 				// http://example.com/wp-content
if ( ! defined( 'RSMP_ADMIN_URL' ) ) 			{ define( 'RSMP_ADMIN_URL', untrailingslashit( admin_url() ) ); }						// http://example.com/wp-admin
if ( ! defined( 'RSFB_PLUGIN_BASENAME' ) ) 		{ define( 'RSFB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); }						// rs-feedburner/rs-feedburner.php
if ( ! defined( 'RSFB_PLUGIN_FILE_BASENAME' ) )	{ define( 'RSFB_PLUGIN_FILE_BASENAME', trim( basename( __FILE__ ), '/' ) ); }			// rs-feedburner.php
if ( ! defined( 'RSFB_PLUGIN_NAME' ) ) 			{ define( 'RSFB_PLUGIN_NAME', trim( dirname( RSFB_PLUGIN_BASENAME ), '/' ) ); }			// rs-feedburner
if ( ! defined( 'RSFB_PLUGIN_URL' ) ) 			{ define( 'RSFB_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) ); }		// http://example.com/wp-content/plugins/rs-feedburner
if ( ! defined( 'RSFB_PLUGIN_FILE_URL' ) ) 		{ define( 'RSFB_PLUGIN_FILE_URL', RSFB_PLUGIN_URL.'/'.RSFB_PLUGIN_FILE_BASENAME ); }	// http://example.com/wp-content/plugins/rs-feedburner/rs-feedburner.php
if ( ! defined( 'RSFB_PLUGIN_IMG_URL' ) ) 		{ define( 'RSFB_PLUGIN_IMG_URL', RSFB_PLUGIN_URL . '/img' ); }							// http://example.com/wp-content/plugins/rs-feedburner/img
if ( ! defined( 'RSFB_PLUGIN_PATH' ) ) 			{ define( 'RSFB_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) ); } 	// /public_html/wp-content/plugins/rs-feedburner
if ( ! defined( 'RSFB_PLUGIN_FILE_PATH' ) )		{ define( 'RSFB_PLUGIN_FILE_PATH', RSFB_PLUGIN_PATH.'/'.RSFB_PLUGIN_FILE_BASENAME ); }	// /public_html/wp-content/plugins/rs-feedburner/rs-feedburner.php
if ( ! defined( 'RSMP_SERVER_ADDR' ) ) 			{ define( 'RSMP_SERVER_ADDR', rsfb_get_server_addr() ); }								// 10.20.30.100
if ( ! defined( 'RSMP_SERVER_NAME' ) ) 			{ define( 'RSMP_SERVER_NAME', rsfb_get_server_name() ); }								// example.com
if ( ! defined( 'RSMP_BLOG_NAME' ) ) 			{ define( 'RSMP_BLOG_NAME', get_bloginfo('name') ); }									// Blog Name

$rsfb_flash = '';
$rsfb_feedburner_settings = get_option('rs_feedburner_settings');

function rsfb_is_hash_valid($form_hash) {
	$ret = false;
	$saved_hash = rsfb_retrieve_hash();
	if ($form_hash === $saved_hash) { $ret = true; }
	return $ret;
	}

function rsfb_generate_hash() {
	$new_hash = md5( uniqid( rand(), TRUE ) );
	return $new_hash;
	}

function rsfb_store_hash($rsfb_generated_hash) {
	return update_option('rs_feedburner_token',$rsfb_generated_hash,'RS Feedburner Security Hash');
	}

function rsfb_retrieve_hash() {
	$ret = get_option('rs_feedburner_token');
	return $ret;
	}

function rsfb_add_plugin_settings_page() {
	if ( function_exists('add_options_page') ) {
		add_options_page('RS FeedBurner', 'RS FeedBurner', 'manage_options', RSFB_PLUGIN_BASENAME, 'rsfb_feedburner_options_subpanel');
		}
	}

function rsfb_filter_plugin_actions( $links, $file ) {
	// Add "Settings" Link on Admin Plugins page, in plugin listings
	if ( $file == RSFB_PLUGIN_BASENAME ){
		$settings_link = '<a href="options-general.php?page='.RSFB_PLUGIN_BASENAME.'">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
		}
	return $links;
	}

function rsfb_feedburner_options_subpanel() {
	global $rsfb_flash, $rsfb_feedburner_settings, $_POST, $wp_rewrite;
	if ( current_user_can('manage_options') ) {
		// Easiest test to see if we have been submitted to
		if(isset($_POST['rs_feedburner_url']) || isset($_POST['rs_feedburner_comments_url'])) {
			// Now we check the hash, to make sure we are not getting CSRF
			if(rsfb_is_hash_valid($_POST['rs_token'])) {
				if (isset($_POST['rs_feedburner_url'])) { 
					$rsfb_feedburner_settings['rs_feedburner_url'] = $_POST['rs_feedburner_url'];
					update_option('rs_feedburner_settings',$rsfb_feedburner_settings);
					$rsfb_flash = "Your settings have been saved.";
					}
				if (isset($_POST['rs_feedburner_comments_url'])) { 
					$rsfb_feedburner_settings['rs_feedburner_comments_url'] = $_POST['rs_feedburner_comments_url'];
					update_option('rs_feedburner_settings',$rsfb_feedburner_settings);
					$rsfb_flash = "Your settings have been saved.";
					} 
				} else {
				// Invalid form hash, possible CSRF attempt
				$rsfb_flash = "Security hash missing.";
				} // endif rsfb_is_hash_valid
			} // endif isset(feedburner_url)
		} else {
		$rsfb_flash = "You don't have enough access rights.";
		}
	
	if ($rsfb_flash != '') echo '<div id="message"class="updated fade"><p>' . $rsfb_flash . '</p></div>';
	
	if ( current_user_can('manage_options') ) {
		$rsfb_temp_hash = rsfb_generate_hash();
		rsfb_store_hash($rsfb_temp_hash);
		$rsfb_token_value=rsfb_retrieve_hash();
		echo '<div class="wrap">';
		echo '<h2 style="color:#7c2001">' . __( 'RS FeedBurner Settings', RSFB_PLUGIN_NAME ) . '</h2>';
		echo '<p><img src="'.RSFB_PLUGIN_IMG_URL.'/rs-feedburner-icon-36.png" width="36" height="36" align="left" style="width:36px;height:36px;border-style:none;vertical-align:middle;padding-right:12px;padding-top:2px;float:left;" alt="" />This plugin helps you redirect all inbound traffic for your feeds to your custom FeedBurner feed.<br />FeedBurner tracks all your feed subscriber traffic and usage and enhance your original WordPress feed.</p>
		<form action="" method="post">
		<input type="hidden" name="redirect" value="true" />
		<input type="hidden" name="rs_token" value="'.$rsfb_token_value.'" />
		<ol>
		<li>If you haven\'t done so already, <a href="http://feedburner.google.com/" target="_blank">create a FeedBurner feed for '.RSMP_BLOG_NAME.'</a>.
		This feed will handle all traffic for your posts.</li>
		<li>Once you have created your FeedBurner feed, enter its address into the field below (<strong>http://feeds.feedburner.com/YourFeed</strong>):<br /><input type="text" name="rs_feedburner_url" value="'.htmlentities($rsfb_feedburner_settings['rs_feedburner_url']).'" size="45" /></li>
		<li>Optional: If you also want to use FeedBurner for your WordPress comments feed, <a href="http://feedburner.google.com/" target="_blank">create a FeedBurner comments feed</a> and then enter its address below:<br /><input type="text" name="rs_feedburner_comments_url" value="'.htmlentities($rsfb_feedburner_settings['rs_feedburner_comments_url']).'" size="45" />
		</ol>
		<p><input type="submit" value="Save Settings" /></p></form>';
		echo '</div>';
		} 
	else {
		echo '<div class="wrap"><p>Sorry, you are not allowed to access this page.</p></div>';
		}
	}

function rsfb_feed_redirect() {
	global $wp, $rsfb_feedburner_settings, $feed, $withcomments;
	$rsfb_query_vars = '';
	if ( !empty( $wp->query_vars['category_name'] ) ) {
		$rsfb_query_vars = $wp->query_vars['category_name'];
		}
	$rsfb_feedburner_main_url 		= trim( $rsfb_feedburner_settings['rs_feedburner_url'] );
	$rsfb_feedburner_comments_url	= trim( $rsfb_feedburner_settings['rs_feedburner_comments_url'] );
	if ( is_feed() && $feed != 'comments-rss2' && !is_single() && $rsfb_query_vars == '' && ( $withcomments != 1 ) && $rsfb_feedburner_main_url != '' ) {
		if (function_exists('status_header')) { status_header( 302 ); }
		header("Location:" . $rsfb_feedburner_main_url );
		header("HTTP/1.1 302 Temporary Redirect");
		exit();
		} 
	elseif ( is_feed() && ( $feed == 'comments-rss2' || $withcomments == 1 ) && $rsfb_feedburner_comments_url != '' ) {
		if (function_exists('status_header')) { status_header( 302 ); }
		header("Location:" . $rsfb_feedburner_comments_url );
		header("HTTP/1.1 302 Temporary Redirect");
		exit();
		}
	}

function rsfb_check_url() {
	if ( is_feed() ) {
		global $rsfb_feedburner_settings;
		switch (basename($_SERVER['PHP_SELF'])) {
			case 'wp-rss.php':
			case 'wp-rss2.php':
			case 'wp-atom.php':
			case 'wp-rdf.php':
				if (trim($rsfb_feedburner_settings['rs_feedburner_url']) != '') {
					if (function_exists('status_header')) status_header( 302 );
					header("Location:".trim($rsfb_feedburner_settings['rs_feedburner_url']));
					header("HTTP/1.1 302 Temporary Redirect");
					exit();
					}
				break;
			case 'wp-commentsrss2.php':
				if (trim($rsfb_feedburner_settings['rs_feedburner_comments_url']) != '') {
					if (function_exists('status_header')) status_header( 302 );
					header("Location:".trim($rsfb_feedburner_settings['rs_feedburner_comments_url']));
					header("HTTP/1.1 302 Temporary Redirect");
					exit();
					}
				break;
			}
		}
	}

if (!preg_match("~feedburner|feedvalidator~i", $_SERVER['HTTP_USER_AGENT'])) {
	add_action('template_redirect', 'rsfb_feed_redirect');
	add_action('init','rsfb_check_url');
	}

add_action( 'admin_menu', 'rsfb_add_plugin_settings_page' );
add_filter( 'plugin_action_links', 'rsfb_filter_plugin_actions', 10, 2 );

// Standard Functions - BEGIN
function rsfb_get_server_addr() {
	if ( !empty( $_SERVER['SERVER_ADDR'] ) ) { $server_addr = $_SERVER['SERVER_ADDR']; } else { $server_addr = getenv('SERVER_ADDR'); }
	return $server_addr;
	}
function rsfb_get_server_name() {
	if ( !empty( $_SERVER['SERVER_NAME'] ) ) { $server_name = strtolower( $_SERVER['SERVER_NAME'] ); } else { $server_name = strtolower( getenv('SERVER_NAME') ); }
	return $server_name;
	}
function rsfb_admin_notices() {
	$admin_notices = get_option('rsfb_admin_notices');
	if ( !empty( $admin_notices ) ) {
		$style 	= $admin_notices['style']; // 'error'  or 'updated'
		$notice	= $admin_notices['notice'];
		echo '<div class="'.$style.'"><p>'.$notice.'</p></div>';
		}
	delete_option('rsfb_admin_notices');
	}
// Standard Functions - END

// Admin Functions - BEGIN
register_activation_hook( __FILE__, 'rsfb_install_on_activation' );
function rsfb_install_on_activation() {
	$installed_ver = get_option('rs_feedburner_version');
	if ( empty( $installed_ver ) || $installed_ver != RSFB_VERSION ) {
		update_option('rs_feedburner_version', RSFB_VERSION);
		}
	}
add_action( 'admin_init', 'rsfb_check_version' );
function rsfb_check_version() {
	global $wp_version;
	$rsfb_wp_version = $wp_version;
	if ( version_compare( $rsfb_wp_version, RSFB_REQUIRED_WP_VERSION, '<' ) ) {
		deactivate_plugins( RSFB_PLUGIN_BASENAME );
		$notice_text = sprintf( __( 'Plugin deactivated. WordPress Version %s required. Please upgrade WordPress to the latest version.', RSFB_PLUGIN_NAME ), RSFB_REQUIRED_WP_VERSION );
		$new_admin_notice = array( 'style' => 'error', 'notice' => $notice_text );
		update_option( 'rsfb_admin_notices', $new_admin_notice );
		add_action( 'admin_notices', 'rsfb_admin_notices' );
		return false;
		}
	add_action( 'admin_notices', 'rsfb_admin_notices' );
	}
// Admin Functions - END


// PLUGIN - END
?>
