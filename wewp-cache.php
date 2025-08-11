<?php
/**
 * Plugin Name: WeWP Cache
 * Plugin URI:  https://www.wewp.io
 * Description: Provides full cache, object cache, and page cache.
 * Author:       WeWP Cache
 * Version:      1.0
 * Network:      True
 * Text Domain:  wewp-cache
 * Requires PHP: 7.1
 * Requires WP:  4.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Fallback PSR-4 autoloader for WeWP namespace (in case Composer is unavailable)
spl_autoload_register( function ( $class ) {
    if ( 0 !== strpos( $class, 'WeWP\\' ) ) {
        return;
    }
    $relative = substr( $class, 5 );
    $path     = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $path ) ) {
        require_once $path;
    }
} );

/**
 * The main wewp function.
 *
 * @return \wewp\Plugin
 */
function WeWP_cache()
{
    if ( isset( $GLOBALS['WeWP'] ) && $GLOBALS['WeWP'] instanceof \WeWP\Plugin ) {
        return $GLOBALS['WeWP'];
    }


    $GLOBALS['WeWP'] = new \WeWP\Plugin( __FILE__ );
    $GLOBALS['WeWP']->run();
    return $GLOBALS['WeWP'];
}

WeWP_cache();