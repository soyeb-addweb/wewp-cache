<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'wewp_purge_site' ) ) {
    /**
     * Purge the entire wewp page cache.
     *
     * @return bool
     */
    function wewp_purge_site() {
        return wewp()->cache->purge_page_cache();
    }
}

if ( ! function_exists( 'wewp_purge_post' ) ) {
    /**
     * Purge a single post from the wewp page cache.
     *
     * @param \WP_Post $post
     * @return bool
     */
    function wewp_purge_post( $post ) {
        return wewp()->cache->purge_post( $post );
    }
}

if ( ! function_exists( 'wewp_purge_url' ) ) {
    /**
     * Purge a single URL from the wewp page cache.
     *
     * @param string $url
     * @return bool
     */
    function wewp_purge_url( $url ) {
        return wewp()->cache->purge_url( $url );
    }
}