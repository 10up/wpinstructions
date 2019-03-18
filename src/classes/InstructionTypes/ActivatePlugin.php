<?php
/**
 * Activate plugin instruction type
 *
 * A large amount of this code was pulled from https://github.com/wp-cli/wp-cli
 *
 * Examples:
 * activate plugin where name is jetpack
 *
 * @package  wpinstructions
 */

namespace WPInstructions\InstructionTypes;

use WPInstructions\InstructionType;
use WPInstructions\Log;
use WPInstructions\PackageManager;

/**
 * Activate plugin class
 */
class ActivatePlugin extends InstructionType {
	/**
	 * Instruction type defaults
	 *
	 * @var array
	 */
	protected $defaults = [
		'active type' => '',
		'plugin name' => '',
	];

	/**
	 * Plugin action
	 *
	 * @var string
	 */
	protected $action = 'activate plugin';

	/**
	 * Map instruction type subject
	 *
	 * @param  string $subject Subject
	 * @return string
	 */
	protected function mapSubject( string $subject ) {
		switch ( $subject ) {
			case 'name':
			case 'slug':
			case 'plugin slug':
			case 'plugin':
			case 'plugin title':
				return 'plugin name';
			case 'type':
				return 'active type';
		}

		return $subject;
	}

	/**
	 * Map instruction type object
	 *
	 * @param  string $object Object
	 * @return string
	 */
	protected function mapObject( string $object ) {
		switch ( $subject ) {
			case 'network activate':
			case 'network active':
				return 'network';
		}

		return $object;
	}

	/**
	 * Execute activate plugin code
	 *
	 * @param  array $options     Prepared options
	 * @param  array $global_args Global instructions args
	 * @return integer
	 */
	public function run( array $options, array $global_args = [] ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$plugin = PackageManager::instance()->get_plugin_by_slug( $options['plugin name'] );

		if ( empty( $plugin ) ) {
			Log::instance()->write( 'Plugin does not exist.', 0, 'error' );

			return 1;
		}

		if ( 'network' === $options['active type'] ) {
			$network_wide = 'network active' === $options['plugin status'];
		}

		$result = activate_plugin( $plugin['path'], '', $network_wide );

		if ( is_wp_error( $result ) ) {
			Log::instance()->write( 'Could not activate plugin `' . $options['plugin name'] . '`', 0, 'error' );
			Log::instance()->write( 'Error message: ' . $result->get_error_message(), 1 );

			return 1;
		}

		Log::instance()->write( 'Plugin `' . $options['plugin name'] . '` activated.' );

		return 0;
	}
}
