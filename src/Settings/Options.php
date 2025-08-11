<?php

namespace WeWP\Settings;

class Options {
    const OPTION_NAME = 'wewp_settings';

    public static function get_all() {
        $defaults = array(
            'pagespeed_preset' => false,
            'optimize_html'    => false,
            'minify_html'      => true,
            'minify_assets'    => false,
            'combine_css'      => false,
            'combine_js'       => false,
            'async_js'         => true,
            'defer_js'         => true,
            'delay_js'         => false,
            'css_async'        => false,
            'http2_push'       => false,
            'lazy_images'      => true,
            'lazy_iframes'     => true,
            'remove_unused_css'=> false,
            'critical_css'     => '',
            'dns_prefetch_list'=> '',
            'font_preload_list'=> '',
            'cdn_host'         => '',
            'precache_enabled' => false,
            'guest_mode'       => false,
            'guest_optimization'=> false,
            'crawler_enabled'  => false,
            'db_maintenance'   => true,
            'webp_avif'        => false,
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