<?php
/**
 * Bootstrap WP Instructions
 *
 * @package  wpinstructions
 */

namespace WPInstructions;

use \Symfony\Component\Console\Application;

$app = new Application( 'WP Instructions', '1.1' );

define( 'WPINSTRUCTIONS_DIR', dirname( __DIR__ ) );

/**
 * Attempt to set this as the application can consume a lot of memory.
 */
ini_set( 'memory_limit', '-1' );

Instruction::registerInstructionType( new InstructionTypes\InstallWordPress() );
Instruction::registerInstructionType( new InstructionTypes\InstallPlugin() );
Instruction::registerInstructionType( new InstructionTypes\InstallTheme() );
Instruction::registerInstructionType( new InstructionTypes\AddSite() );
Instruction::registerInstructionType( new InstructionTypes\ActivatePlugin() );
Instruction::registerInstructionType( new InstructionTypes\EnableTheme() );

$app->add( new Command\Run() );

$app->run();
