<?php
/**
 * Add a site to a multisite
 *
 * A large amount of this code was pulled from https://github.com/wp-cli/wp-cli
 *
 * Examples:
 * add site where name is Site 2 and site url is http://localhost/two and home url is http://localhost/three
 *
 * @package  wpinstructions
 */

namespace WPInstructions\InstructionTypes;

use WPInstructions\InstructionType;
use WPInstructions\Log;
use WPInstructions\UtilsWP;

/**
 * Add site class
 */
class AddSite extends InstructionType {
	/**
	 * Instruction type defaults
	 *
	 * @var array
	 */
	protected $defaults = [
		'site title'      => 'New Site',
		'site url'       => '',
		'home url'       => '',
		'admin email'    => 'test@test.com',
		'admin user'     => 'admin',
		'admin password' => 'password',
	];

	/**
	 * Plugin action
	 *
	 * @var string
	 */
	protected $action = 'add site';

	/**
	 * Map instruction type subject
	 *
	 * @param  string $subject Subject
	 * @return string
	 */
	protected function mapSubject( string $subject ) {
		switch ( $subject ) {
			case 'title':
			case 'blog title':
				return 'site title';
			case 'home':
			case 'url':
				return 'home url';
			case 'site':
				return 'site url';
			case 'user':
				return 'admin user';
			case 'email':
			case 'user email':
				return 'admin email';
			case 'password':
			case 'admin pass':
			case 'pass':
				return 'admin password';
		}

		return $subject;
	}

	/**
	 * Execute add site code
	 *
	 * @param  array $options     Prepared options
	 * @param  array $global_args Global instructions args
	 * @return integer
	 */
	public function run( array $options, array $global_args = [] ) {
		if ( ! is_multisite() ) {
			Log::instance()->write( 'You can only add a site in multisite.', 0, 'error' );

			return 1;
		}

		$user = get_user_by( 'email', $options['admin email'] );

		if ( empty( $user ) ) {
			$user_args = [
				'user_login' => $options['admin user'],
				'user_email' => $options['admin email'],
				'role'       => 'administrator',
				'user_pass'  => $options['admin password'],
			];

			$user = wp_insert_user( $user_args );
		}

		if ( is_subdomain_install() ) {
			$new_domain = parse_url( $options['home url'], PHP_URL_HOST );
			$path       = get_network()->path;
		} else {
			$new_domain = get_network()->domain;
			$path       = get_network()->path . parse_url( $options['home url'], PHP_URL_PATH ) . '/';
		}

		$meta = [
			'public' => 1,
		];

		$id = wpmu_create_blog( $new_domain, $path, $options['site title'], $user->ID, $meta, get_current_network_id() );

		if ( ! is_super_admin( $user->ID ) ) {
			UtilsWP\add_site_admins( $user );
		}

		Log::instance()->write( 'Site `' . $options['site title'] . '` added.' );

		return 0;
	}
}
