<?php
/**
 * Install WordPress instruction type
 *
 * Example:
 * install wordpress where version is latest and site title is Test Site and admin user is admin and
 * admin email is test@test.com and install type is single site and site url is http://localhost and
 * home url is http://localhost
 *
 * @package  wpinstructions
 */

namespace WPInstructions\InstructionTypes;

use WPInstructions\InstructionType;
use WPInstructions\Log;
use WPInstructions\Utils;
use WPInstructions\WordPressBridge;
use Symfony\Component\Process\Process;
use WPSnapshots;

use Requests;

/**
 * Install wordpress instruction type class
 */
class InstallWordPress extends InstructionType {
	/**
	 * Dont require WP
	 *
	 * @var boolean
	 */
	protected $require_wp = false;

	/**
	 * Instruction type action
	 *
	 * @var string
	 */
	protected $action = 'install wordpress';

	/**
	 * Instruction type defaults
	 *
	 * @var array
	 */
	protected $defaults = [
		'version'        => 'latest',
		'site title'     => 'Test Site',
		'site url'       => 'http://localhost',
		'home url'       => 'http://localhost',
		'admin email'    => 'test@test.com',
		'admin user'     => 'admin',
		'admin password' => 'password',
		'install type'   => 'single site',
		'path'           => '',
	];

	/**
	 * Map instruction type subject
	 *
	 * @param  string $subject Subject
	 * @return string
	 */
	protected function mapSubject( string $subject ) {
		switch ( $subject ) {
			case 'wp version':
			case 'wordpress version':
				return 'version';
			case 'user':
				return 'admin user';
			case 'email':
			case 'user email':
				return 'admin email';
			case 'password':
			case 'pass':
				return 'admin password';
			case 'title':
			case 'blog title':
				return 'site title';
			case 'home':
				return 'home url';
			case 'site':
				return 'site url';
			case 'type':
				return 'install type';
		}

		return $subject;
	}

	/**
	 * Execute install WP code
	 *
	 * @param  array $options     Prepared options
	 * @param  array $global_args Global instructions args
	 * @return int
	 */
	public function run( array $options, array $global_args = [] ) {
		Log::instance()->write( 'Downloading WordPress...', 1 );

		$skip_wpcontent = false;

		if ( WPSnapshots\Utils\is_wp_present( $global_args['path'] ) ) {
			$skip_wpcontent = true;

			Log::instance()->write( 'WordPress already exists. Removing old files first...', 1 );

			exec( 'cd ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . ' && rm -rf rm -rf index.php license.txt wp-mail.php wp-settings.php wp-activate.php	wp-cron.php wp-signup.php wp-trackback.php wp-blog-header.php wp-links-opml.php wp-comments-post.php wp-load.php wp-config-sample.php wp-login.php wp-includes wp-admin xmlrpc.php readme.html' );
		}

		$headers         = [ 'Accept' => 'application/json' ];
		$request_options = [
			'timeout'  => 600,
			'filename' => $global_args['path'] . 'wp.tar.gz',
		];

		$multisite = 'multisite' === $options['install type'];

		$download_url = WPSnapshots\Utils\get_download_url( $options['version'] );

		$request = Requests::get( $download_url, $headers, $request_options );

		Log::instance()->write( 'Extracting WordPress...', 1 );

		exec( 'rm -rf ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . 'wordpress && tar -C ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . ' -xf ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . 'wp.tar.gz > /dev/null' );

		Log::instance()->write( 'Moving WordPress files...', 1 );

		if ( $skip_wpcontent ) {
			exec( 'cd ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . ' && mv wordpress/wp-*.php wordpress/wp-includes wordpress/wp-admin wordpress/index.php wordpress/xmlrpc.php .' );
		} else {
			exec( 'mv ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . 'wordpress/* .' );
		}

		Log::instance()->write( 'Removing temporary WordPress files...', 1 );
		exec( 'rm -rf ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . 'wordpress' );

		Log::instance()->write( 'Removing download package....', 1 );
		exec( 'rm -rf ' . WPSnapshots\Utils\escape_shell_path( $global_args['path'] ) . 'wp.tar.gz' );

		Log::instance()->write( 'WordPress downloaded.' );

		$config_constants = [
			'DB_HOST'  => 'localhost',
		];

		if ( ! empty( $global_args['config_db_host'] ) ) {
			$config_constants['DB_HOST'] = $global_args['config_db_host'];
		}

		if ( ! empty( $global_args['config_db_name'] ) ) {
			$config_constants['DB_NAME'] = $global_args['config_db_name'];
		}

		if ( ! empty( $global_args['config_db_user'] ) ) {
			$config_constants['DB_USER'] = $global_args['config_db_user'];
		}

		if ( ! empty( $global_args['config_db_password'] ) ) {
			$config_constants['DB_PASSWORD'] = $global_args['config_db_password'];
		}

		$wp_config_path = WPSnapshots\Utils\locate_wp_config( $global_args['path'] );

		if ( ! $wp_config_path ) {
			if ( empty( $config_constants['DB_HOST'] ) || empty( $config_constants['DB_USER'] ) || empty( $config_constants['DB_NAME'] ) || empty( $config_constants['DB_PASSWORD'] ) ) {
				Log::instance()->write( 'Database host, user, name, and password are required when installing WordPress with a new wp-config.php file.', 0, 'error' );

				return 1;
			}

			Log::instance()->write( 'Creating wp-config.php file...', 1 );
			WPSnapshots\Utils\create_config_file( $global_args['path'] . 'wp-config.php', $global_args['path'] . 'wp-config-sample.php', $config_constants );

			$wp_config_path = $global_args['path'] . 'wp-config.php';

			Log::instance()->write( 'wp-config.php created.' );
		} else {
			WPSnapshots\Utils\write_constants_to_wp_config( $config_constants, $wp_config_path );

			Log::instance()->write( 'wp-config.php updated.' );
		}

		$wp_config_constants = WPSnapshots\Utils\get_wp_config_constants( $wp_config_path );

		$db_host = ( ! empty( $global_args['db_host'] ) ) ? $global_args['db_host'] : $wp_config_constants['DB_HOST'];

		// Test DB connect
		$connection = WPSnapshots\Utils\test_mysql_connection( $db_host, $wp_config_constants['DB_NAME'], $wp_config_constants['DB_USER'], $wp_config_constants['DB_PASSWORD'] );

		if ( true !== $connection ) {
			if ( false !== strpos( $connection, 'php_network_getaddresses' ) ) {
				Log::instance()->write( "Couldn't connect to MySQL host.", 0, 'error' );
			} else {
				Log::instance()->write( 'Could not connect to MySQL. Is your connection info correct?', 0, 'error' );
				Log::instance()->write( 'MySQL error: ' . $connection, 1, 'error' );
			}

			Log::instance()->write( 'MySQL connection info:', 1 );
			Log::instance()->write( 'DB_HOST: ' . $db_host, 1 );
			Log::instance()->write( 'DB_NAME: ' . $wp_config_constants['DB_NAME'], 1 );
			Log::instance()->write( 'DB_USER: ' . $wp_config_constants['DB_USER'], 1 );
			Log::instance()->write( 'DB_PASSWORD: ' . $wp_config_constants['DB_PASSWORD'], 1 );

			return 1;
		}

		$table_prefix = Utils\get_table_prefix( $wp_config_path );

		$wp_installed = Utils\wp_tables_exist( $db_host, $wp_config_constants['DB_NAME'], $wp_config_constants['DB_USER'], $wp_config_constants['DB_PASSWORD'], $table_prefix );

		if ( $wp_installed ) {
			Log::instance()->write( 'WordPress already installed.', 1 );
		} else {
			$process = new Process( [ 'php', WPINSTRUCTIONS_DIR . '/src/subprocesses/installwp.php', $global_args['path'], $options['site title'], $options['admin user'], $options['admin email'], WPSnapshots\Utils\escape_shell_path( $options['admin password'] ) ] );
			$process->run();

			if ( $process->isSuccessful() ) {
				Log::instance()->write( 'WordPress installed.' );
			} else {
				Log::instance()->write( 'Failed to install WordPress.', 0, 'error' );

				return 1;
			}
		}

		$extras = [];

		if ( ! empty( $global_args['db_host'] ) ) {
			$extras['DB_HOST'] = $global_args['db_host'];
		}

		WordPressBridge::instance()->load( $global_args['path'], $extras );

		global $wpdb;

		if ( ! $wp_installed && $multisite ) {
			Log::instance()->write( 'Setting up multisite...', 1 );

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$domain = preg_replace( '|https?://|', '', $options['site url'] );

			if ( 'http://localhost' === $options['site url'] ) {
				$domain = 'localhost';
			} else {
				$domain = parse_url( $options['site url'], PHP_URL_HOST );
			}

			foreach ( $wpdb->tables( 'ms_global' ) as $table => $prefixed_table ) {
				$wpdb->$table = $prefixed_table;
			}

			install_network();

			$result = populate_network(
				1,
				$domain,
				$options['admin email'],
				$options['site title'],
				'/',
				false
			);

			if ( true === $result ) {
				Log::instance()->write( 'Set up multisite database tables.' );
			} elseif ( is_wp_error( $result ) ) {
				Log::instance()->write( 'Failed to install WordPress.', 0, 'error' );

				return 1;
			}

			// delete_site_option() cleans the alloptions cache to prevent dupe option
			delete_site_option( 'upload_space_check_disabled' );
			update_site_option( 'upload_space_check_disabled', 1 );

			$ms_constants = [
				'WP_ALLOW_MULTISITE'   => true,
				'MULTISITE'            => true,
				'SUBDOMAIN_INSTALL'    => false,
				'DOMAIN_CURRENT_SITE'  => '',
				'PATH_CURRENT_SITE'    => '/',
				'SITE_ID_CURRENT_SITE' => 1,
				'BLOG_ID_CURRENT_SITE' => 1,
			];

			WPSnapshots\Utils\write_constants_to_wp_config( $ms_constants, $wp_config_path );

			$site_user = get_user_by( 'email', $options['admin email'] );

			$this->add_site_admins( $site_user );

			update_site_option( 'siteurl', esc_url_raw( $options['site url'] ) );
			update_site_option( 'home', esc_url_raw( $options['home url'] ) );
		} else {
			update_option( 'siteurl', esc_url_raw( $options['site url'] ) );
			update_option( 'home', esc_url_raw( $options['home url'] ) );
		}

		return 0;
	}

	/**
	 * Add site admins to install
	 *
	 * @param WP_User $site_user User to add
	 */
	private function add_site_admins( WP_User $site_user ) {
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
}
