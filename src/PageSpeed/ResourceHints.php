<?php

namespace WeWP\PageSpeed;

use WeWP\Settings\Options;

class ResourceHints {
    public function init() {
        add_filter( 'wp_resource_hints', array( $this, 'add_hints' ), 10, 2 );
        add_action( 'wp_head', array( $this, 'output_font_preloads' ), 2 );
    }

    public function add_hints( $urls, $relation_type ) {
        $prefetch  = $this->list_to_array( Options::get( 'dns_prefetch_list', '' ) );
        $preconnect = (array) apply_filters( 'wewp_preconnect', array() );
        $prerender = (array) apply_filters( 'wewp_prerender', array() );

        if ( 'dns-prefetch' === $relation_type ) {
            $urls = array_merge( $urls, $prefetch );
        }
        if ( 'preconnect' === $relation_type ) {
            $urls = array_merge( $urls, $preconnect );
        }
        if ( 'prerender' === $relation_type ) {
            $urls = array_merge( $urls, $prerender );
        }
        return array_unique( array_filter( $urls ) );
    }

    public function output_font_preloads() {
        $fonts = $this->list_to_array( Options::get( 'font_preload_list', '' ) );
        foreach ( $fonts as $font ) {
            echo '<link rel="preload" as="font" href="' . esc_url( $font ) . '" type="font/woff2" crossorigin />' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    protected function list_to_array( $list ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $list );
        $out = array();
        foreach ( $lines as $line ) {
            $t = trim( $line );
            if ( $t !== '' ) {
                $out[] = $t;
            }
        }
        return $out;
    }
}