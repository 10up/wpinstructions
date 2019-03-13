<?php
/**
 * Install plugin instruction type
 *
 * A large amount of this code was pulled from https://github.com/wp-cli/wp-cli
 *
 * Examples:
 * install plugin where name is jetpack
 *
 * @package  wpinstructions
 */

namespace WPInstructions\InstructionTypes;

use WPInstructions\InstructionType;
use WPInstructions\Log;
use WPInstructions\PackageManager;

/**
 * Install plugin class
 */
class InstallPlugin extends InstructionType {
	/**
	 * Instruction type defaults
	 *
	 * @var array
	 */
	protected $defaults = [
		'plugin version' => 'latest',
		'plugin status'  => 'active',
		'plugin name'    => '',
		'plugin url'     => '',
	];

	/**
	 * Plugin action
	 *
	 * @var string
	 */
	protected $action = 'install plugin';

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
			case 'version':
				return 'plugin version';
			case 'status':
				return 'plugin status';
			case 'url':
			case 'website':
			case 'address':
			case 'plugin website':
				return 'plugin url';
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
			case 'network':
				return 'network active';
			case 'activate':
			case 'site active':
			case 'activated':
			case 'enable':
			case 'enabled':
				return 'active';
		}

		return $object;
	}

	/**
	 * Execute install plugin code
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

		if ( empty( $options['plugin name'] ) ) {
			Log::instance()->write( 'Plugin name is required.', 0, 'error' );

			return 1;
		}

		// Check if a URL to a remote or local zip has been specified
		if ( ! empty( $options['plugin url'] ) ) {
			$plugin = PackageManager::instance()->get_plugin_by_slug( $options['plugin name'] );

			if ( ! empty( $plugin ) ) {
				Log::instance()->write( 'Removed old version of the plugin.', 1 );

				exec( 'rm -rf ' . WP_PLUGIN_DIR . '/' . dirname( $plugin['path'] ) );
			}

			if ( preg_match( '#\.zip$#', $options['plugin url'] ) ) {
				\WP_Filesystem();

				$file = download_url( $options['plugin url'] );

				if ( is_wp_error( $file ) ) {
					Log::instance()->write( 'Could not install plugin.', 0, 'error' );

					return 1;
				}

				unzip_file( $file, WP_PLUGIN_DIR );

				$plugin = PackageManager::instance()->get_plugin_by_slug( $options['plugin name'] );
			} elseif ( preg_match( '#\.git$#', $options['plugin url'] ) ) {
				$result = exec( 'cd ' . WP_PLUGIN_DIR . ' && git clone ' . $options['plugin url'] );

				$plugin = PackageManager::instance()->get_plugin_by_slug( $options['plugin name'] );
			} else {
				Log::instance()->write( 'Invalid plugin URL. Try a zip or git file.', 0, 'error' );

				return 1;
			}
		} else {
			$version = ! ( empty( $options['plugin version'] ) ) ? $options['plugin version'] : 'trunk';

			if ( 'latest' === $version ) {
				$version = 'trunk';
			}

			$result = PackageManager::instance()->install_from_org( $options['plugin name'], $version );

			if ( is_null( $result ) || is_wp_error( $result ) ) {
				Log::instance()->write( 'Could not install plugin.', 0, 'error' );
			} else {
				Log::instance()->write( 'Plugin `' . $options['plugin name'] . '` installed.' );
			}

			if ( is_wp_error( $result ) ) {
				return 1;
			}

			$plugin = PackageManager::instance()->get_plugin_by_slug( $options['plugin name'] );
		}

		if ( empty( $plugin ) ) {
			Log::instance()->write( 'Could not install plugin.', 0, 'error' );

			return 1;
		}

		if ( 'active' === $options['plugin status'] || 'network active' === $options['plugin status'] ) {
			$network_wide = 'network active' === $options['plugin status'];

			$result = activate_plugin( $plugin['path'], '', $network_wide );

			if ( is_wp_error( $result ) ) {
				Log::instance()->write( 'Could not activate plugin `' . $options['plugin name'] . '`', 0, 'error' );
				Log::instance()->write( 'Error message: ' . $result->get_error_message(), 1 );

				return 1;
			}

			Log::instance()->write( 'Plugin `' . $options['plugin name'] . '` activated.' );
		}

		return 0;
	}
}
