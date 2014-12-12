<?php

namespace Zeutzheim\PpintBundle\Model\Language;

use Zeutzheim\PpintBundle\Model\Language;
use Zeutzheim\PpintBundle\Util\Utils;

class Python extends Language {
		
	public function analyzeProvide($src) {
		$tags = array();
		
		//preg_match_all('@(?:^|\\s)module\\s+((?:[a-zA-Z_$][a-zA-Z\\d_$]*\\.)*[a-zA-Z_$][a-zA-Z\\d_$]*)[^a-zA-Z\\d_$\\.]@', $src, $matches);
		$namespaces = array();
		/*
		foreach ($matches[1] as $namespace) {
			$namespaces[$namespace] = 1;
			preg_match_all(Utils::CAMEL_CASE_PATTERN, $namespace, $matches);
			foreach ($matches[0] as $tag)
				Utils::array_add($tags, $tag);
		}
		*/
		$ns = count($namespaces) == 1 ? key($namespaces) . '.' : '';
		
		preg_match_all('@(?:^|\\n)\\s+class\\s+([a-zA-Z_$][a-zA-Z\\d_$]*)@', $src, $matches);
		$classes = array();
		foreach ($matches[1] as $class) {
			$classes[$ns . $class] = 1;
			preg_match_all(Utils::CAMEL_CASE_PATTERN, $class, $matches);
			foreach ($matches[0] as $tag)
				Utils::array_add($tags, $tag);
		}
		
		return array(
			'namespace' => $namespaces,
			'class' => $classes,
			'tag' => $tags,
		);
	}
		
	public function analyzeUse($src) {
		/*
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
		*/
	}
	
	public function getName() {
		return 'Python';
	}
	
	public function getExtension() {
		return '.py';
	}

}
