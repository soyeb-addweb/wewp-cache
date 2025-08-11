<?php

namespace WeWP\Admin;

class DocsPage {
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
    }

    public function add_menu() {
        add_menu_page(
            __( 'WeWP', 'wewp' ),
            __( 'WeWP', 'wewp' ),
            'manage_options',
            'wewp-docs',
            array( $this, 'render' ),
            'dashicons-performance',
            59
        );

        // Link to the settings page as a submenu for convenience
        add_submenu_page(
            'wewp-docs',
            __( 'Settings', 'wewp' ),
            __( 'Settings', 'wewp' ),
            'manage_options',
            'wewp-settings',
            array( new SettingsPage(), 'render' )
        );
    }

    public function render() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'WeWP Plugin Documentation', 'wewp' ) . '</h1>';
        echo '<p>' . esc_html__( 'This plugin provides caching (page + object), performance optimizations, CDN integration, and developer tools.', 'wewp' ) . '</p>';

        echo '<h2>' . esc_html__( 'Quick Start', 'wewp' ) . '</h2>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Go to Settings → WeWP Cache to enable PageSpeed optimizations (minify, lazy load, defer/async).', 'wewp' ) . '</li>';
        echo '<li>' . esc_html__( 'Set your CDN host if you use QUIC.cloud or other CDNs.', 'wewp' ) . '</li>';
        echo '<li>' . esc_html__( 'Optionally enable Guest Mode and Precache to improve first-visit performance.', 'wewp' ) . '</li>';
        echo '</ol>';

        echo '<h2>' . esc_html__( 'Features', 'wewp' ) . '</h2>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Page Cache & Object Cache purging via Admin Bar and WP-CLI.', 'wewp' ) . '</li>';
        echo '<li>' . esc_html__( 'Page Optimization: HTML/CSS/JS minify, combine assets, lazy load images/iframes, async/defer or delay JS, critical CSS, HTTP/2 preload.', 'wewp' ) . '</li>';
        echo '<li>' . esc_html__( 'CDN Integration: rewrite static URLs to your CDN host.', 'wewp' ) . '</li>';
        echo '<li>' . esc_html__( 'PageSpeed: DNS prefetch, font preload, automatic precache and cache crawler.', 'wewp' ) . '</li>';
        echo '<li>' . esc_html__( 'WooCommerce support: bypass cache on cart/checkout/account.', 'wewp' ) . '</li>';
        echo '</ul>';

        echo '<h2>' . esc_html__( 'WP-CLI Commands', 'wewp' ) . '</h2>';
        echo '<pre><code>wp wewp status
wp wewp update-object-cache-dropin
wp wewp cache purge-site
wp wewp cache purge-post &lt;id&gt;
wp wewp cache purge-url &lt;url&gt;
wp wewp db-cleanup
wp wewp db-optimize
wp wewp precache
wp wewp crawl</code></pre>';

        echo '<h2>' . esc_html__( 'REST API', 'wewp' ) . '</h2>';
        echo '<p><code>POST /wp-json/wewp/v1/purge</code> ' . esc_html__( 'with optional { url } to purge a specific URL.', 'wewp' ) . '</p>';

        echo '</div>';
    }
}