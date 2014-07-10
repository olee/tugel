<?php

namespace Zeutzheim\PpintBundle\Model\Language;

use Zeutzheim\PpintBundle\Model\Language;

class Haskell extends Language {
		
	public function analyzeProvide($src) {
		preg_match_all('@(?:^|\\s)module\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		$namespaces = array_unique($matches[1]);
		
		preg_match_all('@(?:^|\\s)instance\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)\\s@', $src, $matches);
		$classes = array_unique($matches[1]);
		if (count($namespaces) == 1)
			foreach ($classes as &$value)
				$value = $namespaces[0] . $value; 
		
		return array(
			'namespace' => $namespaces,
			'class' => $classes,
		);
	}
		
	public function analyzeUse($src) {
		preg_match_all('@(?:^|\\s)import\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		$namespaces = array_unique($matches[1]);
		
		//preg_match_all('@[^\\w]new\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)(?:\\s|\\()@', $src, $matches);
		$matches = array(array());
		$classes = array_unique($matches[1]);
		if (count($namespaces) == 1)
			foreach ($classes as &$value)
				$value = $namespaces[0] . $value; 
		
		return array(
			'namespace' => $namespaces,
			'class' => $classes,
		);
	}
	
	public function getName() {
		return 'Haskell';
	}
	
	public function getExtension() {
		return '.hs';
	}

}
