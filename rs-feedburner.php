<?php
/*
Plugin Name: RS FeedBurner
Plugin URI: http://www.redsandmarketing.com/plugins/rs-feedburner/
Description: This plugin detects native WordPress feeds and redirects them to your FeedBurner feed so you can track your subscribers. 
Author: Scott Allen
Version: 1.4.2
Author URI: http://www.redsandmarketing.com/
Text Domain: rs-feedburner
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
My use of the closing curly braces "}" is a little funky in that I indent them, I know. IMO it's easier to debug. Just know that it's on purpose even though it's not standard. One of my programming quirks, and just how I roll. :)
*/

// Make sure plugin remains secure if called directly
if ( !function_exists( 'add_action' ) ) {
	if ( !headers_sent() ) {
		header('HTTP/1.1 403 Forbidden');
		}
	die('ERROR: This plugin requires WordPress and will not function if called directly.');
	}

define( 'RSFB_VERSION', '1.4.2' );
define( 'RSFB_REQUIRED_WP_VERSION', '3.7' );
// Constants prefixed with 'RSMP_' are shared with other RSM Plugins for efficiency.
if ( !defined( 'RSFB_DEBUG' ) ) 				{ define( 'RSFB_DEBUG', false ); } // Do not change value unless developer asks you to - for debugging only. Change in wp-config.php.
if ( !defined( 'RSMP_SITE_URL' ) ) 				{ define( 'RSMP_SITE_URL', untrailingslashit( site_url() ) ); } 						// http://example.com
if ( !defined( 'RSMP_PLUGINS_DIR_URL' ) ) 		{ define( 'RSMP_PLUGINS_DIR_URL', untrailingslashit( plugins_url() ) ); } 				// http://example.com/wp-content/plugins
if ( !defined( 'RSMP_CONTENT_DIR_URL' ) ) 		{ define( 'RSMP_CONTENT_DIR_URL', untrailingslashit( content_url() ) ); } 				// http://example.com/wp-content
if ( !defined( 'RSMP_ADMIN_URL' ) ) 			{ define( 'RSMP_ADMIN_URL', untrailingslashit( admin_url() ) ); }						// http://example.com/wp-admin
if ( !defined( 'RSFB_PLUGIN_BASENAME' ) ) 		{ define( 'RSFB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); }						// rs-feedburner/rs-feedburner.php
if ( !defined( 'RSFB_PLUGIN_FILE_BASENAME' ) )	{ define( 'RSFB_PLUGIN_FILE_BASENAME', trim( basename( __FILE__ ), '/' ) ); }			// rs-feedburner.php
if ( !defined( 'RSFB_PLUGIN_NAME' ) ) 			{ define( 'RSFB_PLUGIN_NAME', trim( dirname( RSFB_PLUGIN_BASENAME ), '/' ) ); }			// rs-feedburner
if ( !defined( 'RSFB_PLUGIN_URL' ) ) 			{ define( 'RSFB_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) ); }		// http://example.com/wp-content/plugins/rs-feedburner
if ( !defined( 'RSFB_PLUGIN_FILE_URL' ) ) 		{ define( 'RSFB_PLUGIN_FILE_URL', RSFB_PLUGIN_URL.'/'.RSFB_PLUGIN_FILE_BASENAME ); }	// http://example.com/wp-content/plugins/rs-feedburner/rs-feedburner.php
if ( !defined( 'RSFB_PLUGIN_IMG_URL' ) ) 		{ define( 'RSFB_PLUGIN_IMG_URL', RSFB_PLUGIN_URL . '/img' ); }							// http://example.com/wp-content/plugins/rs-feedburner/img
if ( !defined( 'RSFB_PLUGIN_PATH' ) ) 			{ define( 'RSFB_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) ); } 	// /public_html/wp-content/plugins/rs-feedburner
if ( !defined( 'RSFB_PLUGIN_FILE_PATH' ) )		{ define( 'RSFB_PLUGIN_FILE_PATH', RSFB_PLUGIN_PATH.'/'.RSFB_PLUGIN_FILE_BASENAME ); }	// /public_html/wp-content/plugins/rs-feedburner/rs-feedburner.php
if ( !defined( 'RSMP_SERVER_ADDR' ) ) 			{ define( 'RSMP_SERVER_ADDR', rsfb_get_server_addr() ); }								// 10.20.30.100
if ( !defined( 'RSMP_SERVER_NAME' ) ) 			{ define( 'RSMP_SERVER_NAME', rsfb_get_server_name() ); }								// example.com
if ( !defined( 'RSMP_BLOG_NAME' ) ) 			{ define( 'RSMP_BLOG_NAME', get_bloginfo('name') ); }									// Blog Name

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
	return update_option('rs_feedburner_token',$rsfb_generated_hash,'RS FeedBurner Security Hash');
	}

function rsfb_retrieve_hash() {
	$ret = get_option('rs_feedburner_token');
	return $ret;
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
add_filter( 'plugin_row_meta', 'rsfb_filter_plugin_meta', 10, 2 ); // Added 1.4.1

// Standard Functions - BEGIN
function rsfb_get_server_addr() {
	if ( !empty( $_SERVER['SERVER_ADDR'] ) ) { $server_addr = $_SERVER['SERVER_ADDR']; } else { $server_addr = getenv('SERVER_ADDR'); }
	return $server_addr;
	}
function rsfb_get_server_name() {
	if ( !empty( $_SERVER['SERVER_NAME'] ) ) { $server_name = strtolower( $_SERVER['SERVER_NAME'] ); } else { $server_name = strtolower( getenv('SERVER_NAME') ); }
	return $server_name;
	}
function rsfb_is_lang_en_us( $strict = true ) {
	// Test if site is set to use English (US) - the default - or another language/localization
	$rsfb_locale = get_locale();
	if ( $strict != true ) {
		// Not strict - English, but localized translations may be in use
		if ( !empty( $rsfb_locale ) && !preg_match( "~^(en(_[a-z]{2})?)?$~i", $rsfb_locale ) ) { $lang_en_us = false; } else { $lang_en_us = true; }
		}
	else {
		// Strict - English (US), no translation being used
		if ( !empty( $rsfb_locale ) && !preg_match( "~^(en(_us)?)?$~i", $rsfb_locale ) ) { $lang_en_us = false; } else { $lang_en_us = true; }
		}
	return $lang_en_us;
	}
function rsfb_doc_txt() {
	$doc_txt = __( 'Documentation', RSFB_PLUGIN_NAME );
	return $doc_txt;
	}
// Standard Functions - END

// Admin Functions - BEGIN
register_activation_hook( __FILE__, 'rsfb_install_on_first_activation' );
function rsfb_install_on_first_activation() {
	$installed_ver = get_option('rs_feedburner_version');
	if ( empty( $installed_ver ) || $installed_ver != RSFB_VERSION ) {
		update_option('rs_feedburner_version', RSFB_VERSION);
		}
	}
add_action( 'admin_init', 'rsfb_check_version' );
function rsfb_check_version() {
	if ( current_user_can('manage_options') ) {
		// Make sure user has minimum required WordPress version, in order to prevent issues
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
add_action( 'plugins_loaded', 'rsfb_load_languages' );
function rsfb_load_languages() {
	load_plugin_textdomain( RSFB_PLUGIN_NAME, false, basename( dirname( __FILE__ ) ) . '/languages' );
	}
function rsfb_filter_plugin_actions( $links, $file ) {
	// Add "Settings" Link on Admin Plugins page, in plugin listings
	if ( $file == RSFB_PLUGIN_BASENAME ){
		$settings_link = '<a href="options-general.php?page='.RSFB_PLUGIN_NAME.'">' . __('Settings') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
		}
	return $links;
	}
function rsfb_filter_plugin_meta( $links, $file ) {
	// Add "Settings" Link on Admin Plugins page, in plugin meta
	if ( $file == RSFB_PLUGIN_BASENAME ){
		// after other links
		//$links[] = '<a href="options-general.php?page='.RSFB_PLUGIN_NAME.'">' . __('Settings') . '</a>';
		$links[] = '<a href="http://www.redsandmarketing.com/plugins/rs-feedburner/" target="_blank" rel="external" >' . rsfb_doc_txt() . '</a>';
		$links[] = '<a href="http://www.redsandmarketing.com/plugins/wordpress-plugin-support/" target="_blank" rel="external" >' . __( 'Support', RSFB_PLUGIN_NAME ) . '</a>';
		$links[] = '<a href="https://wordpress.org/support/view/plugin-reviews/rs-feedburner?rate=5#postform" target="_blank" rel="external" >' . __( 'Rate the Plugin', RSFB_PLUGIN_NAME ) . '</a>';
		$links[] = '<a href="http://bit.ly/rs-feedburner-donate" target="_blank" rel="external" >' . __( 'Donate', RSFB_PLUGIN_NAME ) . '</a>';
		}
	return $links;
	}
function rsfb_add_plugin_settings_page() {
	if ( function_exists('add_options_page') ) {
		add_options_page('RS FeedBurner', 'RS FeedBurner', 'manage_options', RSFB_PLUGIN_NAME, 'rsfb_plugin_settings_page');
		}
	}
function rsfb_plugin_settings_page() {

	if ( !current_user_can('manage_options') ) {
		$restricted_area_warning = __( 'You do not have sufficient permissions to access this page.' );
		wp_die( $restricted_area_warning );
		}
		
	global $rsfb_flash, $rsfb_feedburner_settings, $_POST, $wp_rewrite;
	if ( current_user_can('manage_options') ) {
		// Test to see if we have been submitted to
		if(isset($_POST['rs_feedburner_url']) || isset($_POST['rs_feedburner_comments_url'])) {
			// Now we check the hash, to make sure we are not getting CSRF
			if(rsfb_is_hash_valid($_POST['rs_token'])) {
				if (isset($_POST['rs_feedburner_url'])) { 
					$rsfb_feedburner_settings['rs_feedburner_url'] = $_POST['rs_feedburner_url'];
					update_option('rs_feedburner_settings',$rsfb_feedburner_settings);
					$rsfb_flash = __( 'Your settings have been saved.', RSFB_PLUGIN_NAME );
					}
				if (isset($_POST['rs_feedburner_comments_url'])) { 
					$rsfb_feedburner_settings['rs_feedburner_comments_url'] = $_POST['rs_feedburner_comments_url'];
					update_option('rs_feedburner_settings',$rsfb_feedburner_settings);
					$rsfb_flash = __( 'Your settings have been saved.', RSFB_PLUGIN_NAME );
					} 
				} else {
				// Invalid form hash, possible CSRF attempt
				$rsfb_flash = __( 'There was an error with your request.', RSFB_PLUGIN_NAME );
				} 
			}
		} else {
		$rsfb_flash = __( 'You do not have sufficient access rights.', RSFB_PLUGIN_NAME );
		}
	
	if ($rsfb_flash != '') echo '<div id="message"class="updated fade"><p>' . $rsfb_flash . '</p></div>';
	
	$rsfb_temp_hash = rsfb_generate_hash();
	rsfb_store_hash($rsfb_temp_hash);
	$rsfb_token_value=rsfb_retrieve_hash();
	echo '<div class="wrap">';
	echo '<h2 style="color:#7c2001">' . 'RS FeedBurner ' . __('Settings') . '</h2>';
	echo '<p><img src="'.RSFB_PLUGIN_IMG_URL.'/rs-feedburner-icon-36.png" width="36" height="36" align="left" style="width:36px;height:36px;border-style:none;vertical-align:middle;padding-right:12px;padding-top:2px;float:left;" alt="" />'.__( 'This plugin helps you redirect all inbound traffic for your feeds to your custom FeedBurner feed.<br />FeedBurner tracks all your feed subscriber traffic and usage and enhances your original WordPress feed.', RSFB_PLUGIN_NAME ).'</p>
	<form action="" method="post">
	<input type="hidden" name="redirect" value="true" />
	<input type="hidden" name="rs_token" value="'.$rsfb_token_value.'" />
	<ol>
	<li>'.sprintf(__( 'If you haven\'t done so already, <a href="http://feedburner.google.com/" target="_blank" rel="external" >create a FeedBurner feed for %s</a>. This feed will handle all traffic for your posts.', RSFB_PLUGIN_NAME ),RSMP_BLOG_NAME).'</li>
	<li>'.__( 'Once you have created your FeedBurner feed, enter its address into the field below (<strong>http://feeds.feedburner.com/YourFeed</strong>):', RSFB_PLUGIN_NAME ).'<br /><input type="text" name="rs_feedburner_url" value="'.htmlentities($rsfb_feedburner_settings['rs_feedburner_url']).'" size="45" /></li>
	<li>'.__( 'Optional: If you also want to use FeedBurner for your WordPress comments feed, <a href="http://feedburner.google.com/" target="_blank" rel="external" >create a FeedBurner comments feed</a> and then enter its address below:', RSFB_PLUGIN_NAME ).'<br /><input type="text" name="rs_feedburner_comments_url" value="'.htmlentities($rsfb_feedburner_settings['rs_feedburner_comments_url']).'" size="45" />
	</ol>
	<p><input type="submit" value="'.__( 'Save Changes' ).'" /></p></form>';
	?>
	<p>&nbsp;</p>
	<p>&nbsp;</p>

	<p><strong><a href="http://bit.ly/rs-feedburner-donate" target="_blank" rel="external" ><?php _e( 'Donate to RS FeedBurner', RSFB_PLUGIN_NAME ); ?></a></strong><br />
	<?php echo __( 'RS FeedBurner is provided for free.', RSFB_PLUGIN_NAME ) . ' ' . __( 'If you like the plugin, consider a donation to help further its development.', RSFB_PLUGIN_NAME ); ?></p>
	<p>&nbsp;</p>

	<p><strong><?php _e( 'Check out our other plugins:', RSFB_PLUGIN_NAME ); ?></strong></p>
	<p><?php _e( 'If you like RS FeedBurner, you might want to check out our other plugins:', RSFB_PLUGIN_NAME ); ?></p>
	<ul style="list-style-type:disc;padding-left:30px;">
		<li><a href="http://www.redsandmarketing.com/plugins/wp-spamshield/" target="_blank" rel="external" ><?php echo 'WP-SpamShield ' . __( 'Anti-Spam', RSFB_PLUGIN_NAME ); ?></a> <?php _e( 'An extremely powerful and user friendly WordPress anti-spam plugin that stops blog comment spam cold, including trackback and pingback spam. Includes spam-blocking contact form feature, and protection from user registration spam as well. WP-SpamShield is an all-in-one spam solution for WordPress. See what it\'s like to run a WordPress site without spam!', RSFB_PLUGIN_NAME ); ?></li>
		<li><a href="http://www.redsandmarketing.com/plugins/rs-head-cleaner/" target="_blank" rel="external" ><?php echo 'RS Head Cleaner Plus'; ?></a> <?php _e( 'This plugin cleans up a number of issues, doing the work of multiple plugins, improving speed, efficiency, security, SEO, and user experience. It removes junk code from the HEAD & HTTP headers, moves JavaScript from header to footer, combines/minifies/caches CSS & JavaScript files, hides the Generator/WordPress Version number, removes version numbers from CSS and JS links, and fixes the "Read more" link so it displays the entire post.', RSFB_PLUGIN_NAME ); ?></li>
		<li><a href="http://www.redsandmarketing.com/plugins/scrapebreaker/" target="_blank" rel="external" ><?php echo 'ScrapeBreaker'; ?></a> <?php _e( 'A combination of frame-breaker and scraper protection. Protect your website content from both frames and server-side scraping techniques.', RSFB_PLUGIN_NAME ); ?></li>
	</ul>
	<p>&nbsp;</p>
	
	<?php
	// Recommended Partners - BEGIN - Added in 1.4
	if ( rsfb_is_lang_en_us() ) {
	?>
	
	<div style='width:647px;border-style:solid;border-width:1px;border-color:#333333;background-color:#FEFEFE;padding:0px 15px 0px 15px;'><p><strong>Recommended Partners</strong></p>
	<p>Each of these products or services are ones that we highly recommend, based on our experience and the experience of our clients. We do receive a commission if you purchase one of these, but these are all products and services we were already recommending because we believe in them. By purchasing from these providers, you get quality and you help support the further development of RS FeedBurner.</p>
	</div>

	<div style="width:300px;height:300px;border-style:solid;border-width:1px;border-color:#333333;background-color:#FEFEFE;padding:0px 15px 0px 15px;margin-top:15px;margin-right:15px;float:left;clear:left;">
	<p><strong><a href="http://bit.ly/RSM_Hostgator" target="_blank" rel="external" >Hostgator Website Hosting</a></strong></p>
	<p><strong>Affordable, high quality web hosting. Great for WordPress and a variety of web applications.</strong></p>
	<p>Hostgator has variety of affordable plans, reliable service, and customer support. Even on shared hosting, you get fast servers that are well-configured. Hostgator provides great balance of value and quality, which is why we recommend them.</p>
	<p><a href="http://bit.ly/RSM_Hostgator"target="_blank" >Click here to find out more. >></a></p>
	</div>

	<div style="width:300px;height:300px;border-style:solid;border-width:1px;border-color:#333333;background-color:#FEFEFE;padding:0px 15px 0px 15px;margin-top:15px;margin-right:15px;float:left;">
	<p><strong><a href="http://bit.ly/RSM_Level10" target="_blank" rel="external" >Level10 Domains</a></strong></p>
	<p><strong>Inexpensive web domains with an easy to use admin dashboard.</strong></p>
	<p>Level10 Domains offers some of the best prices you'll find on web domain purchasing. The dashboard provides an easy way to manage your domains.</p>
	<p><a href="http://bit.ly/RSM_Level10" target="_blank" rel="external" >Click here to find out more. >></a></p>
	</div>

	<div style="width:300px;height:300px;border-style:solid;border-width:1px;border-color:#333333;background-color:#FEFEFE;padding:0px 15px 0px 15px;margin-top:15px;margin-right:15px;float:left;clear:left;">
	<p><strong><a href="http://bit.ly/RSM_Genesis" target="_blank" rel="external" >Genesis WordPress Framework</a></strong></p>
	<p><strong>Other themes and frameworks have nothing on Genesis. Optimized for site speed and SEO.</strong></p>
	<p>Simply put, the Genesis framework is one of the best ways to design and build a WordPress site. Built-in SEO and optimized for speed. Create just about any kind of design with child themes.</p>
	<p><a href="http://bit.ly/RSM_Genesis" target="_blank" rel="external" >Click here to find out more. >></a></p>
	</div>

	<div style="width:300px;height:300px;border-style:solid;border-width:1px;border-color:#333333;background-color:#FEFEFE;padding:0px 15px 0px 15px;margin-top:15px;margin-right:15px;float:left;">
	<p><strong><a href="http://bit.ly/RSM_AIOSEOP" target="_blank" rel="external" >All in One SEO Pack Pro</a></strong></p>
	<p><strong>The best way to manage the code-related SEO for your WordPress site.</strong></p>
	<p>Save time and effort optimizing the code of your WordPress site with All in One SEO Pack. One of the top rated, and most downloaded plugins on WordPress.org, this time-saving plugin is incredibly valuable. The pro version provides powerful features not available in the free version.</p>
	<p><a href="http://bit.ly/RSM_AIOSEOP" target="_blank" rel="external" >Click here to find out more. >></a></p>
	</div>

	<p style="clear:both;">&nbsp;</p>

	<?php
		}
	// Recommended Partners - END - Added in 1.4
	?>

	</div>
	<?php
	}
// Admin Functions - END

// PLUGIN - END
?>
