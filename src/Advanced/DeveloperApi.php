<?php

namespace WeWP\Advanced;

use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

class DeveloperApi {
    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'wewp/v1', '/purge', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'purge' ),
            'permission_callback' => array( $this, 'permissions' ),
            'args' => array(
                'url' => array( 'type' => 'string', 'required' => false ),
            ),
        ) );
    }

    public function permissions() {
        return current_user_can( apply_filters( 'wewp_purge_cache_capability', 'manage_options' ) );
    }

    public function purge( WP_REST_Request $request ) {
        $url = $request->get_param( 'url' );
        if ( $url ) {
            $ok = wewp()->cache->purge_url( esc_url_raw( $url ) );
        } else {
            $ok = wewp()->cache->purge_page_cache();
        }
        if ( ! $ok ) {
            return new WP_Error( 'wewp_purge_failed', 'Purge failed', array( 'status' => 500 ) );
        }
        return array( 'success' => true );
    }
}