<?php
/**
 * PHPUnit bootstrap file for Fix Image Rotation tests.
 *
 * @package Fix_Image_Rotation
 */

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// PHPUnit Polyfills path for WordPress test suite.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

// Determine where the WordPress test suite lives.
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	// CI: sjinks/setup-wordpress-test-library sets WP_TESTS_DIR and generates its own config.
	$_test_root = getenv( 'WP_TESTS_DIR' );
} else {
	// Local: use wp-phpunit from vendor and our own config file.
	$_test_root = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
	define( 'WP_TESTS_CONFIG_FILE_PATH', dirname( __DIR__ ) . '/wp-tests-config.php' );
}

// Give access to tests_add_filter() function.
require_once $_test_root . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/init.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_test_root . '/includes/bootstrap.php';
