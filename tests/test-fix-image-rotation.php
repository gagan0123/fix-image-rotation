<?php
/**
 * Tests for the Fix_Image_Rotation class.
 *
 * @package Fix_Image_Rotation
 */

/**
 * Class Fix_Image_Rotation_Tests
 */
class Fix_Image_Rotation_Tests extends WP_UnitTestCase {

	/**
	 * Plugin instance.
	 *
	 * @var Fix_Image_Rotation
	 */
	private $instance;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();
		$this->instance = new Fix_Image_Rotation();
	}

	/**
	 * Test that the singleton returns a Fix_Image_Rotation instance.
	 */
	public function test_get_instance() {
		$instance = Fix_Image_Rotation::get_instance();
		$this->assertInstanceOf( 'Fix_Image_Rotation', $instance );
	}

	/**
	 * Test that get_instance returns the same instance.
	 */
	public function test_get_instance_returns_same_instance() {
		$instance1 = Fix_Image_Rotation::get_instance();
		$instance2 = Fix_Image_Rotation::get_instance();
		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that hooks are registered when EXIF extension is available.
	 */
	public function test_register_hooks_with_exif() {
		if ( ! extension_loaded( 'exif' ) || ! function_exists( 'exif_read_data' ) ) {
			$this->markTestSkipped( 'EXIF extension is not available.' );
		}

		$instance = new Fix_Image_Rotation();
		$instance->register_hooks();

		$this->assertNotFalse( has_filter( 'wp_handle_upload_prefilter', array( $instance, 'filter_wp_handle_upload_prefilter' ) ) );
		$this->assertNotFalse( has_filter( 'wp_handle_upload', array( $instance, 'filter_wp_handle_upload' ) ) );
	}

	/**
	 * Test that upload prefilter skips non-JPEG files.
	 */
	public function test_prefilter_skips_png() {
		$file = array(
			'name'     => 'test.png',
			'tmp_name' => '/tmp/test.png',
		);

		$result = $this->instance->filter_wp_handle_upload_prefilter( $file );
		$this->assertSame( $file, $result );
	}

	/**
	 * Test that upload prefilter skips GIF files.
	 */
	public function test_prefilter_skips_gif() {
		$file = array(
			'name'     => 'test.gif',
			'tmp_name' => '/tmp/test.gif',
		);

		$result = $this->instance->filter_wp_handle_upload_prefilter( $file );
		$this->assertSame( $file, $result );
	}

	/**
	 * Test that upload prefilter skips WebP files.
	 */
	public function test_prefilter_skips_webp() {
		$file = array(
			'name'     => 'test.webp',
			'tmp_name' => '/tmp/test.webp',
		);

		$result = $this->instance->filter_wp_handle_upload_prefilter( $file );
		$this->assertSame( $file, $result );
	}

	/**
	 * Test that upload filter skips non-JPEG files.
	 */
	public function test_upload_filter_skips_png() {
		$file = array(
			'file' => '/tmp/test.png',
			'url'  => 'http://example.com/test.png',
			'type' => 'image/png',
		);

		$result = $this->instance->filter_wp_handle_upload( $file );
		$this->assertSame( $file, $result );
	}

	/**
	 * Test that upload prefilter handles uppercase extensions.
	 */
	public function test_prefilter_handles_uppercase_extension() {
		if ( ! extension_loaded( 'exif' ) ) {
			$this->markTestSkipped( 'EXIF extension is not available.' );
		}

		// Use a test image with orientation 1 (no rotation needed).
		$test_image = dirname( __FILE__ ) . '/test-images/Landscape_1.jpg';
		if ( ! file_exists( $test_image ) ) {
			$this->markTestSkipped( 'Test image not found.' );
		}

		$tmp = wp_tempnam( 'test.JPG' );
		copy( $test_image, $tmp );

		$file = array(
			'name'     => 'test.JPG',
			'tmp_name' => $tmp,
		);

		$result = $this->instance->filter_wp_handle_upload_prefilter( $file );
		$this->assertSame( $file, $result );

		unlink( $tmp );
	}

	/**
	 * Test that restore_meta_data returns original meta when no previous meta exists.
	 */
	public function test_restore_meta_data_no_previous() {
		$meta = array(
			'orientation' => 6,
			'title'       => 'Test',
		);

		$result = $this->instance->restore_meta_data( $meta, '/tmp/nonexistent.jpg' );
		$this->assertSame( $meta, $result );
	}

	/**
	 * Test calculate_flip_and_rotate for all EXIF orientations via reflection.
	 *
	 * @dataProvider orientation_data_provider
	 *
	 * @param int        $orientation EXIF orientation value.
	 * @param array|bool $expected    Expected operations array or false.
	 */
	public function test_calculate_flip_and_rotate( $orientation, $expected ) {
		$method = new ReflectionMethod( 'Fix_Image_Rotation', 'calculate_flip_and_rotate' );
		$method->setAccessible( true );

		$exif   = array( 'Orientation' => $orientation );
		$result = $method->invoke( $this->instance, '/tmp/test.jpg', $exif );

		$this->assertSame( $expected, $result );
	}

	/**
	 * Data provider for EXIF orientation test cases.
	 *
	 * @return array
	 */
	public function orientation_data_provider() {
		return array(
			'orientation_1_no_change'          => array(
				1,
				false,
			),
			'orientation_2_flip_horizontal'    => array(
				2,
				array(
					'orientation' => 0,
					'rotator'     => false,
					'flipper'     => array( false, true ),
				),
			),
			'orientation_3_rotate_180'         => array(
				3,
				array(
					'orientation' => -180,
					'rotator'     => true,
					'flipper'     => false,
				),
			),
			'orientation_4_flip_vertical'      => array(
				4,
				array(
					'orientation' => 0,
					'rotator'     => false,
					'flipper'     => array( true, false ),
				),
			),
			'orientation_5_rotate_90_flip'     => array(
				5,
				array(
					'orientation' => -90,
					'rotator'     => true,
					'flipper'     => array( false, true ),
				),
			),
			'orientation_6_rotate_90'          => array(
				6,
				array(
					'orientation' => -90,
					'rotator'     => true,
					'flipper'     => false,
				),
			),
			'orientation_7_rotate_270_flip'    => array(
				7,
				array(
					'orientation' => -270,
					'rotator'     => true,
					'flipper'     => array( false, true ),
				),
			),
			'orientation_8_rotate_270'         => array(
				8,
				array(
					'orientation' => -270,
					'rotator'     => true,
					'flipper'     => false,
				),
			),
		);
	}
}
