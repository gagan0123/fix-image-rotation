<?php
/**
 * Contains class to handle interactions with WordPress.
 *
 * @package Fix_Image_Rotation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Fix_Image_Rotation' ) ) {

	/**
	 * Handles most of the interaction of the plugin with WordPress
	 *
	 * @since 1.0
	 */
	class Fix_Image_Rotation {

		/**
		 * Array storing the file names that were processed, as keys.
		 *
		 * @since 1.0
		 *
		 * @access private
		 *
		 * @var array
		 */
		private $orientation_fixed;

		/**
		 * Array storing the meta data of original files in case it
		 * needs to be restored later.
		 *
		 * @since 1.0
		 *
		 * @access private
		 *
		 * @var array
		 */
		private $previous_meta;

		/**
		 * The instance of the class Fix_Image_Rotation
		 *
		 * @since 2.0
		 *
		 * @access protected
		 *
		 * @var Fix_Image_Rotation
		 */
		protected static $instance = null;

		/**
		 * Constructs the plugin object and initializes its variables.
		 *
		 * @since 1.0
		 */
		public function __construct() {
			$this->orientation_fixed = array();
			$this->previous_meta     = array();
		}

		/**
		 * Returns the current instance of the class, in case some other
		 * plugin needs to use its public methods.
		 *
		 * @since 2.0
		 *
		 * @access public
		 *
		 * @return Fix_Image_Rotation Returns the current instance of the class
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Registers the filters required for this plugin
		 *
		 * @since 2.0
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function register_hooks() {
			add_filter( 'wp_handle_upload_prefilter', array( $this, 'filter_wp_handle_upload_prefilter' ), 10, 1 );
			add_filter( 'wp_handle_upload', array( $this, 'filter_wp_handle_upload' ), 1, 3 );
			add_action( 'after_plugin_row', array( $this, 'action_after_plugin_row' ), 1, 3 );
		}

		/**
		 * Displays status for php-mod exif if enabled or not on server
		 * This status is displayed in admin area of WP - Plugins List
		 *
		 * @since 2.2
		 *
		 * @access public
		 *
		 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
		 *
		 * @param array  $plugin_data An array of plugin data.
		 *
		 * @param string $status Status of the plugin.
		 *
		 * @return void
		 */
		public function action_after_plugin_row( $plugin_file, $plugin_data, $status ) {

			// exit early if this row does not belong to this plugin.
			if ( ! isset( $plugin_data['slug'] ) || stristr( plugin_basename( __FILE__ ), DIRECTORY_SEPARATOR, true ) !== $plugin_data['slug'] ) {
				return;
			}

			$php_extension = extension_loaded( 'exif' ) ? __( 'php exif module loaded', 'fix-image-rotation' ) : __( 'no php exif module', 'fix-image-rotation' );
			$exif_callable = is_callable( 'exif_read_data' ) ? __( 'exif_read_data callable', 'fix-image-rotation' ) : __( 'exif_read_data not callable', 'fix-image-rotation' );

			printf(
				'<style>
				.exif-status-inline {
					color: #FFF;
					font-size: 0.9em;
					text-transform: uppercase;
					background-color: #444;
					padding: 1px 6px;
					border-radius: 3px;
					cursor: default;
				}
				</style>
				<tr>
					<th>&nbsp;</td>
					<td>&nbsp;</td>
					<td>
						<span class="exif-status-inline">%s</span> <span class="exif-status-inline">%s</span>
					</td>
				</tr>',
				esc_html( $php_extension ),
				esc_html( $exif_callable )
			);
		}

		/**
		 * Checks the filename before it is uploaded to WordPress and
		 * runs the fix_image_orientation function in case its needed.
		 *
		 * @since 1.0
		 *
		 * @access public
		 *
		 * @hook wp_handle_upload
		 *
		 * @param array $file {
		 *    Array of upload data.
		 *
		 *     @type string $file Filename of the newly-uploaded file.
		 *     @type string $url  URL of the uploaded file.
		 *     @type string $type File type.
		 * }
		 *
		 * @return array Array of upload data.
		 */
		public function filter_wp_handle_upload( $file ) {
			$suffix = substr( $file['file'], strrpos( $file['file'], '.', -1 ) + 1 );
			if ( in_array( strtolower( $suffix ), array( 'jpg', 'jpeg', 'tiff' ), true ) ) {
				$this->fix_image_orientation( $file['file'] );
			}
			return $file;
		}

		/**
		 * Checks the filename before it is uploaded to WordPress and
		 * runs the fix_image_orientation function in case its needed.
		 *
		 * @since 1.0
		 *
		 * @access public
		 *
		 * @hook wp_handle_upload_prefilter
		 *
		 * @param array $file An array of data for a single file.
		 *
		 * @return array An array of data for a single file.
		 */
		public function filter_wp_handle_upload_prefilter( $file ) {
			$suffix = substr( $file['name'], strrpos( $file['name'], '.', -1 ) + 1 );
			if ( in_array( strtolower( $suffix ), array( 'jpg', 'jpeg', 'tiff' ), true ) ) {
				$this->fix_image_orientation( $file['tmp_name'] );
			}
			return $file;
		}

		/**
		 * Fixes the orientation of the image based on exif data
		 *
		 * @since 1.0
		 *
		 * @access public
		 *
		 * @param string $file Path of the file.
		 *
		 * @return void
		 */
		public function fix_image_orientation( $file ) {
			if ( ! isset( $this->orientation_fixed[ $file ] ) && is_callable( 'exif_read_data' ) ) {
				$exif = exif_read_data( $file );

				if ( isset( $exif ) && isset( $exif['Orientation'] ) && $exif['Orientation'] > 1 ) {

					// Need it so that image editors are available to us.
					include_once ABSPATH . 'wp-admin/includes/image-edit.php';

					// Calculate the operations we need to perform on the image.
					$operations = $this->calculate_flip_and_rotate( $file, $exif );

					if ( false !== $operations ) {
						// Lets flip flop and rotate the image as needed.
						$this->do_flip_and_rotate( $file, $operations );
					}
				}
			}
		}

		/**
		 * Calculate the flips and rotations image will need to do to fix its orientation.
		 *
		 * @since 2.1
		 *
		 * @access private
		 *
		 * @param string $file Path of the file.
		 *
		 * @param array  $exif Exif data of the image.
		 *
		 * @return array|bool Array of operations to be performed on the image,
		 *                    false if no operations are needed.
		 */
		private function calculate_flip_and_rotate( $file, $exif ) {

			$rotator     = false;
			$flipper     = false;
			$orientation = 0;

			// Lets switch to the orientation defined in the exif data.
			switch ( $exif['Orientation'] ) {
				case 1:
					// We don't want to fix an already correct image :).
					$this->orientation_fixed[ $file ] = true;
					return false;
				case 2:
					$flipper = array( false, true );
					break;
				case 3:
					$orientation = -180;
					$rotator     = true;
					break;
				case 4:
					$flipper = array( true, false );
					break;
				case 5:
					$orientation = -90;
					$rotator     = true;
					$flipper     = array( false, true );
					break;
				case 6:
					$orientation = -90;
					$rotator     = true;
					break;
				case 7:
					$orientation = -270;
					$rotator     = true;
					$flipper     = array( false, true );
					break;
				case 8:
				case 9:
					$orientation = -270;
					$rotator     = true;
					break;
				default:
					$orientation = 0;
					$rotator     = true;
					break;
			}

			return compact( 'orientation', 'rotator', 'flipper' );
		}

		/**
		 * Flips and rotates the image based on the parameters provided.
		 *
		 * @since 2.1
		 *
		 * @access private
		 *
		 * @param string $file Path of the file.
		 *
		 * @param array  $operations {
		 *      Array of operations to be performed on the image.
		 *
		 *      @type bool       $rotator Whether to rotate the image or not.
		 *      @type int        $orientation Amount of rotation to be performed in degrees.
		 *      @type array|bool $flipper {
		 *          Whether to flip the image or not, false if no flipping needed.
		 *
		 *          @type bool $0 Flip along Horizontal Axis.
		 *          @type bool $1 Flip along Vertical Axis.
		 *      }
		 * }
		 *
		 * @return bool Returns true if operations were successful, false otherwise.
		 */
		private function do_flip_and_rotate( $file, $operations ) {

			$editor = wp_get_image_editor( $file );

			// If GD Library is being used, then we need to store metadata to restore later.
			if ( 'WP_Image_Editor_GD' === get_class( $editor ) ) {
				include_once ABSPATH . 'wp-admin/includes/image.php';
				$this->previous_meta[ $file ] = wp_read_image_metadata( $file );
			}

			if ( ! is_wp_error( $editor ) ) {
				// Lets rotate and flip the image based on exif orientation.
				if ( true === $operations['rotator'] ) {
					$editor->rotate( $operations['orientation'] );
				}
				if ( false !== $operations['flipper'] ) {
					$editor->flip( $operations['flipper'][0], $operations['flipper'][1] );
				}
				$editor->save( $file );
				$this->orientation_fixed[ $file ] = true;
				add_filter( 'wp_read_image_metadata', array( $this, 'restore_meta_data' ), 10, 2 );
				return true;
			}
			return false;
		}

		/**
		 * Restores the meta data of the image after being processed.
		 *
		 * WordPress' Imagick Library does not need this, but GD library
		 * removes metadata from the image upon rotation or flip so this
		 * method restores those values.
		 *
		 * @since 2.0
		 *
		 * @hook wp_read_image_metadata
		 *
		 * @param array  $meta Image meta data.
		 * @param string $file Path to image file.
		 *
		 * @return array Image meta data.
		 */
		public function restore_meta_data( $meta, $file ) {
			if ( isset( $this->previous_meta[ $file ] ) ) {
				$meta = $this->previous_meta[ $file ];

				// Setting the Orientation meta to the new value after fixing the rotation.
				$meta['orientation'] = 1;
				return $meta;
			}
			return $meta;
		}

	}
}
