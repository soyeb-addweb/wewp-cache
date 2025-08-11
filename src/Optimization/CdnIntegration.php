<?php

namespace WeWP\Optimization;

class CdnIntegration {
    protected $cdn_host;

    public function __construct() {
        $this->cdn_host = defined('WEWP_CDN_HOST') ? WEWP_CDN_HOST : '';
        $this->cdn_host = apply_filters('wewp_cdn_host', $this->cdn_host);
    }

    public function init() {
        if ( empty( $this->cdn_host ) ) {
            return;
        }
        add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ), 20 );
        add_filter( 'wp_get_attachment_url', array( $this, 'rewrite_url' ), 20 );
        add_filter( 'the_content', array( $this, 'rewrite_content_urls' ), 20 );
    }

    public function filter_upload_dir( $uploads ) {
        // Map baseurl to CDN
        $uploads['baseurl'] = $this->map_to_cdn( $uploads['baseurl'] );
        return $uploads;
    }

    public function rewrite_url( $url ) {
        return $this->map_to_cdn( $url );
    }

    public function rewrite_content_urls( $content ) {
        $home = home_url();
        $cdn  = untrailingslashit( $this->cdn_host );
        if ( ! $cdn || stripos( $content, $home ) === false ) {
            return $content;
        }
        // Only rewrite uploads and common static extensions
        $pattern = '#(href|src)=("|\')(' . preg_quote( $home, '#' ) . '/(?:wp-content/uploads|wp-includes|.*?\.(?:css|js|png|jpg|jpeg|gif|webp|svg)))\2#i';
        $content = preg_replace_callback( $pattern, function( $m ) use ( $home, $cdn ) {
            $url = $m[3];
            $url = str_replace( $home, $cdn, $url );
            return $m[1] . '=' . $m[2] . $url . $m[2];
        }, $content );
        return $content;
    }

    protected function map_to_cdn( $url ) {
        if ( empty( $this->cdn_host ) ) {
            return $url;
        }
        $home = home_url();
        return str_replace( $home, untrailingslashit( $this->cdn_host ), $url );
    }
}