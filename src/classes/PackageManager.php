<?php
/**
 * WP Package manager tools
 *
 * @package wpinstructions
 */

namespace WPInstructions;

/**
 * Package manager class
 */
class PackageManager {
	/**
	 * Plugin upgrader class
	 *
	 * @var \Plugin_Upgrader
	 */
	public $plugin_upgrader;

	/**
	 * Theme upgrader class
	 *
	 * @var \Theme_Upgrader
	 */
	public $theme_upgrader;

	/**
	 * Setup package manager
	 */
	protected function setup() {
		$this->plugin_upgrader = new \Plugin_Upgrader( new UpgraderSkin() );
		$this->theme_upgrader  = new \Theme_Upgrader( new UpgraderSkin() );
	}

	/**
	 * Get plugin by slug
	 *
	 * @param  string $plugin_slug Plugin slug
	 * @return array
	 */
	public function get_plugin_by_slug( string $plugin_slug ) {
		if ( empty( $plugin_slug ) ) {
			return null;
		}

		wp_cache_delete( 'plugins', 'plugins' );

		$plugins = get_plugins();

		foreach ( $plugins as $plugin_path => $plugin ) {
			if ( preg_match( '#' . $plugin_slug . '/#i', $plugin_path ) || preg_match( '#' . $plugin_slug . '\.php$#i', $plugin_path ) ) {
				$plugin['path'] = $plugin_path;
				return $plugin;
			}
		}

		return null;
	}

	/**
	 * Get theme by slug
	 *
	 * @param  string $theme_slug Theme slug
	 * @return \WP_Theme
	 */
	public function get_theme_by_slug( string $theme_slug ) {
		wp_cache_delete( 'theme-' . md5( get_theme_root() . '/' . $theme_slug ), 'themes' );

		return wp_get_theme( $theme_slug );
	}

	/**
	 * Prepare an API response for downloading a particular version of an item.
	 *
	 * @param object $response wordpress.org API response
	 * @param string $version The desired version of the package
	 */
	protected function alter_api_response( $response, $version ) {
		if ( $response->version === $version ) {
			return;
		}

		// WordPress.org forces https, but still sometimes returns http
		// See https://twitter.com/nacin/status/512362694205140992
		$response->download_link = str_replace( 'http://', 'https://', $response->download_link );

		list( $link ) = explode( $response->slug, $response->download_link );

		if ( false !== strpos( $response->download_link, '/theme/' ) ) {
			$download_type = 'theme';
		} elseif ( false !== strpos( $response->download_link, '/plugin/' ) ) {
			$download_type = 'plugin';
		} else {
			$download_type = 'plugin/theme';
		}

		if ( 'dev' === $version ) {
			$response->download_link = $link . $response->slug . '.zip';
			$response->version       = 'Development Version';
		} else {
			$response->download_link = $link . $response->slug . '.' . $version . '.zip';
			$response->version       = $version;

			// check if the requested version exists
			$response = wp_remote_head( $response->download_link );

			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== (int) $response_code ) {
				if ( is_wp_error( $response ) ) {
					$error_msg = $response->get_error_message();
				} else {
					$error_msg = sprintf( 'HTTP code %d', $response_code );
				}

				Log::instance()->write( sprintf( "Can't find the requested %s's version %s in the WordPress.org %s repository (%s).", $download_type, $version, $download_type, $error_msg ) );
			}
		}
	}

	/**
	 * Install plugin from wordpress.org
	 *
	 * @param  string $slug    Plugin slug
	 * @param  string $version Plugin version
	 * @return \WP_Error|boolean
	 */
	public function install_plugin_from_org( string $slug, $version = 'trunk' ) {
		$api = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		if ( 'trunk' !== $version ) {
			$this->alter_api_response( $api, $version );
		}

		$status = install_plugin_install_status( $api );

		if ( 'install' === $status['status'] ) {
			return $this->plugin_upgrader->install( $api->download_link );
		} elseif ( 'newer_installed' === $status['status'] || 'update_available' === $status['status'] ) {
			exec( 'rm -rf ' . WP_PLUGIN_DIR . '/' . $api->slug );

			return $this->plugin_upgrader->install( $api->download_link );
		}

		return true;
	}

	/**
	 * Install theme from wordpress.org
	 *
	 * @param  string $slug    Plugin slug
	 * @param  string $version Plugin version
	 * @return \WP_Error|boolean
	 */
	public function install_theme_from_org( string $slug, $version = 'trunk' ) {
		$api = themes_api( 'theme_information', array( 'slug' => $slug ) );

		if ( is_wp_error( $api ) ) {
			return $api;
		}

		$latest_version = $api->version;

		$theme = wp_get_theme( $slug );

		if ( 'trunk' === $version ) {
			$version = $api->version;
		}

		if ( $theme->exists() ) {
			if ( $theme->version !== $version ) {
				exec( 'rm -rf ' . get_theme_root() . '/' . $theme->stylesheet );
			} else {
				Log::instance()->write( 'Theme already exists.', 1 );

				return false;
			}
		}

		if ( 'trunk' !== $version ) {
			$this->alter_api_response( $api, $version );
		}

		return $this->theme_upgrader->install( $api->download_link );
	}

	/**
	 * Return singleton instance of class
	 *
	 * @return object
	 */
	public static function instance() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}
