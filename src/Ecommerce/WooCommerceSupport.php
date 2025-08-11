<?php

namespace WeWP\Ecommerce;

class WooCommerceSupport {
    public function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        add_action( 'template_redirect', array( $this, 'maybe_bypass_page_cache' ), 0 );
        add_filter( 'woocommerce_product_is_on_sale', array( $this, 'touch_product_cache' ), 10, 2 );
    }

    public function maybe_bypass_page_cache() {
        if ( is_cart() || is_checkout() || is_account_page() ) {
            if ( ! headers_sent() ) {
                header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
                header( 'Pragma: no-cache' );
            }
            if ( function_exists( 'nocache_headers' ) ) {
                nocache_headers();
            }
            // Mark for upstream to bypass FastCGI cache if integrated
            do_action( 'wewp_bypass_page_cache' );
        }
    }

    public function touch_product_cache( $on_sale, $product ) {
        // Trigger hooks so external purgers can clear related pages when prices change
        do_action( 'wewp_product_cache_touch', $product ? $product->get_id() : 0 );
        return $on_sale;
    }
}