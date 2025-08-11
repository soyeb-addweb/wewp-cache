<?php

namespace WeWP\Admin;

use WeWP\Settings\Options;

class SettingsPage {
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu() {
        add_options_page(
            __( 'WeWP Cache Settings', 'wewp' ),
            __( 'WeWP Cache', 'wewp' ),
            'manage_options',
            'wewp-settings',
            array( $this, 'render' )
        );
    }

    public function register_settings() {
        register_setting( 'wewp_settings_group', Options::OPTION_NAME, array( $this, 'sanitize' ) );

        add_settings_section( 'wewp_section_opt', __( 'Optimization', 'wewp' ), '__return_false', 'wewp-settings' );
        $this->add_checkbox( 'optimize_html', __( 'Enable HTML optimization', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'minify_html', __( 'Minify HTML', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'minify_assets', __( 'Minify CSS/JS (via server/CDN)', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'combine_css', __( 'Combine CSS', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'combine_js', __( 'Combine JS', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'async_js', __( 'Load JS asynchronously', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'defer_js', __( 'Defer JS execution', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'http2_push', __( 'HTTP/2 Preload important assets', 'wewp' ), 'wewp_section_opt' );
        $this->add_checkbox( 'lazy_iframes', __( 'Lazy load iframes', 'wewp' ), 'wewp_section_opt' );

        add_settings_section( 'wewp_section_cdn', __( 'CDN & QUIC.cloud', 'wewp' ), '__return_false', 'wewp-settings' );
        add_settings_field( 'cdn_host', __( 'CDN Host', 'wewp' ), array( $this, 'field_cdn_host' ), 'wewp-settings', 'wewp_section_cdn' );

        add_settings_section( 'wewp_section_speed', __( 'PageSpeed', 'wewp' ), '__return_false', 'wewp-settings' );
        $this->add_checkbox( 'precache_enabled', __( 'Automatic page pre-cache', 'wewp' ), 'wewp_section_speed' );
        $this->add_checkbox( 'guest_mode', __( 'Guest mode (cache-friendly headers)', 'wewp' ), 'wewp_section_speed' );

        add_settings_section( 'wewp_section_adv', __( 'Advanced', 'wewp' ), '__return_false', 'wewp-settings' );
        $this->add_checkbox( 'crawler_enabled', __( 'Enable cache crawler', 'wewp' ), 'wewp_section_adv' );
        $this->add_checkbox( 'db_maintenance', __( 'Enable daily DB maintenance', 'wewp' ), 'wewp_section_adv' );
    }

    protected function add_checkbox( $key, $label, $section ) {
        add_settings_field( $key, $label, array( $this, 'field_checkbox' ), 'wewp-settings', $section, array( 'key' => $key ) );
    }

    public function field_checkbox( $args ) {
        $key   = $args['key'];
        $value = Options::get( $key, false );
        echo '<label><input type="checkbox" name="' . esc_attr( Options::OPTION_NAME ) . '[' . esc_attr( $key ) . ']" value="1"' . checked( ! empty( $value ), true, false ) . '> ' . esc_html__( 'Enable', 'wewp' ) . '</label>';
    }

    public function field_cdn_host() {
        $value = Options::get( 'cdn_host', '' );
        echo '<input type="text" class="regular-text" name="' . esc_attr( Options::OPTION_NAME ) . '[cdn_host]" value="' . esc_attr( $value ) . '" placeholder="https://cdn.example.com">';
        echo '<p class="description">' . esc_html__( 'Set your CDN hostname (supports QUIC.cloud, Cloudflare, BunnyCDN, etc.)', 'wewp' ) . '</p>';
    }

    public function sanitize( $input ) {
        $out = array();
        $bools = array('optimize_html','minify_html','minify_assets','combine_css','combine_js','async_js','defer_js','http2_push','lazy_iframes','precache_enabled','guest_mode','crawler_enabled','db_maintenance');
        foreach ( $bools as $key ) {
            $out[ $key ] = isset( $input[ $key ] ) ? (bool) $input[ $key ] : false;
        }
        $out['cdn_host'] = isset( $input['cdn_host'] ) ? esc_url_raw( $input['cdn_host'] ) : '';
        return $out;
    }

    public function render() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'WeWP Cache Settings', 'wewp' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'wewp_settings_group' );
        do_settings_sections( 'wewp-settings' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}