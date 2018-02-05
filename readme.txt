=== Auto Load Next Post: Beta Tester ===
Author URI: http://autoloadnextpost.com/
Plugin URI: https://github.com/AutoLoadNextPost/alnp-beta-tester
Contributors: autoloadnextpost, sebd86
Tags: Auto Load Next Post, beta tester, bleeding edge, prerelease
Requires PHP: 5.6+
Requires at least: 4.5
Tested up to: 4.9.2
Stable Tag: 2.0.1

Run bleeding edge versions of Auto Load Next Post from Github. This will replace your installed version of Auto Load Next Post with the latest tagged prerelease on GitHub - use with caution, and not on production sites. You have been warned.

== Description ==

**This plugin is meant for testing and development purposes only. You should under no circumstances run this on a production website.**

Easily run the latest tagged prerelease version of [Auto Load Next Post](https://wordpress.org/plugins/auto-load-next-post/) right from GitHub.

Just like with any plugin, this will not check for updates on every admin page load unless you explicitly tell it to. You can do this by clicking the "Check Again" button from the WordPress updates screen or you can set the `ALNP_BETA_TESTER_FORCE_UPDATE` to true in your `wp-config.php` file.

Based on WP_GitHub_Updater by Joachim Kudish and code by Patrick Garman.

Forked from the WooCommerce Beta Tester by Mike Jolley and Claudio Sanches.

== Changelog ==

= 2.0.1 =
* Minor correction with identifying new prerelease version.
* Added get changelog from the latest prerelease for plugin information.

= 2.0.0 =
* Updated to point to the updated repository location.
* Improved code base.

= 1.0.1 =
* Switched to releases API to get latest release, rather than tag which are not chronological.

= 1.0.0 =
* First release.
