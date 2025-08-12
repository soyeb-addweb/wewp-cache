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

        // Page Optimization → PageSpeed Tab
        add_settings_section( 'wewp_section_pagespeed', __( 'Page Optimization — PageSpeed', 'wewp' ), '__return_false', 'wewp-settings' );
        $this->add_checkbox( 'pagespeed_preset', __( 'PageSpeed Score Optimization (pre-tuned settings)', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'guest_mode', __( 'Guest Mode (instant cached page before cookies)', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'guest_optimization', __( 'Guest Optimization (critical CSS, deferred JS for first-time visitors)', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'lazy_images', __( 'Lazy Load Images', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'lazy_iframes', __( 'Lazy Load Iframes', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'remove_unused_css', __( 'Remove Unused CSS', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'delay_js', __( 'Delay JS Execution until user interaction', 'wewp' ), 'wewp_section_pagespeed' );
        add_settings_field( 'delay_js_list', __( 'Delay JS List (one per line: substring or filename)', 'wewp' ), array( $this, 'field_delay_js_list' ), 'wewp-settings', 'wewp_section_pagespeed' );
        $this->add_checkbox( 'css_async', __( 'CSS Asynchronous (non-blocking CSS)', 'wewp' ), 'wewp_section_pagespeed' );
        add_settings_field( 'critical_css', __( 'Critical CSS', 'wewp' ), array( $this, 'field_critical_css' ), 'wewp-settings', 'wewp_section_pagespeed' );
        add_settings_field( 'dns_prefetch_list', __( 'DNS Prefetch (one per line)', 'wewp' ), array( $this, 'field_dns_prefetch' ), 'wewp-settings', 'wewp_section_pagespeed' );
        add_settings_field( 'font_preload_list', __( 'Font Preload URLs (one per line)', 'wewp' ), array( $this, 'field_font_preload' ), 'wewp-settings', 'wewp_section_pagespeed' );
        $this->add_checkbox( 'minify_html', __( 'Minify HTML', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'minify_assets', __( 'Minify CSS/JS', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'combine_css', __( 'Combine CSS', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'combine_js', __( 'Combine JS', 'wewp' ), 'wewp_section_pagespeed' );
        $this->add_checkbox( 'webp_avif', __( 'Serve WebP/AVIF when available', 'wewp' ), 'wewp_section_pagespeed' );

        // CDN section remains
        add_settings_section( 'wewp_section_cdn', __( 'CDN & QUIC.cloud', 'wewp' ), '__return_false', 'wewp-settings' );
        add_settings_field( 'cdn_host', __( 'CDN Host', 'wewp' ), array( $this, 'field_cdn_host' ), 'wewp-settings', 'wewp_section_cdn' );

        // Advanced/maintenance
        add_settings_section( 'wewp_section_adv', __( 'Advanced & Maintenance', 'wewp' ), '__return_false', 'wewp-settings' );
        $this->add_checkbox( 'precache_enabled', __( 'Automatic page pre-cache', 'wewp' ), 'wewp_section_adv' );
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

    public function field_delay_js_list() {
        $value = Options::get( 'delay_js_list', '' );
        echo '<textarea class="large-text code" rows="5" name="' . esc_attr( Options::OPTION_NAME ) . '[delay_js_list]" placeholder="jquery.js\nanalytics\nplayer.js">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Lines are matched against script src. Matching scripts will be delayed until user interaction or 3 seconds.', 'wewp' ) . '</p>';
    }

    public function field_cdn_host() {
        $value = Options::get( 'cdn_host', '' );
        echo '<input type="text" class="regular-text" name="' . esc_attr( Options::OPTION_NAME ) . '[cdn_host]" value="' . esc_attr( $value ) . '" placeholder="https://cdn.example.com">';
        echo '<p class="description">' . esc_html__( 'Set your CDN hostname (supports QUIC.cloud, Cloudflare, BunnyCDN, etc.)', 'wewp' ) . '</p>';
    }

    public function field_critical_css() {
        $value = Options::get( 'critical_css', '' );
        echo '<textarea class="large-text code" rows="6" name="' . esc_attr( Options::OPTION_NAME ) . '[critical_css]">' . esc_textarea( $value ) . '</textarea>';
    }

    public function field_dns_prefetch() {
        $value = Options::get( 'dns_prefetch_list', '' );
        echo '<textarea class="large-text code" rows="4" name="' . esc_attr( Options::OPTION_NAME ) . '[dns_prefetch_list]" placeholder="//fonts.googleapis.com\n//www.google-analytics.com">' . esc_textarea( $value ) . '</textarea>';
    }

    public function field_font_preload() {
        $value = Options::get( 'font_preload_list', '' );
        echo '<textarea class="large-text code" rows="4" name="' . esc_attr( Options::OPTION_NAME ) . '[font_preload_list]" placeholder="/wp-content/themes/theme/fonts/YourFont.woff2?display=swap">' . esc_textarea( $value ) . '</textarea>';
    }

    public function sanitize( $input ) {
        $out = array();
        $bools = array('pagespeed_preset','optimize_html','minify_html','minify_assets','combine_css','combine_js','async_js','defer_js','delay_js','css_async','http2_push','lazy_images','lazy_iframes','remove_unused_css','precache_enabled','guest_mode','guest_optimization','crawler_enabled','db_maintenance','webp_avif');
        foreach ( $bools as $key ) {
            $out[ $key ] = isset( $input[ $key ] ) ? (bool) $input[ $key ] : false;
        }
        $out['cdn_host']          = isset( $input['cdn_host'] ) ? esc_url_raw( $input['cdn_host'] ) : '';
        $out['critical_css']      = isset( $input['critical_css'] ) ? wp_strip_all_tags( $input['critical_css'] ) : '';
        $out['dns_prefetch_list'] = isset( $input['dns_prefetch_list'] ) ? sanitize_textarea_field( $input['dns_prefetch_list'] ) : '';
        $out['font_preload_list'] = isset( $input['font_preload_list'] ) ? sanitize_textarea_field( $input['font_preload_list'] ) : '';
        $out['delay_js_list']     = isset( $input['delay_js_list'] ) ? sanitize_textarea_field( $input['delay_js_list'] ) : '';
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