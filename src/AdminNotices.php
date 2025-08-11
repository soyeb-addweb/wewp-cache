<?php

namespace WeWP;

class AdminNotices {

	/**
	 * @var string
	 */
	public $url;

	/**
	 * AdminNotices constructor.
	 *
	 * @param string $url
	 */
	public function __construct( $url ) {
		$this->url = $url;
	}

	/**
	 * Init
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wewp_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );

		if ( is_multisite() ) {
			add_action( 'network_admin_notices', array( $this, 'show_notices' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'show_notices' ) );
		}
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function enqueue_scripts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( 'wewp-dismiss', $this->url . 'assets/js/dismiss-notice.js', array( 'jquery' ), '1.0' );
	}

	/**
	 * Handle AJAX request to dismiss notice.
	 */
	public function ajax_dismiss_notice() {
		if ( ! check_ajax_referer( 'dismiss-notice', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_die( - 1, 403 );
		}

		$notice = isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : '';

		update_site_option( "wewp_{$notice}_notice_dismissed", true );
	}

	/**
	 * Show notices.
	 */
	public function show_notices() {
		$this->show_redis_cache_disabled_notice();
		$this->show_object_cache_dropin_updated_notice();
	}
	/**
	 * Show a notice about Redis Object Cache plugin being disabled.
	 */
	public function show_redis_cache_disabled_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! get_site_option( 'wewp_redis_cache_disabled' ) || get_site_option( 'wewp_redis_cache_disabled_notice_dismissed' ) ) {
			return;
		}

		$msg   = __( 'The Redis Object Cache plugin has been deactivated and can be removed. The wewp plugin now handles clearing the Redis object cache.', 'wewp' );
		$nonce = wp_create_nonce( 'dismiss-notice' );
		echo "<div class=\"wewp notice notice-success is-dismissible\" data-nonce=\"{$nonce}\" data-notice=\"redis_cache_disabled\"><p><strong>wewp</strong> — {$msg}</p></div>";
	}

	/**
	 * Show a notice when the object-cache.php drop-in has been updated.
	 */
	public function show_object_cache_dropin_updated_notice() {
		$wpcontent_dir = untrailingslashit( WP_CONTENT_DIR );

		if ( file_exists( $wpcontent_dir . '/object-cache.php' ) ) {
			$plugin_path = untrailingslashit( dirname( __DIR__ ) );

			$dropin = get_plugin_data( $wpcontent_dir . '/object-cache.php' );
			$plugin = get_plugin_data( $plugin_path . '/drop-ins/object-cache.php' );

			if ( $dropin['PluginURI'] !== $plugin['PluginURI'] ) {
				return;
			}

			if ( version_compare( $dropin['Version'], $plugin['Version'], '<' ) ) {
				$result = Plugin::update_object_cache_dropin();

				if ( $result ) {
					$msg = __( 'Object cache drop-in updated.', 'wewp' );
					echo "<div class=\"wewp notice notice-success\"><p><strong>wewp</strong> — {$msg}</p></div>";
				} else {
					$msg = __( 'Object cache drop-in could not be updated.', 'wewp' );
					echo "<div class=\"wewp notice notice-error\"><p><strong>wewp</strong> — {$msg}</p></div>";
				}
			}
		}
	}
}
