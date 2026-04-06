<?php
/**
 * Tests for the Fix Image Rotation plugin.
 *
 * @package Fix_Image_Rotation
 */

/**
 * Test case for verifying image orientation is fixed correctly.
 */
class Test_Fix_Image_Rotation extends WP_UnitTestCase {

	/**
	 * Path to the test images directory.
	 *
	 * @var string
	 */
	private static $test_images_dir;

	/**
	 * Temporary directory for working copies of test images.
	 *
	 * @var string
	 */
	private $temp_dir;

	/**
	 * Plugin instance.
	 *
	 * @var Fix_Image_Rotation
	 */
	private $instance;

	/**
	 * Set up the test images directory path once.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		self::$test_images_dir = dirname( __FILE__ ) . '/test-images';
	}

	/**
	 * Set up each test: create temp dir and get plugin instance.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! extension_loaded( 'exif' ) || ! function_exists( 'exif_read_data' ) ) {
			$this->markTestSkipped( 'EXIF extension not available.' );
		}

		if ( ! extension_loaded( 'gd' ) && ! extension_loaded( 'imagick' ) ) {
			$this->markTestSkipped( 'Neither GD nor Imagick extension available.' );
		}

		$this->temp_dir = sys_get_temp_dir() . '/fix-image-rotation-test-' . uniqid();
		mkdir( $this->temp_dir );

		$this->instance = new Fix_Image_Rotation();
	}

	/**
	 * Clean up temp directory after each test.
	 */
	public function tear_down() {
		if ( is_dir( $this->temp_dir ) ) {
			array_map( 'unlink', glob( $this->temp_dir . '/*' ) );
			rmdir( $this->temp_dir );
		}
		parent::tear_down();
	}

	/**
	 * Copy a test image to the temp directory for non-destructive testing.
	 *
	 * @param string $filename Image filename from test-images directory.
	 * @return string Path to the temporary copy.
	 */
	private function get_temp_image( $filename ) {
		$source = self::$test_images_dir . '/' . $filename;
		$dest   = $this->temp_dir . '/' . $filename;
		copy( $source, $dest );
		return $dest;
	}

	/**
	 * Read the EXIF orientation from a file.
	 *
	 * @param string $file Path to the image file.
	 * @return int|false Orientation value, or false if not set.
	 */
	private function get_orientation( $file ) {
		$exif = exif_read_data( $file );
		return isset( $exif['Orientation'] ) ? (int) $exif['Orientation'] : false;
	}

	/**
	 * Get image dimensions.
	 *
	 * @param string $file Path to the image file.
	 * @return array{0: int, 1: int} Width and height.
	 */
	private function get_dimensions( $file ) {
		$size = getimagesize( $file );
		return array( $size[0], $size[1] );
	}

	/**
	 * Test that orientation=1 images are left unchanged.
	 */
	public function test_orientation_1_unchanged() {
		$file = $this->get_temp_image( 'Landscape_1.jpg' );

		$original_size = filesize( $file );
		$this->instance->fix_image_orientation( $file );

		// Orientation 1 means no fix needed, file should be untouched.
		$this->assertEquals( $original_size, filesize( $file ), 'File with orientation=1 should not be modified.' );
	}

	/**
	 * Test that landscape images with orientation 2-8 are fixed.
	 *
	 * @dataProvider data_landscape_orientations
	 *
	 * @param string $filename       Test image filename.
	 * @param int    $orientation    Original EXIF orientation.
	 * @param int    $expected_width Expected width after fix.
	 * @param int    $expected_height Expected height after fix.
	 */
	public function test_landscape_orientation_fixed( $filename, $orientation, $expected_width, $expected_height ) {
		$file = $this->get_temp_image( $filename );

		// Verify the source image has the expected orientation.
		$this->assertEquals( $orientation, $this->get_orientation( $file ), "Source image $filename should have orientation=$orientation." );

		// Fix the image.
		$this->instance->fix_image_orientation( $file );

		// After fixing, the pixel dimensions should be correct for a landscape image (1800x1200).
		list( $width, $height ) = $this->get_dimensions( $file );
		$this->assertEquals( $expected_width, $width, "Fixed $filename should have width=$expected_width." );
		$this->assertEquals( $expected_height, $height, "Fixed $filename should have height=$expected_height." );
	}

	/**
	 * Data provider for landscape orientations 2-8.
	 *
	 * After fixing, all landscape images should have dimensions 1800x1200.
	 *
	 * @return array
	 */
	public function data_landscape_orientations() {
		return array(
			'Landscape orientation 2 (mirror horizontal)' => array( 'Landscape_2.jpg', 2, 1800, 1200 ),
			'Landscape orientation 3 (rotate 180)'        => array( 'Landscape_3.jpg', 3, 1800, 1200 ),
			'Landscape orientation 4 (mirror vertical)'   => array( 'Landscape_4.jpg', 4, 1800, 1200 ),
			'Landscape orientation 5 (mirror + rotate 90)' => array( 'Landscape_5.jpg', 5, 1800, 1200 ),
			'Landscape orientation 6 (rotate 90)'         => array( 'Landscape_6.jpg', 6, 1800, 1200 ),
			'Landscape orientation 7 (mirror + rotate 270)' => array( 'Landscape_7.jpg', 7, 1800, 1200 ),
			'Landscape orientation 8 (rotate 270)'        => array( 'Landscape_8.jpg', 8, 1800, 1200 ),
		);
	}

	/**
	 * Test that portrait images with orientation 2-8 are fixed.
	 *
	 * @dataProvider data_portrait_orientations
	 *
	 * @param string $filename       Test image filename.
	 * @param int    $orientation    Original EXIF orientation.
	 * @param int    $expected_width Expected width after fix.
	 * @param int    $expected_height Expected height after fix.
	 */
	public function test_portrait_orientation_fixed( $filename, $orientation, $expected_width, $expected_height ) {
		$file = $this->get_temp_image( $filename );

		$this->assertEquals( $orientation, $this->get_orientation( $file ), "Source image $filename should have orientation=$orientation." );

		$this->instance->fix_image_orientation( $file );

		list( $width, $height ) = $this->get_dimensions( $file );
		$this->assertEquals( $expected_width, $width, "Fixed $filename should have width=$expected_width." );
		$this->assertEquals( $expected_height, $height, "Fixed $filename should have height=$expected_height." );
	}

	/**
	 * Data provider for portrait orientations 2-8.
	 *
	 * After fixing, all portrait images should have dimensions 1200x1800.
	 *
	 * @return array
	 */
	public function data_portrait_orientations() {
		return array(
			'Portrait orientation 2 (mirror horizontal)'  => array( 'Portrait_2.jpg', 2, 1200, 1800 ),
			'Portrait orientation 3 (rotate 180)'         => array( 'Portrait_3.jpg', 3, 1200, 1800 ),
			'Portrait orientation 4 (mirror vertical)'    => array( 'Portrait_4.jpg', 4, 1200, 1800 ),
			'Portrait orientation 5 (mirror + rotate 90)' => array( 'Portrait_5.jpg', 5, 1200, 1800 ),
			'Portrait orientation 6 (rotate 90)'          => array( 'Portrait_6.jpg', 6, 1200, 1800 ),
			'Portrait orientation 7 (mirror + rotate 270)' => array( 'Portrait_7.jpg', 7, 1200, 1800 ),
			'Portrait orientation 8 (rotate 270)'         => array( 'Portrait_8.jpg', 8, 1200, 1800 ),
		);
	}

	/**
	 * Test that non-JPEG files are not processed by the upload filter.
	 */
	public function test_upload_filter_skips_non_jpeg() {
		$file = array(
			'name'     => 'test.png',
			'tmp_name' => '/tmp/fake.png',
		);

		$result = $this->instance->filter_wp_handle_upload_prefilter( $file );
		$this->assertEquals( $file, $result, 'PNG files should pass through unchanged.' );
	}

	/**
	 * Test that JPEG files are accepted by the upload filter.
	 */
	public function test_upload_filter_processes_jpeg() {
		$file     = $this->get_temp_image( 'Landscape_3.jpg' );
		$upload   = array(
			'name'     => 'Landscape_3.jpg',
			'tmp_name' => $file,
		);

		$result = $this->instance->filter_wp_handle_upload_prefilter( $upload );

		// The filter should return the file array (it processes in place).
		$this->assertIsArray( $result );
		$this->assertEquals( 'Landscape_3.jpg', $result['name'] );

		// The image at tmp_name should now have correct dimensions.
		list( $width, $height ) = $this->get_dimensions( $file );
		$this->assertEquals( 1800, $width );
		$this->assertEquals( 1200, $height );
	}

	/**
	 * Test that fixing an image twice doesn't corrupt it.
	 */
	public function test_double_fix_is_idempotent() {
		$file = $this->get_temp_image( 'Landscape_6.jpg' );

		$this->instance->fix_image_orientation( $file );

		list( $width1, $height1 ) = $this->get_dimensions( $file );

		// Create a new instance (clears the orientation_fixed cache).
		$instance2 = new Fix_Image_Rotation();
		$instance2->fix_image_orientation( $file );

		list( $width2, $height2 ) = $this->get_dimensions( $file );

		$this->assertEquals( $width1, $width2, 'Width should not change on second fix.' );
		$this->assertEquals( $height1, $height2, 'Height should not change on second fix.' );
	}

	/**
	 * Test the wp_handle_upload filter for post-upload processing.
	 */
	public function test_handle_upload_filter() {
		$file   = $this->get_temp_image( 'Portrait_6.jpg' );
		$upload = array(
			'file' => $file,
			'url'  => 'http://example.org/wp-content/uploads/Portrait_6.jpg',
			'type' => 'image/jpeg',
		);

		$result = $this->instance->filter_wp_handle_upload( $upload );

		$this->assertIsArray( $result );

		list( $width, $height ) = $this->get_dimensions( $file );
		$this->assertEquals( 1200, $width );
		$this->assertEquals( 1800, $height );
	}
}
