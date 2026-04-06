<?php
/**
 * WordPress test configuration.
 *
 * This file is used by the WordPress test suite. In CI, the
 * sjinks/setup-wordpress-test-library action generates this automatically.
 * For local testing, set the WP_TESTS_* environment variables.
 *
 * @package Fix_Image_Rotation
 */

define( 'ABSPATH', getenv( 'WP_CORE_DIR' ) ? getenv( 'WP_CORE_DIR' ) . '/' : '/tmp/wordpress/' );
define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'wordpress_test' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ?: '' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
