<?php
/*
Plugin Name: WeWP Debug Log Path
Plugin URI: https://wewp.com
Description: Set debug.log location for wewp.
Author: wewp
Version: 1.0
Author URI: https://wewp.com/
*/

if ( getenv( 'WEWP_LOG_PATH' ) && WP_DEBUG && WP_DEBUG_LOG ) {
	ini_set( 'error_log', getenv( 'WEWP_LOG_PATH' ) );
}