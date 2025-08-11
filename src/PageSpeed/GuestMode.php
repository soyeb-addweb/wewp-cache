<?php

namespace WeWP\PageSpeed;

use WeWP\Settings\Options;

class GuestMode {
    public function init() {
        add_action( 'init', array( $this, 'maybe_set_guest_headers' ), 0 );
    }

    public function maybe_set_guest_headers() {
        if ( is_user_logged_in() ) {
            return;
        }
        if ( ! Options::get( 'guest_mode', false ) ) {
            return;
        }
        if ( headers_sent() ) {
            return;
        }
        header( 'Cache-Control: public, max-age=60' );
    }
}