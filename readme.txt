=== SF Author Url Control ===
Contributors: GregLone
Tags: author, custom, customize, permalink, slug, url, author base, author slug, nicename, user, users
Requires at least: 3.0
Tested up to: 3.7
Stable tag: trunk
License: GPLv3
License URI: http://www.screenfeed.fr/gpl-v3.txt

Allows administrators or capable users to change the users profile url.

== Description ==
With this plugin, administrators can change the default author base in the registered users profile url, and the author slug of each user.
Changing an author slug is a good thing for security (if your login is "This Is Me", your slug will be "this-is-me", a bit easy to guess).
The plugin adds 2 fields for this purpose, one in permalinks settings, the other in a user profile.

* Default: *www.my-site.com/author_base/author_nicename/*
* Customized: *www.my-site.com/jedi/obiwan/*

= How to edit the slugs =
* Go to *Settings* > *Permalinks* to edit the author base: "author_base" => "jedi"
* Go to *Users* > *"Any user edit page"* to edit the user slug: "author_nicename" => "obiwan"

= Translations =
* English
* French
* German

= Multisite =
* The plugin is ready for Multisite.


== Installation ==

1. Extract the plugin folder from the downloaded ZIP file.
2. Upload sf-author-url-control folder to your *"/wp-content/plugins/"* directory.
3. Activate the plugin from the "Plugins" page.
4. Go to *Settings* > *Permalinks* to edit the author base.
5. Go to *Users* > *Any user edit page* to edit the user slug (nicename).


== Frequently Asked Questions ==

= Why the fields don't display? =
You probably don't have the edit_users capability (for user slug) and manage_options capability (for author base).

Eventually, check out [my blog](http://www.screenfeed.fr/sfauc/) for more infos or tips (sorry guys, it's in french).

= Will I keep the customized links if I uninstall the plugin? =
Not exactly. The author base won't be customized anymore, but each user will keep his customized author slug.
To retrieve the initial author slugs, empty the field on each user edit page (only the customized ones of course).


== Screenshots ==
1. The permalinks settings page
2. The user edit page


== Changelog ==

= 1.0.5 =
* 2013/09/21
* Bugfix: the author base couldn't be changed in WP 3.7-alpha

= 1.0.4 =
* 2013/09/13
* Small security fix.

= 1.0.3 =
* 2013/09/12
* Added German translation. Thanks Carny88.

= 1.0.2 =
* 2013/01/27
* Bug fix in permalinks page
* In the user profile page, add a link to the public author page
* Small code improvements

= 1.0.1 =
* 2012/04/16
* Minor bug fix

= 1.0 =
* 2012/04/16
* First public release

== Upgrade Notice ==

= 1.0.5 =
If you run WP 3.7-alpha, you won't be able to change the author base without this version.