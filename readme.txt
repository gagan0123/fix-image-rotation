=== Fix Image Rotation ===
Contributors: gagan0123
Tags: Image Rotation, iPhone
Requires at least: 3.5
Tested up to: 4.8.1
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fixes the rotation of the images based on EXIF data

== Description ==
Fix Image Rotation plugin fixes image orientation based on EXIF data. Fixes the mis-oriented images clicked via mobile phones. 

Functionally it filters all uploads and if EXIF->Orientation is set to a number greater than 1, then the image is re-saved with a new orientation before the image is processed by WordPress.

= Contribute =
To contribute to the plugin fork the [GitHub Repo](https://github.com/gagan0123/fix-image-rotation), make changes and send pull requests.

== Installation ==

1. Add the plugin's directory in the WordPress' plugin directory.
1. Activate the plugin.
1. Enjoy your cup of coffee while the plugin takes care of the images.

== Changelog ==

= 2.0 =
* Fix for PNG files being sent for orientation correction while PNG files don't even have EXIF data.
* Fix for Restoration of meta data when GD Library is being used.
* Testing with WordPress 4.8.1 and fixing some related issues.

= 1.0 =
* Initial Release