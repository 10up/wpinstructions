<?php
/**
 * Utility functions for WP
 *
 * @package  wpinstructions
 */

namespace WPInstructions\UtilsWP;

use WP_User;

/**
 * Add site admins to install
 *
 * @param WP_User $site_user User to add
 */
function add_site_admins( WP_User $site_user ) {
	$site_admins = array( $site_user->user_login );

	$users = get_users( array( 'fields' => array( 'ID', 'user_login' ) ) );

	if ( $users ) {
		foreach ( $users as $user ) {
			if ( is_super_admin( $user->ID ) && ! in_array( $user->user_login, $site_admins, true ) ) {
				$site_admins[] = $user->user_login;
			}
		}
	}

	update_site_option( 'site_admins', $site_admins );
}
