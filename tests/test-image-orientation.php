<?php
/**
 * Integration tests for image orientation fixing.
 *
 * Tests the full rotation pipeline using the test images in tests/test-images/.
 * Each test image has a known EXIF orientation and pixel dimensions. After
 * fixing, all images of the same type (landscape/portrait) should have
 * identical dimensions matching the orientation-1 reference image.
 *
 * Landscape reference (orientation 1): 1800x1200
 * Portrait reference  (orientation 1): 1200x1800
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
	 * Temp files to clean up after each test.
	 *
	 * @var array
	 */
	private $temp_files = array();

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
		$this->temp_files      = array();

		if ( ! is_dir( $this->test_images_dir ) ) {
			$this->markTestSkipped( 'Test images directory not found.' );
		}
	}

	/**
	 * Clean up temp files.
	 */
	public function tear_down() {
		foreach ( $this->temp_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		parent::tear_down();
	}

	/**
	 * Create a temp copy of a test image that preserves the .jpg extension.
	 *
	 * wp_tempnam() appends random characters to the filename, breaking
	 * the file extension (e.g. "test.jpgAbCdEf"). The plugin's extension
	 * check and wp_get_image_editor both require a proper .jpg extension.
	 *
	 * @param string $filename Source filename in the test-images directory.
	 * @return string Path to the temp copy with .jpg extension.
	 */
	private function create_temp_image( $filename ) {
		$source = $this->test_images_dir . $filename;
		$tmp    = sys_get_temp_dir() . '/' . uniqid( 'fir_test_' ) . '.jpg';
		copy( $source, $tmp );
		$this->temp_files[] = $tmp;
		return $tmp;
	}

	/**
	 * Test that landscape images with orientation > 1 are fixed to correct dimensions.
	 *
	 * After fixing, all landscape images should match the orientation-1 reference
	 * dimensions of 1800x1200 regardless of their original EXIF orientation.
	 *
	 * @dataProvider landscape_image_provider
	 *
	 * @param string $filename         Test image filename.
	 * @param int    $orientation      Expected EXIF orientation before fix.
	 * @param int    $original_width   Pixel width before fix.
	 * @param int    $original_height  Pixel height before fix.
	 * @param int    $expected_width   Expected pixel width after fix.
	 * @param int    $expected_height  Expected pixel height after fix.
	 */
	public function test_landscape_image_rotation( $filename, $orientation, $original_width, $original_height, $expected_width, $expected_height ) {
		$source = $this->test_images_dir . $filename;
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( "Test image {$filename} not found." );
		}

		$tmp = $this->create_temp_image( $filename );

		// Verify original has the expected orientation and dimensions.
		$exif_before = exif_read_data( $tmp );
		$this->assertNotFalse( $exif_before, "Failed to read EXIF data from {$filename}" );
		$this->assertArrayHasKey( 'Orientation', $exif_before, "No Orientation in EXIF for {$filename}" );
		$this->assertEquals( $orientation, $exif_before['Orientation'], "Pre-fix orientation mismatch for {$filename}" );

		$size_before = getimagesize( $tmp );
		$this->assertEquals( $original_width, $size_before[0], "Pre-fix width mismatch for {$filename}" );
		$this->assertEquals( $original_height, $size_before[1], "Pre-fix height mismatch for {$filename}" );

		$hash_before = md5_file( $tmp );

		// Fix orientation.
		$this->instance->fix_image_orientation( $tmp );

		// Verify the file was actually modified.
		$hash_after = md5_file( $tmp );
		$this->assertNotSame( $hash_before, $hash_after, "Image {$filename} should have been modified by orientation fix." );

		// Verify dimensions match the expected corrected dimensions (1800x1200 for landscape).
		$size_after = getimagesize( $tmp );
		$this->assertNotFalse( $size_after, "Failed to read fixed image {$filename}" );
		$this->assertEquals( $expected_width, $size_after[0], "Post-fix width mismatch for {$filename}" );
		$this->assertEquals( $expected_height, $size_after[1], "Post-fix height mismatch for {$filename}" );

		// Verify the image editor can load the fixed file.
		$editor = wp_get_image_editor( $tmp );
		$this->assertNotWPError( $editor, "Image editor failed to load fixed image {$filename}" );
	}

	/**
	 * Test that portrait images with orientation > 1 are fixed to correct dimensions.
	 *
	 * After fixing, all portrait images should match the orientation-1 reference
	 * dimensions of 1200x1800 regardless of their original EXIF orientation.
	 *
	 * @dataProvider portrait_image_provider
	 *
	 * @param string $filename         Test image filename.
	 * @param int    $orientation      Expected EXIF orientation before fix.
	 * @param int    $original_width   Pixel width before fix.
	 * @param int    $original_height  Pixel height before fix.
	 * @param int    $expected_width   Expected pixel width after fix.
	 * @param int    $expected_height  Expected pixel height after fix.
	 */
	public function test_portrait_image_rotation( $filename, $orientation, $original_width, $original_height, $expected_width, $expected_height ) {
		$source = $this->test_images_dir . $filename;
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( "Test image {$filename} not found." );
		}

		$tmp = $this->create_temp_image( $filename );

		// Verify original has the expected orientation and dimensions.
		$exif_before = exif_read_data( $tmp );
		$this->assertNotFalse( $exif_before, "Failed to read EXIF data from {$filename}" );
		$this->assertArrayHasKey( 'Orientation', $exif_before, "No Orientation in EXIF for {$filename}" );
		$this->assertEquals( $orientation, $exif_before['Orientation'], "Pre-fix orientation mismatch for {$filename}" );

		$size_before = getimagesize( $tmp );
		$this->assertEquals( $original_width, $size_before[0], "Pre-fix width mismatch for {$filename}" );
		$this->assertEquals( $original_height, $size_before[1], "Pre-fix height mismatch for {$filename}" );

		$hash_before = md5_file( $tmp );

		// Fix orientation.
		$this->instance->fix_image_orientation( $tmp );

		// Verify the file was actually modified.
		$hash_after = md5_file( $tmp );
		$this->assertNotSame( $hash_before, $hash_after, "Image {$filename} should have been modified by orientation fix." );

		// Verify dimensions match the expected corrected dimensions (1200x1800 for portrait).
		$size_after = getimagesize( $tmp );
		$this->assertNotFalse( $size_after, "Failed to read fixed image {$filename}" );
		$this->assertEquals( $expected_width, $size_after[0], "Post-fix width mismatch for {$filename}" );
		$this->assertEquals( $expected_height, $size_after[1], "Post-fix height mismatch for {$filename}" );

		// Verify the image editor can load the fixed file.
		$editor = wp_get_image_editor( $tmp );
		$this->assertNotWPError( $editor, "Image editor failed to load fixed image {$filename}" );
	}

	/**
	 * Test that orientation 1 images are not modified at all.
	 *
	 * @dataProvider orientation_1_provider
	 *
	 * @param string $filename Test image filename.
	 * @param int    $width    Expected pixel width.
	 * @param int    $height   Expected pixel height.
	 */
	public function test_orientation_1_image_not_modified( $filename, $width, $height ) {
		$source = $this->test_images_dir . $filename;
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( "{$filename} test image not found." );
		}

		$tmp = $this->create_temp_image( $filename );

		// Verify original dimensions before fix.
		$size_before = getimagesize( $tmp );
		$this->assertEquals( $width, $size_before[0], "Pre-fix width mismatch for {$filename}" );
		$this->assertEquals( $height, $size_before[1], "Pre-fix height mismatch for {$filename}" );

		$hash_before = md5_file( $tmp );

		$this->instance->fix_image_orientation( $tmp );

		// File should be completely untouched.
		$hash_after = md5_file( $tmp );
		$size_after = getimagesize( $tmp );

		$this->assertSame( $hash_before, $hash_after, "Orientation 1 image {$filename} should not be modified." );
		$this->assertEquals( $width, $size_after[0], "Width should be unchanged for {$filename}" );
		$this->assertEquals( $height, $size_after[1], "Height should be unchanged for {$filename}" );
	}

	/**
	 * Test that fixing the same file twice does not re-process it.
	 */
	public function test_duplicate_processing_prevention() {
		$source = $this->test_images_dir . 'Landscape_6.jpg';
		if ( ! file_exists( $source ) ) {
			$this->markTestSkipped( 'Landscape_6.jpg test image not found.' );
		}

		$tmp = $this->create_temp_image( 'Landscape_6.jpg' );

		// First fix — should rotate 1200x1800 → 1800x1200.
		$this->instance->fix_image_orientation( $tmp );
		$hash_after_first = md5_file( $tmp );
		$size_after_first = getimagesize( $tmp );

		$this->assertEquals( 1800, $size_after_first[0], 'Width after first fix should be 1800.' );
		$this->assertEquals( 1200, $size_after_first[1], 'Height after first fix should be 1200.' );

		// Second fix — should be skipped entirely.
		$this->instance->fix_image_orientation( $tmp );
		$hash_after_second = md5_file( $tmp );

		$this->assertSame( $hash_after_first, $hash_after_second, 'Second fix should not modify the file.' );
	}

	/**
	 * Test that all landscape images end up with identical dimensions after fixing.
	 *
	 * This verifies that regardless of the original EXIF orientation, the visual
	 * result is consistent — all landscape images should be 1800 wide by 1200 tall.
	 */
	public function test_all_landscape_images_have_consistent_dimensions() {
		$expected_width  = 1800;
		$expected_height = 1200;

		for ( $i = 2; $i <= 8; $i++ ) {
			$filename = "Landscape_{$i}.jpg";
			$source   = $this->test_images_dir . $filename;
			if ( ! file_exists( $source ) ) {
				continue;
			}

			// Each iteration needs a fresh instance to avoid duplicate-processing guard.
			$instance = new Fix_Image_Rotation();
			$tmp      = $this->create_temp_image( $filename );
			$instance->fix_image_orientation( $tmp );

			$size = getimagesize( $tmp );
			$this->assertEquals( $expected_width, $size[0], "Landscape_{$i} width should be {$expected_width} after fix." );
			$this->assertEquals( $expected_height, $size[1], "Landscape_{$i} height should be {$expected_height} after fix." );
		}
	}

	/**
	 * Test that all portrait images end up with identical dimensions after fixing.
	 */
	public function test_all_portrait_images_have_consistent_dimensions() {
		$expected_width  = 1200;
		$expected_height = 1800;

		for ( $i = 2; $i <= 8; $i++ ) {
			$filename = "Portrait_{$i}.jpg";
			$source   = $this->test_images_dir . $filename;
			if ( ! file_exists( $source ) ) {
				continue;
			}

			// Each iteration needs a fresh instance to avoid duplicate-processing guard.
			$instance = new Fix_Image_Rotation();
			$tmp      = $this->create_temp_image( $filename );
			$instance->fix_image_orientation( $tmp );

			$size = getimagesize( $tmp );
			$this->assertEquals( $expected_width, $size[0], "Portrait_{$i} width should be {$expected_width} after fix." );
			$this->assertEquals( $expected_height, $size[1], "Portrait_{$i} height should be {$expected_height} after fix." );
		}
	}

	/**
	 * Data provider for landscape test images.
	 *
	 * Format: filename, EXIF orientation, original width, original height, expected width, expected height.
	 *
	 * Orientations 2-4: stored as 1800x1200 (flips/180° rotation keep dimensions).
	 * Orientations 5-8: stored as 1200x1800 (90°/270° rotation swaps dimensions).
	 * After fix, all should be 1800x1200.
	 *
	 * @return array
	 */
	public function landscape_image_provider() {
		return array(
			'Landscape_2_flip_h'      => array( 'Landscape_2.jpg', 2, 1800, 1200, 1800, 1200 ),
			'Landscape_3_rotate_180'  => array( 'Landscape_3.jpg', 3, 1800, 1200, 1800, 1200 ),
			'Landscape_4_flip_v'      => array( 'Landscape_4.jpg', 4, 1800, 1200, 1800, 1200 ),
			'Landscape_5_rot90_flip'  => array( 'Landscape_5.jpg', 5, 1200, 1800, 1800, 1200 ),
			'Landscape_6_rotate_90'   => array( 'Landscape_6.jpg', 6, 1200, 1800, 1800, 1200 ),
			'Landscape_7_rot270_flip' => array( 'Landscape_7.jpg', 7, 1200, 1800, 1800, 1200 ),
			'Landscape_8_rotate_270'  => array( 'Landscape_8.jpg', 8, 1200, 1800, 1800, 1200 ),
		);
	}

	/**
	 * Data provider for portrait test images.
	 *
	 * Format: filename, EXIF orientation, original width, original height, expected width, expected height.
	 *
	 * Orientations 2-4: stored as 1200x1800 (flips/180° rotation keep dimensions).
	 * Orientations 5-8: stored as 1800x1200 (90°/270° rotation swaps dimensions).
	 * After fix, all should be 1200x1800.
	 *
	 * @return array
	 */
	public function portrait_image_provider() {
		return array(
			'Portrait_2_flip_h'      => array( 'Portrait_2.jpg', 2, 1200, 1800, 1200, 1800 ),
			'Portrait_3_rotate_180'  => array( 'Portrait_3.jpg', 3, 1200, 1800, 1200, 1800 ),
			'Portrait_4_flip_v'      => array( 'Portrait_4.jpg', 4, 1200, 1800, 1200, 1800 ),
			'Portrait_5_rot90_flip'  => array( 'Portrait_5.jpg', 5, 1800, 1200, 1200, 1800 ),
			'Portrait_6_rotate_90'   => array( 'Portrait_6.jpg', 6, 1800, 1200, 1200, 1800 ),
			'Portrait_7_rot270_flip' => array( 'Portrait_7.jpg', 7, 1800, 1200, 1200, 1800 ),
			'Portrait_8_rotate_270'  => array( 'Portrait_8.jpg', 8, 1800, 1200, 1200, 1800 ),
		);
	}

	/**
	 * Data provider for orientation 1 (no-op) images.
	 *
	 * @return array
	 */
	public function orientation_1_provider() {
		return array(
			'Landscape_1' => array( 'Landscape_1.jpg', 1800, 1200 ),
			'Portrait_1'  => array( 'Portrait_1.jpg', 1200, 1800 ),
		);
	}
}
