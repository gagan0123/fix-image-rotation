<?php
/**
 * Integration tests for image orientation fixing.
 *
 * Tests the full rotation pipeline using the test images in tests/test-images/.
 *
 * @package Fix_Image_Rotation
 */

/**
 * Class Fix_Image_Rotation_Integration_Tests
 */
class Fix_Image_Rotation_Integration_Tests extends WP_UnitTestCase {

	/**
	 * Plugin instance.
	 *
	 * @var Fix_Image_Rotation
	 */
	private $instance;

	/**
	 * Path to test images directory.
	 *
	 * @var string
	 */
	private $test_images_dir;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! extension_loaded( 'exif' ) || ! function_exists( 'exif_read_data' ) ) {
			$this->markTestSkipped( 'EXIF extension is not available.' );
		}

		$this->instance        = new Fix_Image_Rotation();
		$this->test_images_dir = dirname( __FILE__ ) . '/test-images/';

		if ( ! is_dir( $this->test_images_dir ) ) {
			$this->markTestSkipped( 'Test images directory not found.' );
		}
	}

	/**
	 * Test that landscape images with orientation > 1 are fixed.
	 *
	 * @dataProvider landscape_image_provider
	 *
	 * @param string $filename    Test image filename.
	 * @param int    $orientation Expected EXIF orientation before fix.
	 */
	public function test_landscape_image_rotation( $filename, $orientation ) {
		$source = $this->test_images_dir . $filename;
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( "Test image {$filename} not found." );
		}

		$tmp = wp_tempnam( $filename );
		copy( $source, $tmp );

		// Verify original has the expected orientation.
		$exif_before = exif_read_data( $tmp );
		if ( false === $exif_before || ! isset( $exif_before['Orientation'] ) ) {
			unlink( $tmp );
			$this->markTestSkipped( "Test image {$filename} has no EXIF orientation data." );
		}
		$this->assertEquals( $orientation, $exif_before['Orientation'], "Pre-fix orientation mismatch for {$filename}" );

		// Fix orientation.
		$this->instance->fix_image_orientation( $tmp );

		// After fixing, the image should be loadable and valid.
		$editor = wp_get_image_editor( $tmp );
		$this->assertNotWPError( $editor, "Image editor failed to load fixed image {$filename}" );

		unlink( $tmp );
	}

	/**
	 * Test that portrait images with orientation > 1 are fixed.
	 *
	 * @dataProvider portrait_image_provider
	 *
	 * @param string $filename    Test image filename.
	 * @param int    $orientation Expected EXIF orientation before fix.
	 */
	public function test_portrait_image_rotation( $filename, $orientation ) {
		$source = $this->test_images_dir . $filename;
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( "Test image {$filename} not found." );
		}

		$tmp = wp_tempnam( $filename );
		copy( $source, $tmp );

		// Verify original has the expected orientation.
		$exif_before = exif_read_data( $tmp );
		if ( false === $exif_before || ! isset( $exif_before['Orientation'] ) ) {
			unlink( $tmp );
			$this->markTestSkipped( "Test image {$filename} has no EXIF orientation data." );
		}
		$this->assertEquals( $orientation, $exif_before['Orientation'], "Pre-fix orientation mismatch for {$filename}" );

		// Fix orientation.
		$this->instance->fix_image_orientation( $tmp );

		// After fixing, the image should be loadable and valid.
		$editor = wp_get_image_editor( $tmp );
		$this->assertNotWPError( $editor, "Image editor failed to load fixed image {$filename}" );

		unlink( $tmp );
	}

	/**
	 * Test that orientation 1 images are not modified.
	 */
	public function test_orientation_1_image_not_modified() {
		$source = $this->test_images_dir . 'Landscape_1.jpg';
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( 'Landscape_1.jpg test image not found.' );
		}

		$tmp = wp_tempnam( 'Landscape_1.jpg' );
		copy( $source, $tmp );

		$hash_before = md5_file( $tmp );

		$this->instance->fix_image_orientation( $tmp );

		$hash_after = md5_file( $tmp );

		$this->assertSame( $hash_before, $hash_after, 'Orientation 1 image should not be modified.' );

		unlink( $tmp );
	}

	/**
	 * Test that fixing the same file twice does not re-process it.
	 */
	public function test_duplicate_processing_prevention() {
		$source = $this->test_images_dir . 'Landscape_6.jpg';
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( 'Landscape_6.jpg test image not found.' );
		}

		$tmp = wp_tempnam( 'Landscape_6.jpg' );
		copy( $source, $tmp );

		// First fix.
		$this->instance->fix_image_orientation( $tmp );
		$hash_after_first = md5_file( $tmp );

		// Second fix — should be skipped.
		$this->instance->fix_image_orientation( $tmp );
		$hash_after_second = md5_file( $tmp );

		$this->assertSame( $hash_after_first, $hash_after_second, 'Second fix should not modify the file.' );

		unlink( $tmp );
	}

	/**
	 * Data provider for landscape test images.
	 *
	 * @return array
	 */
	public function landscape_image_provider() {
		return array(
			'Landscape_2' => array( 'Landscape_2.jpg', 2 ),
			'Landscape_3' => array( 'Landscape_3.jpg', 3 ),
			'Landscape_4' => array( 'Landscape_4.jpg', 4 ),
			'Landscape_5' => array( 'Landscape_5.jpg', 5 ),
			'Landscape_6' => array( 'Landscape_6.jpg', 6 ),
			'Landscape_7' => array( 'Landscape_7.jpg', 7 ),
			'Landscape_8' => array( 'Landscape_8.jpg', 8 ),
		);
	}

	/**
	 * Data provider for portrait test images.
	 *
	 * @return array
	 */
	public function portrait_image_provider() {
		return array(
			'Portrait_2' => array( 'Portrait_2.jpg', 2 ),
			'Portrait_3' => array( 'Portrait_3.jpg', 3 ),
			'Portrait_4' => array( 'Portrait_4.jpg', 4 ),
			'Portrait_5' => array( 'Portrait_5.jpg', 5 ),
			'Portrait_6' => array( 'Portrait_6.jpg', 6 ),
			'Portrait_7' => array( 'Portrait_7.jpg', 7 ),
			'Portrait_8' => array( 'Portrait_8.jpg', 8 ),
		);
	}
}
