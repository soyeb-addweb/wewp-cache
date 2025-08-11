<?php

namespace WeWP\Cli;

use WP_CLI;

/**
 * Perform wewp cache operations.
 *
 * ## EXAMPLES
 *
 *     # Purge the entire wewp page cache
 *     $ wp wewp purge-site
 */
class CacheCommands {

	/**
	 * Purge the entire wewp page cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wewp purge-site
	 *
	 * @subcommand purge-site
	 */
	public function purge_site() {
		if ( wewp()->cache->purge_page_cache() ) {
			WP_CLI::success( __( 'The page cache was purged.', 'wewp' ) );
		} else {
			WP_CLI::error( __( 'The page cache could not be purged.', 'wewp' ) );
		}
	}

	/**
	 * Purge a single post from the wewp page cache.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The ID of the post to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wewp purge-post 123
	 *
	 * @subcommand purge-post
	 */
	public function purge_post( $args ) {
		$post = get_post( $args[0] );

		if ( ! $post ) {
			WP_CLI::error( __( 'Post not found.', 'wewp' ) );

			return;
		}

		if ( wewp()->cache->purge_post( $post ) ) {
			WP_CLI::success( __( 'Post purged from the page cache.', 'wewp' ) );
		} else {
			WP_CLI::error( __( 'Post could not be purged from the page cache.', 'wewp' ) );
		}
	}

	/**
	 * Purge a single URL from the wewp page cache.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : The URL to purge.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wewp purge-url https://example.com
	 *
	 * @subcommand purge-url
	 */
	public function purge_url( $args ) {
		if ( wewp()->cache->purge_url( $args[0] ) ) {
			WP_CLI::success( __( 'URL purged from the page cache.', 'wewp' ) );
		} else {
			WP_CLI::error( __( 'URL could not be purged from the page cache.', 'wewp' ) );
		}
	}
}