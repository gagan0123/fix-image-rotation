<?php

/**
 * @package Fix Image Rotation
 * @version 1.0
 */
/*
  Plugin Name: Fix Image Rotation
  Description: Fix Image Rotation plugin fixes image orientation based on EXIF data.  This is primarily a patch for mis-oriented images delivered from iPhones.  Functionally it filters all uploads and if EXIF->Orientation is set to a number greater than 1, then the image is resaved with a new orientation before the image is processed by wordpress.
  Author: Gagan Deep Singh
  Version: 1.0
  Author URI: http://gagan.pro
 */

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('FixImageRotation')) {

    class FixImageRotation {

        var $orientation_fixed = array();

        public function __construct() {
            add_filter('wp_handle_upload_prefilter', array($this, 'filter_wp_handle_upload_prefilter'), 10, 1);
            add_filter('wp_handle_upload', array($this, 'filter_wp_handle_upload'), 1, 3);
        }

        public function filter_wp_handle_upload($file) {
            $suffix = substr($file['file'], strrpos($file['file'], '.', -1) + 1);
            if (in_array($suffix, array('jpg', 'jpeg', 'png', 'gif'))) {
                $this->fixImageOrientation($file['file']);
            }
            return $file;
        }

        public function filter_wp_handle_upload_prefilter($file) {
            $suffix = substr($file['name'], strrpos($file['name'], '.', -1) + 1);
            if (in_array($suffix, array('jpg', 'jpeg', 'png', 'gif'))) {
                $this->fixImageOrientation($file['tmp_name']);
            }
            return $file;
        }

        public function fixImageOrientation($file) {
            if (is_callable('exif_read_data') && !isset($this->oreintation_fixed[$file])) {
                $exif = exif_read_data($file);
                if (isset($exif) && isset($exif['Orientation']) && $exif['Orientation'] > 1) {
                    include_once( ABSPATH . 'wp-admin/includes/image-edit.php' );
                    $rotator = false;
                    $flipper = false;
                    switch ($exif['Orientation']) {
                        case 1:
                            //We don't want to fix an already correct image :)
                            $this->orientation_fixed[$file] = true;
                            return;
                        case 2:
                            $flipper = array(false, true);
                            break;
                        case 3:
                            $orientation = -180;
                            $rotator = true;
                            break;
                        case 4:
                            $flipper = array(true, false);
                            break;
                        case 5:
                            $orientation = -90;
                            $rotator = true;
                            $flipper = array(false, true);
                            break;
                        case 6:
                            $orientation = -90;
                            $rotator = true;
                            break;
                        case 7:
                            $orientation = -270;
                            $rotator = true;
                            $flipper = array(false, true);
                            break;
                        case 8:
                        case 9:
                            $orientation = -270;
                            $rotator = true;
                            break;
                        default:
                            $orientation = 0;
                            $rotator = true;
                            break;
                    }

                    $editor = wp_get_image_editor($file);
                    if (!is_wp_error($editor)) {
                        if ($rotator === true) {
                            $editor->rotate($orientation);
                        }
                        if ($flipper !== false) {
                            $editor->flip($flipper[0], $flipper[1]);
                        }
                        $editor->save($file);
                    }
                } // end if $exif
            } // end is_callable('exif_read_data')
            $this->orientation_fixed[$file] = true;
        }

    }

    new FixImageRotation();
}