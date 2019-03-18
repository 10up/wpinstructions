<?php
/**
 * Enable theme instruction type
 *
 * A large amount of this code was pulled from https://github.com/wp-cli/wp-cli
 *
 * Examples:
 * enable theme where name is twentyseventeen
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
class EnableTheme extends InstructionType {
	/**
	 * Instruction type defaults
	 *
	 * @var array
	 */
	protected $defaults = [
		'theme name' => '',
	];

	/**
	 * Plugin action
	 *
	 * @var string
	 */
	protected $action = 'enable theme';

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
			case 'theme':
			case 'theme title':
				return 'theme name';
		}

		return $subject;
	}

	/**
	 * Execute enable theme code
	 *
	 * @param  array $options     Prepared options
	 * @param  array $global_args Global instructions args
	 * @return integer
	 */
	public function run( array $options, array $global_args = [] ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$theme = PackageManager::instance()->get_theme_by_slug( $options['theme name'] );

		if ( ! $theme->exists() ) {
			Log::instance()->write( 'Theme not installed.', 0, 'error' );

			return 1;
		}

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

		return 0;
	}
}
