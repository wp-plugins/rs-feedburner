<?php
/*
Plugin Name: RS Feedburner
Plugin URI: http://www.redsandmarketing.com/plugins/rs-feedburner/
Description: This plugin detects native WordPress feeds and redirects them to your FeedBurner feed so you can track your subscribers. 
Author: Scott Allen
Version: 1.0.0.1
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

define( 'RSFB_VERSION', '1.0.0.1' );
define( 'RSFB_REQUIRED_WP_VERSION', '2.8' );
if ( ! defined( 'RSFB_SITE_URL' ) ) {
	define( 'RSFB_SITE_URL', untrailingslashit( site_url() ) ); // http://example.com
	}
if ( ! defined( 'RSFB_PLUGINS_DIR_URL' ) ) {
	define( 'RSFB_PLUGINS_DIR_URL', untrailingslashit( plugins_url() ) ); // http://example.com/wp-content/plugins
	}
if ( ! defined( 'RSFB_CONTENT_DIR_URL' ) ) {
	define( 'RSFB_CONTENT_DIR_URL', untrailingslashit( content_url() ) ); // http://example.com/wp-content
	}
if ( ! defined( 'RSFB_ADMIN_URL' ) ) {
	define( 'RSFB_ADMIN_URL', untrailingslashit( admin_url() ) ); // http://example.com/wp-admin
	}
if ( ! defined( 'RSFB_PLUGIN_BASENAME' ) ) {
	define( 'RSFB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // rs-feedburner/rs-feedburner.php
	}
if ( ! defined( 'RSFB_PLUGIN_FILE_BASENAME' ) ) {
	define( 'RSFB_PLUGIN_FILE_BASENAME', trim( basename( __FILE__ ), '/' ) ); // rs-feedburner.php
	}
if ( ! defined( 'RSFB_PLUGIN_NAME' ) ) {
	define( 'RSFB_PLUGIN_NAME', trim( dirname( RSFB_PLUGIN_BASENAME ), '/' ) ); // rs-feedburner
	}
if ( ! defined( 'RSFB_PLUGIN_URL' ) ) {
	define( 'RSFB_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) ); // http://example.com/wp-content/plugins/rs-feedburner
	}
if ( ! defined( 'RSFB_PLUGIN_FILE_URL' ) ) {
	define( 'RSFB_PLUGIN_FILE_URL', RSFB_PLUGIN_URL.'/'.RSFB_PLUGIN_FILE_BASENAME ); // http://example.com/wp-content/plugins/rs-feedburner/rs-feedburner.php
	}
if ( ! defined( 'RSFB_PLUGIN_IMG_URL' ) ) {
	define( 'RSFB_PLUGIN_IMG_URL', RSFB_PLUGIN_URL . '/img' ); // http://example.com/wp-content/plugins/rs-feedburner/img
	}
if ( ! defined( 'RSFB_PLUGIN_PATH' ) ) {
	define( 'RSFB_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) ); // /public_html/wp-content/plugins/rs-feedburner
	}
if ( ! defined( 'RSFB_PLUGIN_FILE_PATH' ) ) {
	define( 'RSFB_PLUGIN_FILE_PATH', RSFB_PLUGIN_PATH.'/'.RSFB_PLUGIN_FILE_BASENAME ); // /public_html/wp-content/plugins/rs-feedburner/rs-feedburner.php
	}
if ( ! defined( 'RSFB_SERVER_ADDR' ) ) {
	define( 'RSFB_SERVER_ADDR', $_SERVER['SERVER_ADDR'] ); // 10.20.30.100
	}
if ( ! defined( 'RSFB_SERVER_NAME' ) ) {
	define( 'RSFB_SERVER_NAME', $_SERVER['SERVER_NAME'] ); // example.com
	}
if ( ! defined( 'RSFB_BLOG_NAME' ) ) {
	define( 'RSFB_BLOG_NAME', get_bloginfo('name') ); // Blog Name
	}

$data = array(
	'rs_feedburner_url'				=> '',
	'rs_feedburner_comments_url'	=> ''
	);

$rs_flash = '';

function rs_is_authorized() {
	global $user_level;
	if (function_exists("current_user_can")) {
		return current_user_can('activate_plugins');
		} else {
		return $user_level > 5;
		}
	}
								
add_option('rs_feedburner_settings',$data,'RS FeedBurner Feed Options');

$rs_feedburner_settings = get_option('rs_feedburner_settings');

function rs_is_hash_valid($form_hash) {
	$ret = false;
	$saved_hash = rs_retrieve_hash();
	if ($form_hash === $saved_hash) {
		$ret = true;
		}
	return $ret;
	}

function rs_generate_hash() {
	return md5(uniqid(rand(), TRUE));
	}

function rs_store_hash($rs_generated_hash) {
	return update_option('rs_feedburner_token',$rs_generated_hash,'RS Feedburner Security Hash');
	}

function rs_retrieve_hash() {
	$ret = get_option('rs_feedburner_token');
	return $ret;
	}

function rs_add_feedburner_options_page() {
	if (function_exists('add_options_page')) {
		add_options_page('RS FeedBurner', 'RS FeedBurner', 8, RSFB_PLUGIN_BASENAME, 'rs_feedburner_options_subpanel');
		}
	}

function rs_feedburner_options_subpanel() {
	global $rs_flash, $rs_feedburner_settings, $_POST, $wp_rewrite;
	if (rs_is_authorized()) {
		// Easiest test to see if we have been submitted to
		if(isset($_POST['rs_feedburner_url']) || isset($_POST['rs_feedburner_comments_url'])) {
			// Now we check the hash, to make sure we are not getting CSRF
			if(rs_is_hash_valid($_POST['rs_token'])) {
				if (isset($_POST['rs_feedburner_url'])) { 
					$rs_feedburner_settings['rs_feedburner_url'] = $_POST['rs_feedburner_url'];
					update_option('rs_feedburner_settings',$rs_feedburner_settings);
					$rs_flash = "Your settings have been saved.";
					}
				if (isset($_POST['rs_feedburner_comments_url'])) { 
					$rs_feedburner_settings['rs_feedburner_comments_url'] = $_POST['rs_feedburner_comments_url'];
					update_option('rs_feedburner_settings',$rs_feedburner_settings);
					$rs_flash = "Your settings have been saved.";
					} 
				} else {
				// Invalid form hash, possible CSRF attempt
				$rs_flash = "Security hash missing.";
				} // endif rs_is_hash_valid
			} // endif isset(feedburner_url)
		} else {
		$rs_flash = "You don't have enough access rights.";
		}
	
	if ($rs_flash != '') echo '<div id="message"class="updated fade"><p>' . $rs_flash . '</p></div>';
	
	if (rs_is_authorized()) {
		$rs_temp_hash = rs_generate_hash();
		rs_store_hash($rs_temp_hash);
		$rs_token_value=rs_retrieve_hash();
		echo '<div class="wrap">';
		echo '<h2 style="color:#7c2001">RS FeedBurner Settings</h2>';
		echo '<p><img src="'.RSFB_PLUGIN_IMG_URL.'/rs-feedburner-icon-36.png" width="36" height="36" align="left" style="width:36px;height:36px;border-style:none;vertical-align:middle;padding-right:12px;padding-top:2px;float:left;" alt="" />This plugin helps you redirect all inbound traffic for your feeds to your custom FeedBurner feed.<br />FeedBurner tracks all your feed subscriber traffic and usage and enhance your original WordPress feed.</p>
		<form action="" method="post">
		<input type="hidden" name="redirect" value="true" />
		<input type="hidden" name="rs_token" value="'.$rs_token_value.'" />
		<ol>
		<li>If you haven\'t done so already, <a href="http://feedburner.google.com/" target="_blank">create a FeedBurner feed for '.RSFB_BLOG_NAME.'</a>.
		This feed will handle all traffic for your posts.</li>
		<li>Once you have created your FeedBurner feed, enter its address into the field below (<strong>http://feeds.feedburner.com/YourFeed</strong>):<br /><input type="text" name="rs_feedburner_url" value="'.htmlentities($rs_feedburner_settings['rs_feedburner_url']).'" size="45" /></li>
		<li>Optional: If you also want to use FeedBurner for your WordPress comments feed, <a href="http://feedburner.google.com/" target="_blank">create a FeedBurner comments feed</a> and then enter its address below:<br /><input type="text" name="rs_feedburner_comments_url" value="'.htmlentities($rs_feedburner_settings['rs_feedburner_comments_url']).'" size="45" />
		</ol>
		<p><input type="submit" value="Save Settings" /></p></form>';
		echo '</div>';
		} else {
		echo '<div class="wrap"><p>Sorry, you are not allowed to access this page.</p></div>';
		}

	}

function rs_feed_redirect() {
	global $wp, $rs_feedburner_settings, $feed, $withcomments;
	if (is_feed() && $feed != 'comments-rss2' && !is_single() && $wp->query_vars['category_name'] == '' && ($withcomments != 1) && trim($rs_feedburner_settings['rs_feedburner_url']) != '') {
		if (function_exists('status_header')) status_header( 302 );
		header("Location:" . trim($rs_feedburner_settings['rs_feedburner_url']));
		header("HTTP/1.1 302 Temporary Redirect");
		exit();
		} elseif (is_feed() && ($feed == 'comments-rss2' || $withcomments == 1) && trim($rs_feedburner_settings['rs_feedburner_comments_url']) != '') {
		if (function_exists('status_header')) status_header( 302 );
		header("Location:" . trim($rs_feedburner_settings['rs_feedburner_comments_url']));
		header("HTTP/1.1 302 Temporary Redirect");
		exit();
		}
	}

function rs_check_url() {
	global $rs_feedburner_settings;
	switch (basename($_SERVER['PHP_SELF'])) {
		case 'wp-rss.php':
		case 'wp-rss2.php':
		case 'wp-atom.php':
		case 'wp-rdf.php':
			if (trim($rs_feedburner_settings['rs_feedburner_url']) != '') {
				if (function_exists('status_header')) status_header( 302 );
				header("Location:".trim($rs_feedburner_settings['rs_feedburner_url']));
				header("HTTP/1.1 302 Temporary Redirect");
				exit();
				}
			break;
		case 'wp-commentsrss2.php':
			if (trim($rs_feedburner_settings['rs_feedburner_comments_url']) != '') {
				if (function_exists('status_header')) status_header( 302 );
				header("Location:".trim($rs_feedburner_settings['rs_feedburner_comments_url']));
				header("HTTP/1.1 302 Temporary Redirect");
				exit();
				}
			break;
		}
	}

if (!preg_match("/feedburner|feedvalidator/i", $_SERVER['HTTP_USER_AGENT'])) {
	add_action('template_redirect', 'rs_feed_redirect');
	add_action('init','rs_check_url');
	}

add_action('admin_menu', 'rs_add_feedburner_options_page');

// PLUGIN - END
?>
