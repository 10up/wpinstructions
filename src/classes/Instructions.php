<?php
/**
 * Run an instructions file
 *
 * @package  wpinstructions
 */

namespace WPInstructions;

/**
 * Instructions class
 */
class Instructions {
	/**
	 * Global arguments
	 *
	 * @var array
	 */
	protected $global_args = [];

	/**
	 * All instructions
	 *
	 * @var string
	 */
	protected $text;

	/**
	 * Create Instructions object
	 *
	 * @param string $text        All instructions text
	 * @param array  $global_args Global instructions args
	 */
	public function __construct( string $text, array $global_args = [] ) {
		$this->text        = $text;
		$this->global_args = $global_args;
	}

	/**
	 * Run all instructions
	 *
	 * @return boolean True is success
	 */
	public function runAll() {
		$lines = explode( "\n", $this->text );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( ! empty( $line ) ) {
				if ( '#' === substr( $line, 0, 1 ) ) {
					continue;
				}

				Log::instance()->write( 'Running instruction: ' . $line, 1 );

				$instruction_result = $this->run( $line );

				if ( 1 === $instruction_result ) {
					Log::instance()->write( 'Instruction `' . $line . '` did not complete successfully.', 0, 'error' );

					return false;
				} elseif ( 2 === $instruction_result ) {
					Log::instance()->write( 'Instruction `' . $line . '` was skipped.', 0, 'warning' );
				}
			}
		}

		return true;
	}

	/**
	 * Run a single instruction
	 *
	 * @param  string $text Raw instruction text
	 * @return integer
	 */
	public function run( string $text ) {
		$instruction = Instruction::createFromText( $text, $this->global_args );

		return $instruction->run();
	}
}
