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
		$extensions = $this->getExtensions();
		if (!is_array($extensions))
			$extensions = array($extensions);
		foreach ($extensions as $ext) {
			if (substr(strtolower($filename), -strlen($ext)) === $ext)
				return true;
		}
		return false;
	}

	/*******************************************************************/

	public abstract function analyzeProvide(Index $index, $path, $file);

	public abstract function analyzeUse(Index $index, $path, $file);

	public abstract function getName();

	public abstract function getExtensions();

}
