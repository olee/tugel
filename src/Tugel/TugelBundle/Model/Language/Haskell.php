<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Util\Utils;

class Haskell extends Language {
		
	public function analyzeProvide($path, $file) {
		$src = file_get_contents($path . $file);
		$tags = array();
		$tags2 = array();
		
		preg_match_all('@(?:^|\\s)module\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		$namespaces = array();
		foreach ($matches[1] as $namespace) {
			Utils::array_add($tags2, $namespace);
			Utils::array_add($namespaces, $namespace);
			preg_match_all(Utils::CAMEL_CASE_PATTERN, $namespace, $matches);
			foreach ($matches[0] as $tag)
				Utils::array_add($tags, $tag);
		}
		$ns = count($namespaces) == 1 ? key($namespaces) . '.' : '';
		
		preg_match_all('@(?:^|\\s)instance\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)\\s@', $src, $matches);
		$classes = array();
		foreach ($matches[1] as $class) {
			Utils::array_add($tags2, $class);
			Utils::array_add($classes, $ns . $class);
			preg_match_all(Utils::CAMEL_CASE_PATTERN, $class, $matches);
			foreach ($matches[0] as $tag)
				Utils::array_add($tags, $tag);
		}

		return array(
			'namespace' => $namespaces,
			'class' => $classes,
			'tag' => $tags,
			'tag2' => $tags2,
		);
	}
		
	public function analyzeUse($path, $file) {
		$src = file_get_contents($path . $file);
		preg_match_all('@(?:^|\\s)import\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		$namespaces = array();
		foreach ($matches[1] as $namespace)
			$namespaces[$namespace] = 1;
		$ns = count($namespaces) == 1 ? key($namespaces) . '.' : '';
		
		//preg_match_all('@[^\\w]new\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)(?:\\s|\\()@', $src, $matches);
		$matches = array(array());
		$classes = array();
		foreach ($matches[1] as $class)
			$classes[$ns . $class] = 1;
		
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
