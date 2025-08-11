<?php

namespace WeWP\Cli;

use WeWP\Plugin;
use WP_CLI;

/**
 * Perform wewp operations.
 *
 * ## EXAMPLES
 *
 *     # Show the status of wewp
 *     $ wp wewp status
 */
class Commands {

	/**
	 * Update the Redis object cache drop-in.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wewp update-object-cache-dropin
	 *
	 * @subcommand update-object-cache-dropin
	 */
	public function update_object_cache_dropin() {
		$result = Plugin::update_object_cache_dropin();

		if ( $result ) {
			WP_CLI::success( __( 'Object cache drop-in updated.', 'wewp' ) );
		} else {
			WP_CLI::error( __( 'Object cache drop-in could not be updated.', 'wewp' ) );
		}
	}

	/**
	 * Show the status of wewp.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wewp status
	 *
	 * @subcommand status
	 */
	public function status() {
		$status = WP_CLI::colorize( '%r' . __( 'Disabled', 'wewp' ) . '%n' );
		if ( defined( 'WEWP_CACHE_PATH' ) || getenv( 'WEWP_CACHE_PATH' ) ) {
			$status = WP_CLI::colorize( '%g' . __( 'Enabled', 'wewp' ) . '%n' );
		}

		WP_CLI::line( __( 'Page Cache: ', 'wewp' ) . $status );

		$status = WP_CLI::colorize( '%r' . __( 'Disabled', 'wewp' ) . '%n' );
		if ( wp_using_ext_object_cache() ) {
			$status = WP_CLI::colorize( '%g' . __( 'Enabled', 'wewp' ) . '%n' );
		}

		WP_CLI::line( __( 'Object Cache: ', 'wewp' ) . $status );
	}
}