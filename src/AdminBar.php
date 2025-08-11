<?php

namespace WeWP;

class AdminBar {

	/**
	 * @var array
	 */
	private $items = array();

	/**
	 * @var string
	 */
	public $url;

	/**
	 * AdminBar constructor.
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
		add_action( 'admin_bar_menu', array( $this, 'render' ), 100 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'wewp-admin', $this->url . 'assets/css/admin.css', array(), '1.0' );
	}

	/**
	 * Render the admin bar menu.
	 *
	 * @param $wp_admin_bar
	 */
	public function render( $wp_admin_bar ) {
		if ( empty( $this->items ) ) {
			return;
		}

		if ( ! current_user_can( apply_filters( 'wewp_purge_cache_capability', 'manage_options' ) ) ) {
			return;
		}

		$wp_admin_bar->add_node( array(
			'id'    => 'wewp',
			'title' => apply_filters( 'wewp_admin_bar_title', __( 'WeWP Cache', 'wewp' ) ),
		) );

		foreach ( $this->items as $item ) {
			$wp_admin_bar->add_node( array(
				'parent' => 'wewp',
				'id'     => strtolower( str_replace( ' ', '-', $item['title'] ) ),
				'title'  => $item['title'],
				'href'   => wp_nonce_url( add_query_arg( 'wewp_action', $item['action'], admin_url() ), $item['action'] ),
			) );
		}
	}

	/**
	 * Add an item to the admin bar.
	 *
	 * @param string $title
	 * @param string $action
	 */
	public function add_item( $title, $action ) {
		$this->items[] = array(
			'title'  => $title,
			'action' => $action,
		);
	}
}
