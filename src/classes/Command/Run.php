<?php
/**
 * Run instructions
 *
 * @package wpacceptance
 */

namespace WPInstructions\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use WPInstructions\Log;
use WPInstructions\Instructions;

use WPSnapshots;

/**
 * Run command class
 */
class Run extends Command {

	/**
	 * Setup up command
	 */
	protected function configure(): void {
		$this->setName( 'run' );
		$this->setDescription( 'Run a set of instructions.' );

		$this->addOption( 'path', null, InputOption::VALUE_REQUIRED, 'Path to WordPress.' );
		$this->addOption( 'config_db_host', null, InputOption::VALUE_REQUIRED, 'Config database host. Used if installing WordPress.' );
		$this->addOption( 'config_db_name', null, InputOption::VALUE_REQUIRED, 'Config database name. Used if installing WordPress.' );
		$this->addOption( 'config_db_user', null, InputOption::VALUE_REQUIRED, 'Config database user. Used if installing WordPress.' );
		$this->addOption( 'config_db_password', null, InputOption::VALUE_REQUIRED, 'Config database password. Used if installing WordPress.' );

		$this->addOption( 'db_host', null, InputOption::VALUE_REQUIRED, 'Database host. Use this if you need a different DB host for connecting than what is in wp-config.php' );
	}

	/**
	 * Execute command
	 *
	 * @param  InputInterface  $input Console input
	 * @param  OutputInterface $output Console output
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		Log::instance()->setOutput( $output );

		$args = [
			'path' => getcwd(),
		];

		if ( ! empty( $input->getOption( 'path' ) ) ) {
			$args['path'] = $input->getOption( 'path' );
		}

		$args['path'] = WPSnapshots\Utils\normalize_path( $args['path'] );

		if ( ! empty( $input->getOption( 'config_db_host' ) ) ) {
			$args['config_db_host'] = $input->getOption( 'config_db_host' );
		}

		if ( ! empty( $input->getOption( 'db_host' ) ) ) {
			$args['db_host'] = $input->getOption( 'db_host' );
		}

		if ( ! empty( $input->getOption( 'config_db_name' ) ) ) {
			$args['config_db_name'] = $input->getOption( 'config_db_name' );
		}

		if ( ! empty( $input->getOption( 'config_db_user' ) ) ) {
			$args['config_db_user'] = $input->getOption( 'config_db_user' );
		}

		if ( ! empty( $input->getOption( 'config_db_password' ) ) ) {
			$args['config_db_password'] = $input->getOption( 'config_db_password' );
		}

		$instructions_text = file_get_contents( getcwd() . '/WPInstructions' );

		$instructions = new Instructions( $instructions_text, $args );
		$instructions->build();
		$instructions->runAll();

		Log::instance()->write( 'Done.', 0, 'success' );
	}

}
