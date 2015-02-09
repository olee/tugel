<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Model\Index;
use Tugel\TugelBundle\Util\Utils;

class Haskell extends Language {
		
	public function analyzeProvide(Index $index, $path, $file) {
		$src = file_get_contents($path . $file);
		
		preg_match_all('/^\s*module\s+((?:[a-zA-Z_$][a-zA-Z\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\d_$]*)[^a-zA-Z\d_$\.]/m', $src, $matches);
		foreach ($matches[1] as $namespace) {
			$index->addNamespace($namespace);
		}
		
		preg_match_all('/^\s*(?:data|type|newtype)\s+([a-zA-Z_$][a-zA-Z\d_$]*)(?:\s+\w*)*=/m', $src, $matches);
		foreach ($matches[1] as $class) {
			$index->addClass($class);
		}
		
		preg_match_all('/^\s*class\s+(?:.*=>\s*)\(?([a-zA-Z_$][a-zA-Z\d_$]*)(?:\s+\(?\s*\w*\s*\)?)*where/m', $src, $matches);
		foreach ($matches[1] as $class) {
			$index->addClass($class);
		}
	}
		
	public function analyzeUse(Index $index, $path, $file) {
		$src = file_get_contents($path . $file);
		preg_match_all('@(?:^|\\s)import\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		$index['namespace'] = array();
		foreach ($matches[1] as $namespace)
			$index['namespace'][$namespace] = 1;
		
		//preg_match_all('@[^\\w]new\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)(?:\\s|\\()@', $src, $matches);
		$matches = array(array());
		$index['class'] = array();
		foreach ($matches[1] as $class)
			$index['class'][$class] = 1;
	}
	
	public function getName() {
		return 'Haskell';
	}
	
	public function getExtensions() {
		return '.hs';
	}

}
