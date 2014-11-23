<?php

namespace Zeutzheim\PpintBundle\Model\Language;

use Zeutzheim\PpintBundle\Model\Language;
use Zeutzheim\PpintBundle\Util\Utils;

class PHP extends Language {

	public function analyzeProvide($src) {
		$tags = array();
		
		preg_match_all('@(?:^|\\s)namespace\\s+\\\\?([a-zA-Z_][\\da-zA-Z\\\\_]*)\\s*;@', $src, $matches);
		$namespaces = array();
		foreach ($matches[1] as $namespace) {
			$namespaces[$namespace] = 1;
			preg_match_all(Utils::CAMEL_CASE_PATTERN, $namespace, $matches);
			foreach ($matches[0] as $tag)
				Utils::array_add($tags, $tag);
		}
		$ns = count($namespaces) == 1 ? key($namespaces) . '\\' : '';
		
		preg_match_all('@(?:^|\\s)class\\s+([a-zA-Z][\\da-zA-Z_]*)[\\s\\{]@', $src, $matches);
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
		preg_match_all('@(?:^|\\s)use\\s+\\\\?([a-zA-Z_][\\da-zA-Z\\\\_]*)\\s*;@', $src, $matches);
		$namespaces = array();
		foreach ($matches[1] as $namespace)
			$namespaces[$namespace] = 1;
		$ns = count($namespaces) == 1 ? key($namespaces) . '\\' : '';

		preg_match_all('@[^\\w]new\\s+\\\\?([a-zA-Z][\\da-zA-Z_]*)[\\s\\(]@', $src, $matches);
		$classes = array();
		foreach ($matches[1] as $class)
			$classes[$ns . $class] = 1;

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
