<?php

namespace WeWP\Optimization;

class CssJsMinifier {
    public function init() {
        add_filter( 'style_loader_tag', array( $this, 'filter_style_tag' ), 20, 4 );
        add_filter( 'script_loader_tag', array( $this, 'filter_script_tag' ), 20, 3 );
    }

    public function filter_style_tag( $html, $handle, $href, $media ) {
        $href = $this->maybe_minify_url( $href );
        return preg_replace( '/href=("|\')(.*?)(\1)/', 'href="$href"', $html );
    }

    public function filter_script_tag( $tag, $handle, $src ) {
        $src = $this->maybe_minify_url( $src );
        return preg_replace( '/src=("|\')(.*?)(\1)/', 'src="' . esc_url( $src ) . '"', $tag );
    }

    protected function maybe_minify_url( $url ) {
        $enabled = defined('WEWP_MINIFY_ASSETS') ? (bool) WEWP_MINIFY_ASSETS : false;
        $enabled = (bool) apply_filters( 'wewp_minify_assets', $enabled );
        if ( ! $enabled ) {
            return $url;
        }
        // For demonstration, append a query flag that can be handled by a CDN or server to serve minified versions
        $url = add_query_arg( 'minify', '1', $url );
        return $url;
    }
}