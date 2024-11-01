=== Plugin Name ===
Contributors: hephaistosthemaker
Donate link: 
Tags: trakt.tv, autopost, movie
Requires at least: 3.8
Tested up to: 4.0
Stable tag: 1.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html


WP Autopost Trakt.tv Activity plugin does retrive your trakt.tv activity (scrobble and checkin) data and create custom post for each activity.



== Description ==

WP Autopost Trakt.tv Activity plugin does retrive your trakt.tv activity (scrobble and checkin) data and create post for each activity.

For movies, trakt.tv api can send different activity: scrobble, checkin, watching, seen, rating, watchlist, shout, review, created list, item_added in list

This plugin only take into account the following activity: scrobble, checkin

If you want support for other types of activity, you can contact the development team and explain your usecase.


== Installation ==



1. Upload `wp-autopost-trakttv-activity` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to settings, personalize the different fields
1. the script will run automatically twice a day and be limited to 10 movies per run

== Frequently Asked Questions ==



== Screenshots ==
1. WP Autopost Trakt.tv Activity plugin can automatically create custom blog entry from your trakt.tv data
2. It is compatible with featured pictures in order to create beautiful visuals on your blog
3. The compatibility with the featured pictures allow generation of grid or slider automatically if it is supported by your theme
4. The settings page allow you to personnalise a lot of parameters (trakt.tv account credentials, post options (category, tag) and featuring option (featuring tag, number of featured posts))
5. You can also customize the blog entry content through html and shortcodes to use trakt.tv data the way you want


== Changelog ==
= 1.1 =
* Bug correction with author selection
* New strategy in order to bypass the worpress sanitizer wich remove part of the posted code when the script run and the current user is not set


== Upgrade Notice ==


