<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Model\Index;
use Tugel\TugelBundle\Util\Utils;

class Python extends Language {
		
	public function analyzeProvide(Index $index, $path, $file) {
		$src = file_get_contents($path . $file);
		
		//preg_match_all('@(?:^|\\s)module\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		/*
		$namespaces = array();
		foreach ($matches[1] as $namespace) {
			Utils::array_add($index['namespace'], $namespace);
		}
		*/
		
		preg_match_all('@(?:^|\\n)\\s+class\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)@', $src, $matches);
		foreach ($matches[1] as $class) {
			$index->addClass($class);
		}
	}
		
	public function analyzeUse(Index $index, $path, $file) {
		$src = file_get_contents($path . $file);
		/*
		preg_match_all('@(?:^|\\s)import\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		$namespaces = array();
		foreach ($matches[1] as $namespace)
			$namespaces[$namespace] = 1;
		
		//preg_match_all('@[^\\w]new\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)(?:\\s|\\()@', $src, $matches);
		$matches = array(array());
		$classes = array();
		foreach ($matches[1] as $class)
			$classes[$class] = 1;
		
		return array(
			'namespace' => $namespaces,
			'class' => $classes,
		);
		*/
	}
	
	public function getName() {
		return 'Python';
	}
	
	public function getExtensions() {
		return '.py';
	}

}
