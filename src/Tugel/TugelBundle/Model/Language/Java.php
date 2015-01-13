<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Util\Utils;

class Java extends Language {
		
	public function analyzeProvide($src) {
		$tags = array();
		$tags2 = array();
		
		preg_match_all('@(?:^|\\s)package\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][^\\s;]*)(?:\\s|;)@', $src, $matches);
		$namespaces = array();
		foreach ($matches[1] as $namespace) {
			Utils::array_add($tags2, $namespace);
			Utils::array_add($namespaces, $namespace);
			preg_match_all(Utils::CAMEL_CASE_PATTERN, $namespace, $matches);
			foreach ($matches[0] as $tag)
				Utils::array_add($tags, $tag);
		}
		$ns = count($namespaces) == 1 ? key($namespaces) . '.' : '';
		
		preg_match_all('@(?<!private)\\s+class\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)(?:\\s|\\{)@', $src, $matches);
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
		
	public function analyzeUse($src) {
		preg_match_all('@(?:^|\\s)import\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)\\s*;@', $src, $matches);
		$namespaces = array();
		foreach ($matches[1] as $namespace)
			$namespaces[$namespace] = 1;
		$ns = count($namespaces) == 1 ? key($namespaces) . '.' : '';
		
		preg_match_all('@[^\\w]new\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)(?:\\s|\\()@', $src, $matches);
		$classes = array();
		foreach ($matches[1] as $class)
			$classes[$ns . $class] = 1;
		
		return array(
			'namespace' => $namespaces,
			'class' => $classes,
		);
	}
	
	public function getName() {
		return 'Java';
	}
	
	public function getExtension() {
		return '.java';
	}

}
