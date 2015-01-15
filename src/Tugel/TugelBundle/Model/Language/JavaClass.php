<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Util\Utils;

class JavaClass extends Language {
		
	public function analyzeProvide($path, $file) {
		$index = array(
			'namespace' => array(),
			'class' => array(),
			'tag' => array(),
			'tag2' => array(),
		);

		$name = str_replace('/', '.', $file);
		//echo "$name\n";
		
		if (preg_match('@^(.*)\.[^\.]+.class$@i', $name, $matches)) {
			Utils::array_add($index['namespace'], $matches[1]);
			Utils::array_add($index['tag'], $matches[1]);
			Utils::array_add($index['tag2'], $matches[1]);
		}
		
		if (preg_match('@([^\$\.]+).class$@i', $name, $matches)) {
			Utils::array_add($index['class'], $matches[1]);
			Utils::array_add($index['tag'], $matches[1]);
			Utils::array_add($index['tag2'], $matches[1]);
		}
		
		return $index;
	}
		
	public function analyzeUse($path, $file) {
		$index = array(
			'namespace' => array(),
			'class' => array(),
			'tag' => array(),
			'tag2' => array(),
		);

		$name = str_replace('/', '.', $file);
		//echo "$name\n";
		
		if (preg_match('@^(.*)\.[^\.]+.class$@i', $name, $matches)) {
			Utils::array_add($index['namespace'], $matches[1]);
			Utils::array_add($index['tag'], $matches[1]);
			Utils::array_add($index['tag2'], $matches[1]);
		}
		
		if (preg_match('@([^\$\.]+).class$@i', $name, $matches)) {
			Utils::array_add($index['class'], $matches[1]);
			Utils::array_add($index['tag'], $matches[1]);
			Utils::array_add($index['tag2'], $matches[1]);
		}
		
		return $index;
	}
	
	public function getName() {
		return 'Java';
	}
	
	public function getExtension() {
		return '.class';
	}

}
