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
	 * Array of instructions
	 *
	 * @var instructions
	 */
	protected $instructions = [];

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
	 * Build instructions array
	 */
	public function prepare() {
		$lines = explode( "\n", $this->text );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( ! empty( $line ) ) {
				if ( '#' === substr( $line, 0, 1 ) ) {
					continue;
				}

				$this->instructions[] = Instruction::createFromText( $line, $this->global_args );
			}
		}
	}

	/**
	 * Run all instructions
	 *
	 * @return boolean True is success
	 */
	public function runAll() {

		foreach ( $this->instructions as $instruction ) {
			$instruction->prepare();

			$instruction_result = $instruction->run();

			if ( 1 === $instruction_result ) {
				Log::instance()->write( 'Instruction `' . $line . '` did not complete successfully.', 0, 'error' );

				return false;
			} elseif ( 2 === $instruction_result ) {
				Log::instance()->write( 'Instruction `' . $line . '` was skipped.', 0, 'warning' );
			}
		}

		return true;
	}

	/**
	 * Get Instructions
	 *
	 * @return array
	 */
	public function getInstructions() {
		return $this->instructions;
	}
}
