=== Auto Load Next Post Beta Tester ===
Author URI: http://sebastiendumont.com/
Plugin URI: https://github.com/seb86/Auto-Load-Next-Post-Beta-Tester
Contributors: sebd86
Tags: auto load next post, alnp, beta, beta tester, bleeding edge
Requires at least: 4.2
Tested up to: 4.4.1
Stable Tag: 1.0.0

Run bleeding edge versions of Auto Load Next Post from Github. This will replace your installed version of Auto Load Next Post with the latest tagged release on Github - use with caution, and not on production sites. You have been warned.

== Description ==

**This plugin is meant for testing and development purposes only. You should under no circumstances run this on a production website.**

Easily run the latest tagged version of [Auto Load Next Post](http://wordpress.org/plugins/auto-load-next-post/) right from GitHub, including beta versions.

Just like with any plugin, this will not check for updates on every admin page load unless you explicitly tell it to. You can do this by clicking the "Check Again" button from the WordPress updates screen or you can set the `ALNP_BETA_TESTER_FORCE_UPDATE` to true in your `wp-config.php` file.

Based on WP_GitHub_Updater by Joachim Kudish and code by Patrick Garman.

Forked from the WooCommerce Beta Tester by Mike Jolley and Claudio Sanches.

== Changelog ==

= 1.0.1 =
* Switched to releases API to get latest release, rather than tag which are not chronological.

= 1.0.0 =
* First release.
