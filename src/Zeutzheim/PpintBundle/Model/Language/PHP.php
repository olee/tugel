<?php

namespace Zeutzheim\PpintBundle\Model\Language;

use Zeutzheim\PpintBundle\Model\Language;

class PHP extends Language {
		
	public function analyzeProvide($src) {
		preg_match_all('@(?:^|\\s)namespace\\s+\\\\?([a-zA-Z_][\\da-zA-Z\\\\_]*)\\s*;@', $src, $matches);
		$namespaces = array_unique($matches[1]);
		
		preg_match_all('@(?:^|\\s)class\\s+([a-zA-Z][\\da-zA-Z_]*)[\\s\\{]@', $src, $matches);
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
		preg_match_all('@(?:^|\\s)use\\s+\\\\?([a-zA-Z_][\\da-zA-Z\\\\_]*)\\s*;@', $src, $matches);
		$namespaces = array_unique($matches[1]);
		
		preg_match_all('@[^\\w]new\\s+\\\\?([a-zA-Z][\\da-zA-Z_]*)[\\s\\(]@', $src, $matches);
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
		return 'PHP';
	}
	
	public function getExtension() {
		return '.php';
	}

}
