<?php
/**
 * Install theme instruction type
 *
 * A large amount of this code was pulled from https://github.com/wp-cli/wp-cli
 *
 * Examples:
 * install theme where name is twentynineteen
 *
 * @package  wpinstructions
 */

namespace WPInstructions\InstructionTypes;

use WPInstructions\InstructionType;
use WPInstructions\PackageManager;
use WPInstructions\Log;

/**
 * Install theme class
 */
class InstallTheme extends InstructionType {
	/**
	 * Instruction type defaults
	 *
	 * @var array
	 */
	protected $defaults = [
		'theme version' => 'latest',
		'theme status'  => 'enabled',
		'theme url'     => '',
		'theme name'    => '',
	];

	/**
	 * Instruction action
	 *
	 * @var string
	 */
	protected $action = 'install theme';

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
			case 'theme slug':
			case 'theme title':
			case 'theme':
				return 'theme name';
			case 'version':
				return 'theme version';
			case 'status':
				return 'theme status';
			case 'url':
				return 'theme url';
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
			case 'activate':
			case 'activated':
			case 'active':
			case 'enable':
			case 'site active':
				return 'enabled';
		}

		return $object;
	}

	/**
	 * Execute install theme code
	 *
	 * @param  array $options     Prepared options
	 * @param  array $global_args Global instructions args
	 * @return integer
	 */
	public function run( array $options, array $global_args = [] ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( empty( $options['theme name'] ) ) {
			Log::instance()->write( 'Theme name is required.', 0, 'error' );

			return 1;
		}

		// Check if a URL to a remote or local zip has been specified
		if ( ! empty( $options['theme url'] ) ) {
			$theme = PackageManager::instance()->get_theme_by_slug( $options['theme name'] );

			if ( $theme->exists() ) {
				Log::instance()->write( 'Removed old version of the theme.', 1 );

				exec( 'rm -rf ' . get_theme_root() . '/' . $theme->stylesheet );
			}

			if ( preg_match( '#\.zip$#', $options['theme url'] ) ) {
				\WP_Filesystem();

				$file = download_url( $options['theme url'] );

				if ( is_wp_error( $file ) ) {
					Log::instance()->write( 'Could not install theme.', 0, 'error' );

					return 1;
				}

				unzip_file( $file, get_theme_root() );

				$theme = PackageManager::instance()->get_theme_by_slug( $options['theme name'] );
			} elseif ( preg_match( '#\.git$#', $options['theme url'] ) ) {
				exec( 'cd ' . get_theme_root() . ' && git clone ' . $options['theme url'] );

				$theme = PackageManager::instance()->get_theme_by_slug( $options['theme name'] );
			} else {
				Log::instance()->write( 'Invalid theme URL. Try a zip or git file.', 0, 'error' );

				return 1;
			}
		} else {
			$version = ! ( empty( $options['theme version'] ) ) ? $options['theme version'] : 'trunk';

			if ( 'latest' === $version ) {
				$version = 'trunk';
			}

			$result = PackageManager::instance()->install_theme_from_org( $options['theme name'], $version );

			if ( is_null( $result ) || is_wp_error( $result ) ) {
				Log::instance()->write( 'Could not install theme.', 0, 'error' );
			} else {
				Log::instance()->write( 'Theme `' . $options['theme name'] . '` installed.' );
			}

			if ( is_wp_error( $result ) ) {
				return 1;
			}

			$theme = PackageManager::instance()->get_theme_by_slug( $options['theme name'] );
		}

		if ( ! $theme->exists() ) {
			Log::instance()->write( 'Could not install theme.', 0, 'error' );

			return 1;
		}

		if ( 'enabled' === $options['theme status'] ) {
			if ( function_exists( 'get_site_option' ) ) {
				$allowed_themes = get_site_option( 'allowedthemes' );

				if ( empty( $allowed_themes ) ) {
					$allowed_themes = [];
				}

				$allowed_themes[ $theme->get_stylesheet() ] = true;

				update_site_option( 'allowedthemes', $allowed_themes );
			}

			switch_theme( $theme->get_template(), $theme->get_stylesheet() );

			if ( $theme->get_stylesheet_directory() !== get_stylesheet_directory() ) {
				Log::instance()->write( 'Could not enable theme `' . $options['theme name'] . '`', 0, 'error' );

				return 1;
			}

			Log::instance()->write( 'Theme `' . $options['theme name'] . '` enabled.' );
		}

		return 0;
	}
}
