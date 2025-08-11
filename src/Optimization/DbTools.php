<?php

namespace WeWP\Optimization;

use WP_CLI;
use wpdb;
use WeWP\Settings\Options;

class DbTools {
    public function init() {
        add_action( 'wewp_db_cleanup', array( $this, 'cleanup' ) );
        if ( Options::get( 'db_maintenance', true ) && ! wp_next_scheduled( 'wewp_db_cleanup' ) ) {
            wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'wewp_db_cleanup' );
        }
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'wewp db-cleanup', array( $this, 'cli_cleanup' ) );
            WP_CLI::add_command( 'wewp db-optimize', array( $this, 'cli_optimize' ) );
        }
    }

    public function cleanup() {
        if ( ! Options::get( 'db_maintenance', true ) ) {
            return;
        }
        global $wpdb;
        $wpdb->query( "DELETE p FROM {$wpdb->posts} p WHERE p.post_type = 'revision'" );
        $wpdb->query( $wpdb->prepare( "DELETE p FROM {$wpdb->posts} p WHERE p.post_status = %s", 'trash' ) );
        $wpdb->query( $wpdb->prepare( "DELETE c FROM {$wpdb->comments} c WHERE c.comment_approved = %s", 'spam' ) );
        $wpdb->query( "DELETE a, b FROM {$wpdb->options} a JOIN {$wpdb->options} b ON b.option_name = REPLACE(a.option_name, '_timeout', '') WHERE a.option_name LIKE '\_transient\_%\_timeout' AND a.option_value < UNIX_TIMESTAMP()" );
    }

    public function optimize_tables() {
        global $wpdb;
        $tables = $wpdb->get_col( 'SHOW TABLES' );
        foreach ( (array) $tables as $table ) {
            $wpdb->query( "OPTIMIZE TABLE {$table}" );
        }
    }

    public function cli_cleanup() {
        $this->cleanup();
        WP_CLI::success( 'Database cleanup complete.' );
    }

    public function cli_optimize() {
        $this->optimize_tables();
        WP_CLI::success( 'Database tables optimized.' );
    }
}