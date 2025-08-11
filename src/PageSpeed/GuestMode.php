<?php

namespace WeWP\PageSpeed;

class GuestMode {
    public function init() {
        add_action( 'init', array( $this, 'maybe_set_guest_headers' ), 0 );
    }

    public function maybe_set_guest_headers() {
        if ( is_user_logged_in() ) {
            return;
        }
        if ( headers_sent() ) {
            return;
        }
        // Encourage upstream caches before cookies are set
        header( 'Cache-Control: public, max-age=60' );
    }
}