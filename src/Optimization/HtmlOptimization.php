<?php

namespace WeWP\Optimization;

class HtmlOptimization {
    /**
     * Whether optimization is enabled.
     * Controlled by WEWP_OPTIMIZE_HTML constant or 'wewp_optimize_html' filter.
     *
     * @return bool
     */
    protected function is_enabled() {
        $enabled = defined('WEWP_OPTIMIZE_HTML') ? (bool) WEWP_OPTIMIZE_HTML : false;
        return (bool) apply_filters('wewp_optimize_html', $enabled);
    }

    public function init() {
        add_action('template_redirect', array($this, 'start_buffering'), PHP_INT_MAX - 10);
    }

    public function start_buffering() {
        if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || ! $this->is_enabled() ) {
            return;
        }
        ob_start( array( $this, 'process_html' ) );
    }

    /**
     * Process final HTML.
     *
     * @param string $html
     * @return string
     */
    public function process_html( $html ) {
        if ( ! is_string( $html ) || stripos( $html, '<html' ) === false ) {
            return $html;
        }

        $html = $this->inject_critical_css( $html );
        $html = $this->lazyload_iframes( $html );
        $html = $this->add_async_defer_to_scripts( $html );
        $html = $this->minify_html( $html );
        $this->send_http2_preload_headers( $html );

        return $html;
    }

    protected function inject_critical_css( $html ) {
        $critical_css = apply_filters('wewp_critical_css', defined('WEWP_CRITICAL_CSS') ? WEWP_CRITICAL_CSS : '' );
        if ( empty( $critical_css ) ) {
            return $html;
        }
        // Insert right after opening <head>
        $style_tag = '<style id="wewp-critical-css">' . $critical_css . '</style>';
        $html = preg_replace( '/<head(.*?)>/', '<head$1>' . $style_tag, $html, 1 );
        return $html;
    }

    protected function lazyload_iframes( $html ) {
        $enabled = defined('WEWP_LAZY_IFRAMES') ? (bool) WEWP_LAZY_IFRAMES : true;
        $enabled = (bool) apply_filters('wewp_lazyload_iframes', $enabled);
        if ( ! $enabled ) {
            return $html;
        }
        // Add loading="lazy" to iframes that do not have it
        $html = preg_replace( '/<iframe(?![^>]*\bloading=)[^>]*>/', '$0', $html );
        $html = preg_replace_callback( '/<iframe(.*?)>/', function($matches) {
            $tag = $matches[0];
            if ( stripos( $tag, ' loading=' ) !== false ) {
                return $tag;
            }
            // Insert loading="lazy" before closing '>'
            return preg_replace( '/>$/', ' loading="lazy">', $tag );
        }, $html );
        return $html;
    }

    protected function add_async_defer_to_scripts( $html ) {
        $async = defined('WEWP_ASYNC_JS') ? (bool) WEWP_ASYNC_JS : true;
        $defer = defined('WEWP_DEFER_JS') ? (bool) WEWP_DEFER_JS : true;
        $exclusions = apply_filters('wewp_defer_async_exclusions', array('jquery.js', 'jquery.min.js'));

        if ( ! $async && ! $defer ) {
            return $html;
        }

        return preg_replace_callback('/<script([^>]*)src=("|\')(.*?)(\2)([^>]*)><\/script>/i', function($matches) use ($async, $defer, $exclusions) {
            $full  = $matches[0];
            $attrs = $matches[1] . $matches[5];
            $src   = $matches[3];

            foreach ( (array) $exclusions as $exclude ) {
                if ( stripos( $src, $exclude ) !== false ) {
                    return $full;
                }
            }

            if ( $defer && stripos( $attrs, ' defer' ) === false ) {
                $full = str_replace('<script', '<script defer', $full);
            }
            if ( $async && stripos( $attrs, ' async' ) === false ) {
                $full = str_replace('<script', '<script async', $full);
            }
            return $full;
        }, $html );
    }

    protected function minify_html( $html ) {
        $enabled = defined('WEWP_MINIFY_HTML') ? (bool) WEWP_MINIFY_HTML : true;
        $enabled = (bool) apply_filters('wewp_minify_html', $enabled);
        if ( ! $enabled ) {
            return $html;
        }
        // Remove HTML comments except IE conditions and scripts templates
        $html = preg_replace('/<!--(?!\[if|<!|>)(.*?)-->/s', '', $html);
        // Collapse whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);
        // Trim
        $html = trim($html);
        return $html;
    }

    protected function send_http2_preload_headers( $html ) {
        $enabled = defined('WEWP_HTTP2_PUSH') ? (bool) WEWP_HTTP2_PUSH : false;
        $enabled = (bool) apply_filters('wewp_http2_push', $enabled);
        if ( ! $enabled || headers_sent() ) {
            return;
        }
        $links = array();
        // Preload main stylesheets
        if ( preg_match_all('/<link[^>]+rel=("|\')(stylesheet)(\1)[^>]+href=("|\')(.*?)(\4)/i', $html, $m) ) {
            foreach ($m[5] as $href) {
                $links[] = '<' . esc_url_raw( $href ) . '>; rel=preload; as=style';
            }
        }
        // Preload scripts
        if ( preg_match_all('/<script[^>]+src=("|\')(.*?)(\1)/i', $html, $m2) ) {
            foreach ($m2[2] as $src) {
                $links[] = '<' . esc_url_raw( $src ) . '>; rel=preload; as=script';
            }
        }
        if ( ! empty( $links ) ) {
            foreach ( $links as $link ) {
                header( 'Link: ' . $link, false );
            }
        }
    }
}