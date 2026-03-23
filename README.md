<img src='https://github.com/gagan0123/fix-image-rotation/raw/master/assets/icon-128x128.png' align='right' />

# Fix Image Rotation #
**Contributors:** [gagan0123](https://profiles.wordpress.org/gagan0123/), [shashwatmittal](https://profiles.wordpress.org/shashwatmittal/), [markjaquith](https://profiles.wordpress.org/markjaquith/), [bgrande](https://profiles.wordpress.org/bgrande/)  
**Donate Link:** https://PayPal.me/gagan0123  
**Tags:** image rotation, exif, orientation, iPhone, upload  
**Requires at least:** 6.2  
**Requires PHP:** 7.4  
**Tested up to:** 6.9  
**Stable tag:** 2.2.2  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Fixes the rotation of the images based on EXIF data

## Description ##

Fix Image Rotation automatically corrects image orientation based on EXIF data when images are uploaded to your WordPress site.

Mobile phone cameras (especially iPhones) often save photos with an EXIF Orientation flag instead of physically rotating the pixel data. This can result in images appearing sideways or upside-down after uploading to WordPress.

This plugin intercepts the upload process and physically rotates or flips the image to match its intended orientation before WordPress generates thumbnails and stores the file.

**Why is this plugin still needed?**

Although WordPress 5.3 added built-in image rotation handling, it does not cover all edge cases. This plugin provides more robust handling of all 8 EXIF orientation values, works with both GD Library and Imagick, and preserves image metadata that GD Library would otherwise strip during rotation.

### Supported Image Formats ###

* JPEG / JPG
* TIFF

PNG, GIF, and other formats are not processed because they do not contain EXIF orientation data.

### Requirements ###

* PHP EXIF extension must be enabled on your server
* Either the GD Library or Imagick PHP extension for image manipulation

### Special Thanks to ###
[Shashwat Mittal](https://profiles.wordpress.org/shashwatmittal/) for meta data restoration of rotated images.
[Mark Jaquith](https://profiles.wordpress.org/markjaquith/) for making the fix image rotation class more useful.
[@tealborder](https://github.com/tealborder) for adding required library notice in plugins menu.
[@broberson](https://github.com/broberson) for finding and fixing an issue with the plugin.
[Benedikt](https://profiles.wordpress.org/bgrande/) for multiple contributions towards betterment of the plugin.

### Contribute ###
To contribute to the plugin fork the [GitHub Repo](https://github.com/gagan0123/fix-image-rotation), make changes and send pull requests.

### Icon Attribution ###
Icons made by [Picol](https://www.flaticon.com/authors/picol) is licensed by [CC 3.0 BY](http://creativecommons.org/licenses/by/3.0/)

## Installation ##

1. Go to **Plugins > Add New** in your WordPress admin.
1. Search for "Fix Image Rotation".
1. Click **Install Now** and then **Activate**.
1. That's it! The plugin works automatically — no settings needed.

Alternatively, upload the `fix-image-rotation` folder to `wp-content/plugins/` and activate from the Plugins page.

## Frequently Asked Questions ##

### How does it work? ###
When a photo is taken by a camera or phone, EXIF metadata records the device orientation. This plugin reads the EXIF Orientation value during upload and physically rotates or flips the image to match, so it displays correctly everywhere.

### Where's the settings page? ###
This plugin works out of the box and does not require any settings.

### I see an admin notice about EXIF not being available ###
Your server's PHP installation needs the EXIF extension enabled. Contact your hosting provider to enable it.

### Can I contribute to the plugin? ###
Yes! Fork the [GitHub Repo](https://github.com/gagan0123/fix-image-rotation), make changes and send pull requests.

## Screenshots ##
### 1. Adding images of different orientations without this plugin. ###
![Adding images of different orientations without this plugin.](https://github.com/gagan0123/fix-image-rotation/raw/master/assets/screenshot-1.png)

### 2. Adding images of different orientations with this plugin. ###
![Adding images of different orientations with this plugin.](https://github.com/gagan0123/fix-image-rotation/raw/master/assets/screenshot-2.png)


## Changelog ##

### 2.2.2 ###
* Testing with WordPress 5.6.
* Updates to readme and let WordPress.org know that the plugin is tested with WordPress 5.6

### 2.2.1 ###
* Remove extra rows below plugin details.
* Add admin notice if exif extension not loaded or exif_read_data function does not exist or is disabled.

### 2.2 ###
* Adds "PHP EXIF MODULE LOADED" and "EXIF_READ_DATA CALLABLE" below plugin details.
* Prevent undefined function call to wp_read_image_metadata in rare cases.
* Handle file endings with uppercase as well.
* Some performance patches.
* WordPress Coding Standards compatibility.

### 2.1.1 ###
* Moved hooks registrations outside constructor.

### 2.1 ###
* Updates correct orientation of fixed images in WordPress metadata of the image.

### 2.0 ###
* Fix for PNG files being sent for orientation correction while PNG files don't even have EXIF data.
* Fix for Restoration of meta data when GD Library is being used.
* Testing with WordPress 4.8.1 and fixing some related issues.

### 1.0 ###
* Initial Release
