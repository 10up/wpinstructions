<?php
/**
 * A Upgrader Skin for WordPress taht is silent
 *
 * @package wpinstructions
 */

namespace WPInstructions;

/**
 * Upgrader skin class
 */
class UpgraderSkin extends \WP_Upgrader_Skin {
	/**
	 * Store plugin API
	 *
	 * @var object
	 */
	public $api;

	/**
	 * Output nothing
	 */
	public function header() {}

	/**
	 * Output nothing
	 */
	public function footer() {}

	/**
	 * Output nothing
	 */
	public function bulk_header() {}

	/**
	 * Output nothing
	 */
	public function bulk_footer() {}

	/**
	 * Output an error
	 *
	 * @param  string|WP_Error $error Upgrader error
	 */
	public function error( $error ) {
		if ( ! $error ) {
			return;
		}

		if ( is_string( $error ) && isset( $this->upgrader->strings[ $error ] ) ) {
			$error = $this->upgrader->strings[ $error ];
		} elseif ( is_wp_error( $error ) ) {
			$error = $error->get_error_message();
		}

		Log::instance()->write( $error, 0, 'error' );
	}

	/**
	 * Output info on upgrading
	 *
	 * @param  string $string Message
	 */
	public function feedback( $string ) {
		Log::instance()->write( $string, 2 );
	}
}
