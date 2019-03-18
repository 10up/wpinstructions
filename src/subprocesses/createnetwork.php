<?php
/**
 * Install WP network. Must be executed as a separate process.
 *
 * @package  wpinstructions
 */

namespace WPInstructions;

require_once __DIR__ . '/../../vendor/autoload.php';

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

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$wp_config_path = $argv[2];
$site_url       = $argv[3];
$admin_email    = $argv[4];
$site_title     = $argv[5];

$network_domain = preg_replace( '|https?://|', '', $site_url );

if ( 'http://localhost' === $site_url ) {
	$network_domain = 'localhost';
} else {
	$network_domain = parse_url( $site_url, PHP_URL_HOST );
}

foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
	$wpdb->$table = $prefixed_table;
}

install_network();

$result = populate_network(
	1,
	$network_domain,
	$admin_email,
	$site_title,
	'/',
	false
);

if ( true !== $result ) {
	exit( 1 );
}

// delete_site_option() cleans the alloptions cache to prevent dupe option
delete_site_option( 'upload_space_check_disabled' );
update_site_option( 'upload_space_check_disabled', 1 );

$ms_constants = [
	'WP_ALLOW_MULTISITE'   => true,
	'MULTISITE'            => true,
	'SUBDOMAIN_INSTALL'    => false,
	'DOMAIN_CURRENT_SITE'  => $network_domain,
	'PATH_CURRENT_SITE'    => '/',
	'SITE_ID_CURRENT_SITE' => 1,
	'BLOG_ID_CURRENT_SITE' => 1,
];

\WPSnapshots\Utils\write_constants_to_wp_config( $ms_constants, $wp_config_path );

exit( 0 );
