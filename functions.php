<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'wewp' ) ) {
    /**
     * Global accessor for the WeWP plugin instance.
     *
     * @return \WeWP\Plugin
     */
    function wewp() {
        if ( isset( $GLOBALS['WeWP'] ) && $GLOBALS['WeWP'] instanceof \WeWP\Plugin ) {
            return $GLOBALS['WeWP'];
        }

        if ( function_exists( 'WeWP_cache' ) ) {
            return WeWP_cache();
        }

        return null;
    }
}

if ( ! function_exists( 'wewp_is_first_time_guest' ) ) {
    /**
     * Detect if this is a first-time guest visit (no auth and no wewp_guest cookie).
     *
     * @return bool
     */
    function wewp_is_first_time_guest() {
        if ( is_user_logged_in() ) {
            return false;
        }
        return empty( $_COOKIE['wewp_guest'] );
    }
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