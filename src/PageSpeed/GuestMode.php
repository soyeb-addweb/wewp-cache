<?php

namespace WeWP\PageSpeed;

use WeWP\Settings\Options;

class GuestMode {
    public function init() {
        add_action( 'init', array( $this, 'maybe_set_guest_headers' ), 0 );
        add_action( 'template_redirect', array( $this, 'maybe_set_guest_cookie' ), PHP_INT_MAX );
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

    public function maybe_set_guest_cookie() {
        if ( is_user_logged_in() ) {
            return;
        }
        if ( ! Options::get( 'guest_optimization', false ) ) {
            return;
        }
        if ( headers_sent() ) {
            return;
        }
        if ( empty( $_COOKIE['wewp_guest'] ) ) {
            setcookie( 'wewp_guest', '1', time() + DAY_IN_SECONDS * 30, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
    }
}