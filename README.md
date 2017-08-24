# Fix Image Rotation #
**Contributors:** [gagan0123](https://profiles.wordpress.org/gagan0123)  
**Tags:** Image Rotation, iPhone  
**Requires at least:** 3.7  
**Tested up to:** 4.8.1  
**Stable tag:** 2.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Fixes the rotation of the images based on EXIF data

## Description ##
Fix Image Rotation plugin fixes image orientation based on EXIF data. Fixes the mis-oriented images clicked via mobile phones. 

Functionally it filters all uploads and if EXIF->Orientation is set to a number greater than 1, then the image is re-saved with a new orientation before the image is processed by WordPress.

### Contribute ###
To contribute to the plugin fork the [GitHub Repo](https://github.com/gagan0123/fix-image-rotation), make changes and send pull requests.

### Icon Attribution ###
Icons made by [Picol](https://www.flaticon.com/authors/picol) is licensed by [CC 3.0 BY](http://creativecommons.org/licenses/by/3.0/)

## Installation ##

1. Add the plugin's directory in the WordPress' plugin directory.
1. Activate the plugin.
1. Enjoy your cup of coffee while the plugin takes care of the images.

## Frequently Asked Questions ##

### How it works? ###
When an image is clicked by a camera or a phone, it stores some additional information about the image. One such information is Orientation. This plugin makes use of the Orientation value stored by the camera/phone and rotates or flips the image based on that.

### Where's the settings page? ###
This plugin works out of the box and does not require any settings.

### Can I contribute to the plugin? ###
Yes you can. As mentioned in the description, just fork the [GitHub Repo](https://github.com/gagan0123/fix-image-rotation), make changes and send pull requests.
You can even contribute by adding banner images and logos for the plugin. If you are familiar with GitHub, then fork the above repo and add the images in a folder named assets, and send pull request; else you can submit URLs to the images as support request.


## Changelog ##

### 2.0 ###
* Fix for PNG files being sent for orientation correction while PNG files don't even have EXIF data.
* Fix for Restoration of meta data when GD Library is being used.
* Testing with WordPress 4.8.1 and fixing some related issues.

### 1.0 ###
* Initial Release