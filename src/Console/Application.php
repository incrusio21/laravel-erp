<?php namespace LaravelErp\Console;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

class Application extends \Illuminate\Console\Application {

	/**
	 * Get the default input definitions for the applications.
	 *
	 * @return \Symfony\Component\Console\Input\InputDefinition
	 */
	protected function getDefaultInputDefinition(): InputDefinition
	{
		$definition = parent::getDefaultInputDefinition();

		$definition->addOption($this->getSetOption());

		return $definition;
	}

	/**
	 * Get the global environment option for the definition.
	 *
	 * @return \Symfony\Component\Console\Input\InputOption
	 */
	protected function getSetOption()
	{
		$message = 'The site the command should run under.';

		return new InputOption('--site', null, InputOption::VALUE_REQUIRED, $message);
	}


}