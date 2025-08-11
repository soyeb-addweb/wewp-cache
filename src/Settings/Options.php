<?php

namespace WeWP\Settings;

class Options {
    const OPTION_NAME = 'wewp_settings';

    public static function get_all() {
        $defaults = array(
            'optimize_html'   => false,
            'minify_html'     => true,
            'minify_assets'   => false,
            'combine_css'     => false,
            'combine_js'      => false,
            'async_js'        => true,
            'defer_js'        => true,
            'http2_push'      => false,
            'lazy_iframes'    => true,
            'cdn_host'        => '',
            'precache_enabled'=> false,
            'guest_mode'      => false,
            'crawler_enabled' => false,
            'db_maintenance'  => true,
        );
        $opts = get_option( self::OPTION_NAME, array() );
        if ( ! is_array( $opts ) ) {
            $opts = array();
        }
        return wp_parse_args( $opts, $defaults );
    }

    public static function get( $key, $default = null ) {
        $all = self::get_all();
        return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
    }
}