<?php
/**
 * Install WP. Must be executed as a separate process.
 *
 * @package  wpinstructions
 */

define( 'WP_INSTALLING', true );

define( 'ABSPATH', $argv[1] );

/** Load WordPress Bootstrap */
require_once ABSPATH . 'wp-load.php';

/** Load WordPress Administration Upgrade API */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/** Load WordPress Translation Install API */
require_once ABSPATH . 'wp-admin/includes/translation-install.php';

/** Load wpdb */
require_once ABSPATH . 'wp-includes/wp-db.php';

$result = wp_install( $argv[2], $argv[3], $argv[4], true, '', $argv[5] );

// Add uploads dir
exec( 'mkdir ' . ABSPATH . 'wp-content/uploads' );

if ( is_wp_error( $result ) ) {
	exit( 1 );
}

exit( 0 );
