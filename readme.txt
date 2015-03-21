=== RS FeedBurner ===
Contributors: RedSand
Donate link: http://www.redsandmarketing.com/rs-feedburner-donate/
Tags: feedburner, feedpress, feed, feeds, feedsmith, redirect, redirects, rss, seo, subscribe, subscribers, subscription, feedburner alternative, comments
Requires at least: 3.7
Tested up to: 4.1
Stable tag: trunk

This plugin detects native WordPress feeds and redirects them to your FeedBurner or FeedPress feed so you can track your subscribers.

== Description == 

This plugin redirects all requests for your native WordPress feeds to your FeedBurner or FeedPress feeds so you can track all your subscribers and maximize your blog/site readership and user engagement.

You can redirect both your main WordPress feed and your comments feed if you like. (Comments feed is optional since not everyone uses it.)

This plugin is a fork of the original FeedBurner Feedsmith plugin by Steve Smith. Since it was discontinued, I picked up where it left off so we all can have an actively updated plugin.

As of version 1.4.6, this plugin supports FeedPress, the main FeedBurner alternative.

= Documentation / Tech Support =
* Documentation: [Plugin Homepage](http://www.redsandmarketing.com/plugins/rs-feedburner/)
* Tech Support: [WordPress Plugin Support](http://www.redsandmarketing.com/plugins/wordpress-plugin-support/)

Features:

* Simple to use
* Fast
* Compatible

One of the **easiest** ways to implement your FeedBurner or FeedPress feed on your WordPress site. *It just works.*

Important: If you are using a caching plugin, you will need to set it to *not* cache your *feeds*. (Cache plugins usually have an "exclude" setting so you can specify which pages won't be cached.)

= More Info / Documentation =
For more info and full documentation, visit the [RS FeedBurner homepage](http://www.redsandmarketing.com/plugins/rs-feedburner/ "RS FeedBurner homepage").

== Installation ==

= Installation Instructions =

**Option 1:** Install the plugin directly through the WordPress Admin Dashboard (Recommended)

1. Go to *Plugins* -> *Add New*.

2. Type *RS FeedBurner* into the Search box, and click *Search Plugins*.

3. When the results are displayed, click *Install Now*.

4. When it says the plugin has successfully installed, click **Activate Plugin** to activate the plugin (or you can do this on the Plugins page).

**Option 2:** Install .zip file through WordPress Admin Dashboard

1. Go to *Plugins* -> *Add New* -> *Upload*.

2. Click *Choose File* and find `rs-feedburner.zip` on your computer's hard drive.

3. Click *Install Now*.

4. Click **Activate Plugin** to activate the plugin (or you can do this on the Plugins page).

**Option 3:** Install .zip file through an FTP Client (Recommended for Advanced Users Only)

1. After downloading, unzip file and use an FTP client to upload the enclosed `rs-feedburner` directory to your WordPress plugins directory (usually `/wp-content/plugins/`) on your web server.

2. Go to your Plugins page in the WordPress Admin Dashboard, and find this plugin in the list.

3. Click **Activate** to activate the plugin.

= Next Steps After Installation = 

1. Go to the options page and enter the URL of your FeedBurner feed, and click *Save Settings*.

2. You can optionally enter your comments feed url on FeedBurner if you have one setup.

You are good to go...it's that easy.

== Frequently Asked Questions ==

= What options do I need to fill in? =

After you sign up for a FeedBurner or FeedPress account, just fill in your new FeedBurner or FeedPress feed address, and click *Save Settings*. That's all! After that it will work.

Optional: If you have a FeedBurner or FeedPress feed setup for your comments, you can enter that as well, and your comment feed will automatically be redirected too.

= You do great work...can I hire you? =

Absolutely...go to my [WordPress Consulting](http://www.redsandmarketing.com/web-design/wordpress-consulting/ "WordPress Consulting") page for more information.

== Changelog ==

= 1.4.6 =
*released 03/21/15*

* Added support for FeedPress, a FeedBurner alternative.
* Made various code improvements.

= 1.4.5 =
*released 03/06/15*

* Fixed a minor bug.
* Made various minor code improvements.

= 1.4.4 =
*released 03/02/15*

* Fixed a minor bug.
* Made various minor code improvements.

= 1.4.3 =
*released 02/21/15*

* Added an uninstall function that completely uninstalls the plugin and removes all options, data, and traces of its existence when it is deleted through the dashboard.
* Added validation check for feed URLs on the settings page and in the redirect, to make sure user enters a valid URL for their FeedBurner feed(s).
* Made various minor code improvements.

= 1.4.2 =
*released 01/29/15*

* Fixed a bug.
* Made various minor code improvements.

= 1.4 =
*released 01/19/15*

* Increased minimum required WordPress version to 3.7.
* Updated .pot file.
* Added recommended partners and donate link on settings page.

= 1.3 =
*released 12/18/14*

* Prepared the plugin for internationalization and localization, and created .pot file for translation.
* Increased minimum required WordPress version to 3.6.

= 1.2 =
*released 09/13/14*

* Made various minor code improvements.
* Slight performance improvement when used with other RS plugins.
* Increased minimum required WordPress version to 3.2.

= 1.1 =
*released 07/11/14*

* Added additional security checks.
* Added a "Settings" link in the plugin action links on the Plugins page. (Next to "Activate"/"Deactivate".)
* Slight performance improvement.
* Fixed a couple bugs.
* Cleaned up some code.

= 1.0.0.4 =
*released 04/28/14*

* Fixed a bug that caused an error message on certain server configurations.

= 1.0.0.3 =
*released 04/13/14*

* Added additional security checks.

= 1.0.0.2 =
*released 04/04/14*

* Minor code improvements.

= 1.0 =
*released 03/09/14*

* Initial release.

This plugin is a fork of the original FeedBurner Feedsmith plugin by Steve Smith. Since it was discontinued, I picked up where it left off so we all can have an actively updated plugin.

== Upgrade Notice ==
= 1.4.6 =
Added support for FeedPress, a FeedBurner alternative, and made various code improvements. Please see Changelog for details.
