<?php
/**
 * Utility functions
 *
 * @package  wpinstructions
 */

namespace WPInstructions\Utils;

/**
 * Check if WP db tables exist
 *
 * @return boolean
 */
function wp_tables_exist( $host, $database, $user, $password, $table_prefix ) {
	$mysqli = mysqli_init();

	if ( ! @$mysqli->real_connect( $host, $user, $password, $database ) ) {
		return false;
	}

	$result = $mysqli->query( 'SHOW TABLES' );
	$tables = [];

	while ( $row = $result->fetch_array() ) {
		$tables[] = $row[0];
	}

	return in_array( $table_prefix . 'users', $tables, true );
}

/**
 * Get wp-config.php table prefix
 *
 * @param  string $wp_config_path Path to wp-config.php
 * @return string
 */
function get_table_prefix( $wp_config_path ) {
	$wp_config_code = explode( "\n", file_get_contents( $wp_config_path ) );
	$prefix         = null;

	foreach ( $wp_config_code as $line ) {
		if ( preg_match( '#^[\s]*\$table_prefix.*("|\')(.*?)("|\').*$#', $line ) ) {
			return preg_replace( '#^[\s]*\$table_prefix.*?("|\')(.*?)("|\').*$#', '$2', $line );
		}
	}

	return $prefix;
}
