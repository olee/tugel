<?php

namespace Tugel\TugelBundle\Event;

use Monolog\Logger;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;

class ExceptionListener extends ContainerAwareHelperNT {

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * constructor
	 */
	public function __construct(Logger $logger) {
		$this->logger = $logger;
	}

	function logMessages($msgs) {
		foreach (explode("\n", $msgs) as $msg) {
			$this->logger->error($msg);
			echo $msg . "\n";
		}
	}

	public function onConsoleException(ConsoleExceptionEvent $event) {
		$e = $event->getException();
		$this->logMessages($this->jTraceEx($e));
		if ($e instanceof \Elastica\Exception\ResponseException) {
			$this->logMessages(print_r($e->getRequest()->toArray(), true));
			$this->logMessages(print_r($e->getResponse()->toArray(), true));
		}
	}

	function jTraceEx($e, $seen = null) {
		$starter = $seen ? 'Caused by: ' : '';
		$result = array();
		if (!$seen)
			$seen = array();
		$trace = $e->getTrace();
		$prev = $e->getPrevious();
		$result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
		$file = $e->getFile();
		$line = $e->getLine();
		while (true) {
			$current = "$file:$line";
			if (is_array($seen) && in_array($current, $seen)) {
				$result[] = sprintf(' ... %d more', count($trace) + 1);
				break;
			}
			$result[] = sprintf(' at %s%s%s(%s%s%s)', count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '', count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '', count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)', $line === null ? $file : basename($file), $line === null ? '' : ':', $line === null ? '' : $line);
			if (is_array($seen))
				$seen[] = "$file:$line";
			if (!count($trace))
				break;
			$file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
			$line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
			array_shift($trace);
		}
		$result = join("\n", $result);
		if ($prev)
			$result .= "\n" . jTraceEx($prev, $seen);

		return $result;
	}

}
