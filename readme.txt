=== Fix Image Rotation ===
Contributors: gagan0123, shashwatmittal, markjaquith, bgrande
Tags: Image Rotation, iPhone
Requires at least: 3.7
Requires PHP: 5.6
Tested up to: 5.1
Stable tag: 2.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Fixes the rotation of the images based on EXIF data

== Description ==
Fix Image Rotation plugin fixes image orientation based on EXIF data. Fixes the mis-oriented images clicked via mobile phones. 

Functionally it filters all uploads and if EXIF->Orientation is set to a number greater than 1, then the image is re-saved with a new orientation before the image is processed by WordPress.

= Special Thanks to =
[Shashwat Mittal](https://profiles.wordpress.org/shashwatmittal/) for meta data restoration of rotated images.
[Mark Jaquith](https://profiles.wordpress.org/markjaquith/) for making the fix image rotation class more useful.
[@tealborder](https://github.com/tealborder) for adding required library notice in plugins menu.
[@broberson](https://github.com/broberson) for finding and fixing and issue with the plugin.
[Benedikt](https://profiles.wordpress.org/bgrande/) for multiple contributions towards betterment of the plugin.


= Contribute =
To contribute to the plugin fork the [GitHub Repo](https://github.com/gagan0123/fix-image-rotation), make changes and send pull requests.

= Icon Attribution =
Icons made by [Picol](https://www.flaticon.com/authors/picol) is licensed by [CC 3.0 BY](http://creativecommons.org/licenses/by/3.0/)

== Installation ==

1. Add the plugin's directory in the WordPress' plugin directory.
1. Activate the plugin.
1. Enjoy your cup of coffee while the plugin takes care of the images.

== Frequently Asked Questions ==

= How it works? = 
When an image is clicked by a camera or a phone, it stores some additional information about the image. One such information is Orientation. This plugin makes use of the Orientation value stored by the camera/phone and rotates or flips the image based on that.

= Where's the settings page? =
This plugin works out of the box and does not require any settings.

= Can I contribute to the plugin? =
Yes you can. As mentioned in the description, just fork the [GitHub Repo](https://github.com/gagan0123/fix-image-rotation), make changes and send pull requests.
You can even contribute by adding banner images and logos for the plugin. If you are familiar with GitHub, then fork the above repo and add the images in a folder named assets, and send pull request; else you can submit URLs to the images as support request.


== Screenshots ==
1. Adding images of different orientations without this plugin.
2. Adding images of different orientations with this plugin.

== Changelog ==

= 2.2.1 =
* Remove extra rows below plugin details.
* Add admin notice if exif extension not loaded or exif_read_data function does not exist or is disabled.

= 2.2 =
* Adds "PHP EXIF MODULE LOADED" and "EXIF_READ_DATA CALLABLE" below plugin details.
* Prevent undefined function call to wp_read_image_metadata in rare cases.
* Handle file endings with uppercase as well.
* Some performance patches.
* WordPress Coding Standards compatibility.

= 2.1.1 =
* Moved hooks registrations outside constructor.

= 2.1 =
* Updates correct orientation of fixed images in WordPress metadata of the image.

= 2.0 =
* Fix for PNG files being sent for orientation correction while PNG files don't even have EXIF data.
* Fix for Restoration of meta data when GD Library is being used.
* Testing with WordPress 4.8.1 and fixing some related issues.

= 1.0 =
* Initial Release