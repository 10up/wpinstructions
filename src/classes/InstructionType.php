<?php
/**
 * Instruction type class. Should be extended to register an instruction type.
 *
 * Each instruction type has a unique action as well as subject, verbs, and objects
 *
 * @package  wpinstructions
 */

namespace WPInstructions;

/**
 * Abstract class for instruction type
 */
abstract class InstructionType {

	/**
	 * Require WP installed. Also bootstraps WP for command starts
	 *
	 * @var boolean
	 */
	protected $require_wp = true;

	/**
	 * Called when instruction is run. This contains all the instruction type specific code.
	 *
	 * @param  array $options     Instruction options
	 * @param  array $global_args Global instructions args
	 * @return int Where 0 is success, 1 is error, 2 is skipped
	 */
	abstract public function run( array $options, array $global_args = [] );

	/**
	 * Map subjects to canonicals
	 *
	 * @param  string $subject Subject to map
	 * @return string
	 */
	protected function mapSubject( string $subject ) {
		return $subject;
	}

	/**
	 * Map objects to canonicals
	 *
	 * @param  string $object Object to map
	 * @return string
	 */
	protected function mapObject( string $object ) {
		return $object;
	}

	/**
	 * Return the action
	 *
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Return require WP
	 *
	 * @return boolean
	 */
	public function getRequireWordPress() {
		return $this->require_wp;
	}

	/**
	 * Map verbs to canonicals
	 *
	 * @param  string $verb Verb to map
	 * @return string
	 */
	protected function mapVerb( string $verb ) {
		switch ( $verb ) {

			case 'equals':
			case 'equal':
			case 'is':
				return '=';
		}

		return $verb;
	}

	/**
	 * Prepare readable options from instruction
	 *
	 * @param  array $options Options to prepare
	 * @return array
	 */
	public function prepareOptions( array $options ) {
		$prepared_options = $this->defaults;

		foreach ( $options as $option ) {
			$verb = $this->mapVerb( $option['verb'] );

			if ( '=' === $verb ) {
				$prepared_options[ $this->mapSubject( $option['subject'] ) ] = $this->mapObject( $option['object'] );
			}
		}

		return $prepared_options;
	}
}
