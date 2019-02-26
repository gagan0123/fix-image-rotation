<?php
/**
 * Plugin Name: Fix Image Rotation
 * Plugin URI: https://wordpress.org/plugins/fix-image-rotation/
 * Description: Fix Image Rotation plugin fixes image orientation based on EXIF data.  This is primarily a patch for mis-oriented images delivered from iPhones.  Functionally it filters all uploads and if EXIF->Orientation is set to a number greater than 1, then the image is resaved with a new orientation before the image is processed by WordPress.
 * Author: Gagan Deep Singh
 * Version: 2.2.1
 * Author URI: http://gagan.pro
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fix-image-rotation
 * Domain Path: /languages
 *
 * @package Fix_Image_Rotation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'GS_FIR_PATH' ) ) {
	define( 'GS_FIR_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

require_once GS_FIR_PATH . 'includes/class-fix-image-rotation.php';

Fix_Image_Rotation::get_instance()->register_hooks();
