<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Model\Index;
use Tugel\TugelBundle\Util\Utils;

class JavaClass extends Language {
		
	public function analyzeProvide(Index $index, $path, $file) {
		$name = str_replace('/', '.', $file);
		
		if (preg_match('@^(.*)\.[^\.]+.class$@i', $name, $matches)) {
			$index->addNamespace($matches[1]);
			$index->addTag($matches[1]);
		}
		
		if (preg_match('@([^\$\.]+).class$@i', $name, $matches)) {
			$index->addClass($matches[1]);
			$index->addTag($matches[1]);
		}
	}
		
	public function analyzeUse(Index $index, $path, $file) {
		$index = array(
			'namespace' => array(),
			'class' => array(),
			'tags' => '',
		);

		$name = str_replace('/', '.', $file);
		//echo "$name\n";
		
		if (preg_match('@^(.*)\.[^\.]+.class$@i', $name, $matches)) {
			Utils::array_add($index['namespace'], $matches[1]);
			$index['tags'] .= $matches[1] . ' ';
		}
		
		if (preg_match('@([^\$\.]+).class$@i', $name, $matches)) {
			Utils::array_add($index['class'], $matches[1]);
			$index['tags'] .= $matches[1] . ' ';
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
