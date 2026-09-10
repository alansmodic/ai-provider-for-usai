<?php
/**
 * PHPUnit bootstrap.
 *
 * @package AlanSmodic\AiProviderForUsai
 */

declare( strict_types=1 );

$root = dirname( __DIR__ );

if ( ! file_exists( $root . '/vendor/autoload.php' ) ) {
	fwrite( STDERR, "Composer autoloader not found. Run `composer install`.\n" );
	exit( 1 );
}

require_once $root . '/vendor/autoload.php';
require_once $root . '/tests/wordpress-functions.php';
