<?php

namespace WeWP\PageSpeed;

use WP_CLI;

class Precache {
    public function init() {
        add_action( 'wewp_precache', array( $this, 'crawl' ) );
        if ( ! wp_next_scheduled( 'wewp_precache' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'wewp_precache' );
        }
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'wewp precache', array( $this, 'cli_precache' ) );
        }
    }

    public function crawl() {
        $urls = $this->get_urls_to_precache();
        foreach ( $urls as $url ) {
            wp_remote_get( $url, array( 'timeout' => 5, 'blocking' => false ) );
        }
    }

    public function cli_precache() {
        $this->crawl();
        WP_CLI::success( 'Precache triggered.' );
    }

    protected function get_urls_to_precache() {
        $urls = array( home_url( '/' ) );
        $recent = get_posts( array(
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ) );
        foreach ( $recent as $post_id ) {
            $urls[] = get_permalink( $post_id );
        }
        return array_unique( apply_filters( 'wewp_precache_urls', $urls ) );
    }
}