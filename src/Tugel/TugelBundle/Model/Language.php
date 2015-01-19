<?php

namespace Tugel\TugelBundle\Model;

use Monolog\Logger;

abstract class Language {

	/**
	 * @var Logger
	 */
	protected $logger;

	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	public function checkFilename($filename) {
		return !$filename || substr(strtolower($filename), -strlen($this->getExtension())) === $this->getExtension();
	}

	//*******************************************************************

	public abstract function analyzeProvide(Index $index, $path, $file);

	public abstract function analyzeUse(Index $index, $path, $file);

	public abstract function getName();

	public abstract function getExtension();

}
