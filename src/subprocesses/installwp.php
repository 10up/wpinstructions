<?php
/**
 * Install WP. Must be executed as a separate process.
 *
 * @package  wpinstructions
 */

define( 'WP_INSTALLING', true );

define( 'ABSPATH', $argv[1] );

clearstatcache();

/** Load WordPress Bootstrap */
require_once ABSPATH . 'wp-load.php';

/** Load WordPress Administration Upgrade API */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/** Load WordPress Translation Install API */
require_once ABSPATH . 'wp-admin/includes/translation-install.php';

/** Load wpdb */
require_once ABSPATH . 'wp-includes/wp-db.php';

require_once ABSPATH . 'wp-includes/version.php';

echo file_get_contents(ABSPATH . 'wp-includes/version.php');

echo exec( 'cat ' . ABSPATH . 'wp-includes/version.php' );

error_reporting( E_ERROR | E_WARNING | E_PARSE );

/**
 * Dont send out notification
 */
function wp_new_blog_notification() {
	// Do nothing
}

var_dump($wp_db_version);

$result = wp_install( $argv[2], $argv[3], $argv[4], true, '', $argv[5] );

// Add uploads dir
exec( 'mkdir ' . ABSPATH . 'wp-content/uploads' );

if ( is_wp_error( $result ) ) {
	exit( $result->get_error_message() );
}

exit( 0 );
