=== CSS Addons ===
Contributors: bastho
Tags: css, style, theme, customization, editor
Requires at least: 3.1
Tested up to: 4.3.1
Donate link: http://ba.stienho.fr/#don
Stable tag: /trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Description: Lets administrator add CSS addons to any theme

== Description ==

This plugin is for you if :

* You know CSS
* Dont have legitimity to apply it or manage a multisite network
* You work with webmasters wich want to customize WP but think that CSS means Custom Super Style

This plugin works as well for standard single site or multisite network.

The power user can create a set of CSS rules readable for humans with :

* slug
* name
* descrition
* CSS code

It adds the list of addons in the customize view (under appearance) and webmaster just have to check the ones they want.
That's it.
For coder webmasters, a free field let them put there own CSS in a code-highlighted editor.


== Installation ==

1. Upload `css-addons` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin


== Screenshots ==

1. Settings page
2. Customizer view
3. New editor (since 1.5)

== Changelog ==

= 1.5.1 = 
* Bug fix in thickbox
* Added .pot file

= 1.5.0 = 
* Add CSS highligthing in editor
* Add customizer preview support

= 1.4.0 = 
* Remove favicon feature since it's natively supported by WP 4.3+
* Update french localization

= 1.3.3 = 
* WP 4.3 compliant

= 1.3.2 = 
* Fix: Remove some PHP warnings

= 1.3.1 = 
* Fix: Later load of script

= 1.3.0 =
* Add: Librairies, started with bootsrap

= 1.2.1 =
* Fix: Correct URL when domain mapping is active

= 1.2.0 =
* add: Custom favicon support
* fix: Improve tests before loading or not admin scripts

= 1.1.0 =
* add: alphabetical sort of addons
* add: delete button in addons list
* fix: remove slashes when saving addons
* fix: stylesheet was not loaded if at least one of addons or custom CSS was empty

= 1.0.0 =
* Initial release

== Upgrade notice ==

= 1.4.0 =
The favicon feature is removed, WordPress now has it's own.

== Languages ==

* en	: 100%
* fr_FR : 100%
