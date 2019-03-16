<?php
/**
 * A single instruction
 *
 * @package  wpinstructions
 */

namespace WPInstructions;

use Exception\InstructionTypeInvalid;
use WPSnapshots;
use WPInstructions\Utils;

/**
 * Class created for each instruction
 */
class Instruction {
	/**
	 * Registered instruction types
	 *
	 * @var array
	 */
	public static $registered_instruction_types = [];

	/**
	 * Global args for all instructions
	 *
	 * @var array
	 */
	protected $global_args = [];

	/**
	 * Instruction action e.g. install wordpress
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * Instruction options
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Instruction raw text
	 *
	 * @var string
	 */
	protected $text;

	/**
	 * Prepared options
	 *
	 * @var array
	 */
	protected $prepared_options;

	/**
	 * Register an instruction type
	 *
	 * @param  InstructionType $instruction_type Instruction type
	 */
	public static function registerInstructionType( InstructionType $instruction_type ) {
		self::$registered_instruction_types[ $instruction_type->getAction() ] = $instruction_type;
	}

	/**
	 * Create an Instruction object from raw text
	 *
	 * @param  string $text        Instruction text
	 * @param  array  $global_args Global instruction args
	 * @return Instruction
	 */
	public static function createFromText( string $text, array $global_args = [] ) {
		$parts = self::parseInstructionParts( $text );

		return new self( $parts['action'], $parts['options'], $text, $global_args );
	}

	/**
	 * Parse out all parts of the instruction
	 *
	 * @param  string $text Instruction text
	 * @return array
	 */
	public static function parseInstructionParts( string $text ) {
		Log::instance()->write( 'Parsing instruction parts...', 2 );

		$text = trim( $text );
		$text = preg_replace( '#[\s]+#', ' ', $text );

		$parts = [];

		$raw_action = preg_replace( '#where.*#i', '', $text );

		$parts['action']  = trim( strtolower( $raw_action ) );
		$parts['options'] = [];

		$where_clause = str_replace( $raw_action, '', $text );

		if ( ! empty( $where_clause ) ) {
			$where_clause = trim( preg_replace( '#^[\s]*where(.*)$#i', '$1', $where_clause ) );

			$clauses = preg_split( '#and#i', $where_clause );

			foreach ( $clauses as $clause ) {
				$clause = trim( $clause );

				$possible_verbs = [
					'is',
					'equals',
					'=',
				];

				$matches = [];

				preg_match_all( '#^(.*?)(' . implode( '|', $possible_verbs ) . ')(.*)$#', $clause, $matches, PREG_PATTERN_ORDER );

				if ( 4 === count( $matches ) ) {
					$parts['options'][] = [
						'subject' => strtolower( trim( $matches[1][0] ) ),
						'verb'    => strtolower( trim( $matches[2][0] ) ),
						'object'  => trim( $matches[3][0] ),
					];
				}
			}
		}

		Log::instance()->write( 'Instruction properties:', 2 );
		Log::instance()->write( print_r( $parts, true ), 2 );

		return $parts;
	}

	/**
	 * Create instruction
	 *
	 * @param string $action      Instruction action
	 * @param array  $options     Instruction options
	 * @param string $text        Raw instruction text
	 * @param array  $global_args Global instruction args
	 */
	public function __construct( string $action, array $options, string $text, array $global_args = [] ) {
		$this->action      = $action;
		$this->options     = $options;
		$this->text        = $text;
		$this->global_args = $global_args;
	}

	/**
	 * Prepare instruction to be ran
	 *
	 * @throws InstructionTypeInvalid Instruction type not registered
	 */
	public function prepare() {
		if ( empty( self::$registered_instruction_types[ $this->action ] ) ) {
			throw new InstructionTypeInvalid();
		}

		$instruction_type = self::$registered_instruction_types[ $this->action ];

		$this->prepared_options = $instruction_type->prepareOptions( $this->options );

		Log::instance()->write( 'Prepared options:', 2 );
		Log::instance()->write( print_r( $this->prepared_options, true ), 2 );
	}

	/**
	 * Run the instruction
	 *
	 * @return integer 0 means success
	 */
	public function run() {
		Log::instance()->write( 'Running instruction with action ' . $this->action, 1 );

		$instruction_type = self::$registered_instruction_types[ $this->action ];

		if ( ! WordPressBridge::instance()->isLoaded() && $instruction_type->getRequireWordPress() ) {
			Log::instance()->write( 'Bootstrapping WP before command...', 2 );

			$extras = [];

			if ( ! empty( $global_args['db_host'] ) ) {
				$extras['DB_HOST'] = $global_args['db_host'];
			}

			if ( 0 !== WordPressBridge::instance()->load( $this->global_args['path'], $extras ) ) {
				return 1;
			}
		}

		return self::$registered_instruction_types[ $this->action ]->run( $this->prepared_options, $this->global_args );
	}

	/**
	 * Return instruction text
	 *
	 * @return  string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Return instruction options
	 *
	 * @return  array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Return instruction prepared options
	 *
	 * @return  array
	 */
	public function getPreparedOptions() {
		return $this->prepared_options;
	}

	/**
	 * Return instruction action
	 *
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Return instruction global args
	 *
	 * @return array
	 */
	public function getGlobalArgs() {
		return $this->global_args;
	}
}
