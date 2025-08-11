<?php

namespace WeWP\Optimization;

use WeWP\Settings\Options;

class Combiner {
    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_combine' ), 999 );
        add_action( 'template_redirect', array( $this, 'serve_combined' ), 0 );
    }

    protected function is_enabled_css() {
        $enabled = (bool) Options::get( 'combine_css', false );
        return (bool) apply_filters( 'wewp_combine_css', $enabled );
    }

    protected function is_enabled_js() {
        $enabled = (bool) Options::get( 'combine_js', false );
        return (bool) apply_filters( 'wewp_combine_js', $enabled );
    }

    public function maybe_combine() {
        if ( is_admin() ) {
            return;
        }
        if ( $this->is_enabled_css() ) {
            $this->combine_styles();
        }
        if ( $this->is_enabled_js() ) {
            $this->combine_scripts();
        }
    }

    protected function combine_styles() {
        global $wp_styles;
        if ( ! $wp_styles instanceof \WP_Styles ) {
            return;
        }
        $handles = array();
        foreach ( (array) $wp_styles->queue as $handle ) {
            $src = isset( $wp_styles->registered[ $handle ] ) ? $wp_styles->registered[ $handle ]->src : '';
            if ( empty( $src ) ) {
                continue;
            }
            $url = $this->resolve_src( $src, $wp_styles->base_url );
            if ( ! $this->is_local_asset( $url ) ) {
                continue;
            }
            $handles[] = $handle;
        }
        if ( empty( $handles ) ) {
            return;
        }
        foreach ( $handles as $h ) {
            wp_dequeue_style( $h );
        }
        $combine_url = add_query_arg( array(
            'wewp_combine' => 'css',
            'h'            => implode( ',', $handles ),
            'ver'          => $this->site_version(),
        ), home_url( '/' ) );
        wp_enqueue_style( 'wewp-combined', $combine_url, array(), null );
    }

    protected function combine_scripts() {
        global $wp_scripts;
        if ( ! $wp_scripts instanceof \WP_Scripts ) {
            return;
        }
        $handles = array();
        foreach ( (array) $wp_scripts->queue as $handle ) {
            $src = isset( $wp_scripts->registered[ $handle ] ) ? $wp_scripts->registered[ $handle ]->src : '';
            if ( empty( $src ) ) {
                continue;
            }
            $url = $this->resolve_src( $src, $wp_scripts->base_url );
            if ( ! $this->is_local_asset( $url ) ) {
                continue;
            }
            $handles[] = $handle;
        }
        if ( empty( $handles ) ) {
            return;
        }
        foreach ( $handles as $h ) {
            wp_dequeue_script( $h );
        }
        $combine_url = add_query_arg( array(
            'wewp_combine' => 'js',
            'h'            => implode( ',', $handles ),
            'ver'          => $this->site_version(),
        ), home_url( '/' ) );
        wp_enqueue_script( 'wewp-combined', $combine_url, array(), null, true );
    }

    public function serve_combined() {
        if ( ! isset( $_GET['wewp_combine'] ) || ! isset( $_GET['h'] ) ) {
            return;
        }
        $type = sanitize_key( wp_unslash( $_GET['wewp_combine'] ) );
        $handles = array_filter( array_map( 'sanitize_key', explode( ',', wp_unslash( $_GET['h'] ) ) ) );

        if ( $type === 'css' ) {
            $content = $this->get_combined_css( $handles );
            if ( ! headers_sent() ) {
                header( 'Content-Type: text/css; charset=UTF-8' );
                header( 'Cache-Control: public, max-age=31536000, immutable' );
            }
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }
        if ( $type === 'js' ) {
            $content = $this->get_combined_js( $handles );
            if ( ! headers_sent() ) {
                header( 'Content-Type: application/javascript; charset=UTF-8' );
                header( 'Cache-Control: public, max-age=31536000, immutable' );
            }
            echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }
    }

    protected function get_combined_css( array $handles ) {
        global $wp_styles;
        $buffer = '';
        foreach ( $handles as $handle ) {
            if ( isset( $wp_styles->registered[ $handle ] ) ) {
                $src = $this->resolve_src( $wp_styles->registered[ $handle ]->src, $wp_styles->base_url );
                $css = $this->fetch( $src );
                $buffer .= "\n/* {$handle} */\n" . $this->minify_css( $css );
            }
        }
        return $buffer;
    }

    protected function get_combined_js( array $handles ) {
        global $wp_scripts;
        $buffer = '';
        foreach ( $handles as $handle ) {
            if ( isset( $wp_scripts->registered[ $handle ] ) ) {
                $src = $this->resolve_src( $wp_scripts->registered[ $handle ]->src, $wp_scripts->base_url );
                $js  = $this->fetch( $src );
                $buffer .= "\n/* {$handle} */\n" . $this->minify_js( $js );
            }
        }
        return $buffer;
    }

    protected function resolve_src( $src, $base ) {
        if ( 0 === strpos( $src, 'http://' ) || 0 === strpos( $src, 'https://' ) ) {
            return $src;
        }
        return rtrim( $base, '/' ) . '/' . ltrim( $src, '/' );
    }

    protected function is_local_asset( $url ) {
        $home = wp_parse_url( home_url(), PHP_URL_HOST );
        $host = wp_parse_url( $url, PHP_URL_HOST );
        return empty( $host ) || $host === $home;
    }

    protected function fetch( $url ) {
        $response = wp_remote_get( $url, array( 'timeout' => 10 ) );
        if ( is_wp_error( $response ) ) {
            return '';
        }
        return (string) wp_remote_retrieve_body( $response );
    }

    protected function minify_css( $css ) {
        // Basic CSS minification: remove comments and whitespace
        $css = preg_replace( '!/\*.*?\*/!s', '', $css );
        $css = preg_replace( '/\s+/', ' ', $css );
        $css = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $css );
        $css = str_replace( array( ' {', '{ ' ), '{', $css );
        $css = str_replace( array( ' }', '} ' ), '}', $css );
        $css = str_replace( '; ', ';', $css );
        $css = str_replace( ': ', ':', $css );
        return trim( $css );
    }

    protected function minify_js( $js ) {
        // Very basic JS minification: remove comments and collapse whitespace
        $js = preg_replace( '#/\*.*?\*/#s', '', $js );
        $js = preg_replace( '#([^:])//.*$#m', '$1', $js );
        $js = preg_replace( '/\s+/', ' ', $js );
        return trim( $js );
    }

    protected function site_version() {
        return (string) apply_filters( 'wewp_assets_version', wp_get_environment_type() . '-' . get_bloginfo( 'version' ) );
    }
}