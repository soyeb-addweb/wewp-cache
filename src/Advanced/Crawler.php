<?php

namespace WeWP\Advanced;

use WP_CLI;

class Crawler {
    public function init() {
        add_action( 'wewp_crawler_run', array( $this, 'run' ) );
        if ( ! wp_next_scheduled( 'wewp_crawler_run' ) ) {
            wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', 'wewp_crawler_run' );
        }
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'wewp crawl', array( $this, 'cli_crawl' ) );
        }
    }

    public function run() {
        $urls = $this->discover_urls();
        foreach ( $urls as $url ) {
            wp_remote_get( $url, array( 'timeout' => 5, 'blocking' => false ) );
        }
    }

    public function cli_crawl() {
        $this->run();
        WP_CLI::success( 'Crawler executed.' );
    }

    protected function discover_urls() {
        $urls = array();
        // Include homepage
        $urls[] = home_url( '/' );
        // From sitemap if available
        $sitemap = home_url( '/sitemap.xml' );
        $resp = wp_remote_get( $sitemap, array( 'timeout' => 5 ) );
        if ( ! is_wp_error( $resp ) && 200 === wp_remote_retrieve_response_code( $resp ) ) {
            $body = wp_remote_retrieve_body( $resp );
            if ( preg_match_all( '/<loc>(.*?)<\/loc>/', $body, $m ) ) {
                $urls = array_merge( $urls, $m[1] );
            }
        }
        return array_unique( apply_filters( 'wewp_crawler_urls', $urls ) );
    }
}