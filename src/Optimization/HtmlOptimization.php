<?php

namespace WeWP\Optimization;

use WeWP\Settings\Options;

class HtmlOptimization {
    /**
     * Whether optimization is enabled.
     * Controlled by WEWP_OPTIMIZE_HTML constant or 'wewp_optimize_html' filter.
     *
     * @return bool
     */
    protected function is_enabled() {
        return (bool) Options::get( 'optimize_html', false );
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
        if ( Options::get( 'lazy_images', true ) ) {
            $html = $this->lazyload_images( $html );
        }
        if ( Options::get( 'lazy_iframes', true ) ) {
            $html = $this->lazyload_iframes( $html );
        }
        if ( Options::get( 'css_async', false ) ) {
            $html = $this->make_css_async( $html );
        }
        if ( Options::get( 'delay_js', false ) ) {
            $html = $this->delay_js_execution( $html );
        } elseif ( Options::get( 'async_js', true ) || Options::get( 'defer_js', true ) ) {
            $html = $this->add_async_defer_to_scripts( $html );
        }
        if ( Options::get( 'minify_html', true ) ) {
            $html = $this->minify_html( $html );
        }
        if ( Options::get( 'http2_push', false ) ) {
            $this->send_http2_preload_headers( $html );
        }
        return $html;
    }

    protected function inject_critical_css( $html ) {
        $critical_css = Options::get( 'critical_css', '' );
        if ( empty( $critical_css ) ) {
            return $html;
        }
        $style_tag = '<style id="wewp-critical-css">' . $critical_css . '</style>';
        $html = preg_replace( '/<head(.*?)>/', '<head$1>' . $style_tag, $html, 1 );
        return $html;
    }

    protected function lazyload_images( $html ) {
        // Add loading="lazy" to images without it
        $html = preg_replace_callback( '/<img(.*?)>/i', function( $m ) {
            $tag = $m[0];
            if ( stripos( $tag, ' loading=' ) === false ) {
                $tag = preg_replace( '/>$/', ' loading="lazy">', $tag );
            }
            return $tag;
        }, $html );
        return $html;
    }

    protected function lazyload_iframes( $html ) {
        $html = preg_replace( '/<iframe(?![^>]*\bloading=)[^>]*>/', '$0', $html );
        $html = preg_replace_callback( '/<iframe(.*?)>/', function($matches) {
            $tag = $matches[0];
            if ( stripos( $tag, ' loading=' ) !== false ) {
                return $tag;
            }
            return preg_replace( '/>$/', ' loading="lazy">', $tag );
        }, $html );
        return $html;
    }

    protected function make_css_async( $html ) {
        // Convert <link rel="stylesheet"> to preload with onload swap
        $html = preg_replace_callback('/<link([^>]+)rel=("|\')stylesheet(\2)([^>]*)>/i', function($m) {
            $attrs = $m[1] . $m[4];
            if ( stripos( $attrs, ' href=' ) === false ) {
                return $m[0];
            }
            $tag = '<link rel="preload" as="style" ' . trim( $attrs ) . ' onload="this.onload=null;this.rel=\'stylesheet\'">';
            $tag .= '<noscript><link rel="stylesheet" ' . trim( $attrs ) . '></noscript>';
            return $tag;
        }, $html );
        return $html;
    }

    protected function delay_js_execution( $html ) {
        // Replace <script src=...> with data-delayed attribute and bootstrap a small runner script
        $html = preg_replace_callback('/<script([^>]*)src=("|\')(.*?)(\2)([^>]*)><\/script>/i', function($matches) {
            $attrs = trim( $matches[1] . ' ' . $matches[5] );
            $src   = $matches[3];
            // Skip inline or json scripts
            if ( stripos( $attrs, ' type="application/ld+json"' ) !== false ) {
                return $matches[0];
            }
            return '<script data-wewp-delay src="' . esc_url( $src ) . '" ' . $attrs . '></script>';
        }, $html );

        // Inject the runner before </body>
        $runner = "<script>(function(){var run=function(){var s=document.querySelectorAll('script[data-wewp-delay]');for(var i=0;i<s.length;i++){var el=s[i];if(!el.dataset.wewpRan){el.dataset.wewpRan=1;var n=document.createElement('script');for(var j=0;j<el.attributes.length;j++){var a=el.attributes[j];if(a.name!=='data-wewp-delay'){n.setAttribute(a.name,a.value);}}n.src=el.src;el.parentNode.insertBefore(n,el);}}};['click','scroll','mousemove','keydown','touchstart'].forEach(function(e){window.addEventListener(e,run,{once:true,passive:true});});setTimeout(run,3000);})();</script>";
        if ( false !== stripos( $html, '</body>' ) ) {
            $html = str_ireplace( '</body>', $runner . '</body>', $html );
        } else {
            $html .= $runner;
        }
        return $html;
    }

    protected function add_async_defer_to_scripts( $html ) {
        $async = (bool) Options::get( 'async_js', true );
        $defer = (bool) Options::get( 'defer_js', true );
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
        $html = preg_replace('/<!--(?!\[if|<!|>)(.*?)-->/s', '', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        $html = trim($html);
        return $html;
    }

    protected function send_http2_preload_headers( $html ) {
        if ( headers_sent() ) {
            return;
        }
        $links = array();
        if ( preg_match_all('/<link[^>]+rel=("|\')(stylesheet)(\1)[^>]+href=("|\')(.*?)(\4)/i', $html, $m) ) {
            foreach ($m[5] as $href) {
                $links[] = '<' . esc_url_raw( $href ) . '>; rel=preload; as=style';
            }
        }
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