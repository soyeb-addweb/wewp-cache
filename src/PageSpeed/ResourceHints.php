<?php

namespace WeWP\PageSpeed;

class ResourceHints {
    public function init() {
        add_filter( 'wp_resource_hints', array( $this, 'add_hints' ), 10, 2 );
    }

    public function add_hints( $urls, $relation_type ) {
        $prefetch  = (array) apply_filters( 'wewp_dns_prefetch', array() );
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
        return array_unique( $urls );
    }
}