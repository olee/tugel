<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Model\Index;
use Tugel\TugelBundle\Util\Utils;

class Ruby extends Language {
		
	public function analyzeProvide(Index $index, $path, $file) {
		$src = file_get_contents($path . $file);
		
		preg_match_all('/(?:^|;)[ \t]*module[ \t]+([a-zA-Z_$][a-zA-Z\d_$]*)*(?:\n|;)/m', $src, $matches);
		foreach ($matches[1] as $namespace) {
			$index->addNamespace($namespace);
		}
		
		preg_match_all('/(?:^|;)[ \t]*class[ \t]+([a-zA-Z_$][a-zA-Z\d_$]*)(?:[ \t]*<[ \t]*[\w:\.]+)?(?:\n|;)/m', $src, $matches);
		foreach ($matches[1] as $class) {
			$index->addClass($class);
		}
	}
		
	public function analyzeUse(Index $index, $path, $file) {
	}
	
	public function getName() {
		return 'Ruby';
	}
	
	public function getExtensions() {
		return array(
			'.rb',
			'.rake',
		);
	}

}
