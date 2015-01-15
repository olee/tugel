<?php

namespace Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Util\Utils;

use PhpParser\Parser;
use PhpParser\Lexer;

class PHP extends Language {
	
	const FAST_INDEX = true;
	
	public static function tagItems(&$tags, $items, $pattern = Utils::CAMEL_CASE_PATTERN) {
		foreach ($items as $key => $count) {
			preg_match_all($pattern, $key, $matches);
			foreach ($matches[0] as $tag)
				Utils::array_add($tags, $tag, $count);
		}
	}

	public static function parseAndIndex($src) {
		$indexer = new IndexNodeVisitor();
		try {
			ini_set('xdebug.max_nesting_level', 2000);
			$parser = new \PhpParser\Parser(new \PhpParser\Lexer());
			$stmts = $parser->parse($src);
			
			$traverser = new \PhpParser\NodeTraverser();
			$traverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver());
			$traverser->addVisitor($indexer);
			$traverser->traverse($stmts);
		} catch (\PhpParser\Error $e) {
			echo 'Parse Error: ', $e->getMessage() . "\n";
		}
		return $indexer->index;
	}

	public function analyzeProvide($path, $file) {
		$src = file_get_contents($path . $file);
		if (PHP::FAST_INDEX) {
			$tags2 = array();
			$classes = array();
			$namespaces = array();
			
			preg_match_all('@(?:^|\\s)namespace\\s+\\\\?([a-zA-Z_][\\da-zA-Z\\\\_]*)\\s*;@', $src, $matches);
			foreach ($matches[1] as $namespace) {
				Utils::array_add($tags2, $namespace);
				Utils::array_add($namespaces, $namespace);
			}
			
			$ns = count($namespaces) == 1 ? key($namespaces) . '\\' : '';
			
			preg_match_all('@(?:^|\\s)class\\s+([a-zA-Z][\\da-zA-Z_]*)[\\s\\{]@', $src, $matches);
			foreach ($matches[1] as $class) {
				Utils::array_add($tags2, $class);
				Utils::array_add($classes, $ns . $class);
			}
	
			$index = array(
				'tag' => array(),
				'tag2' => $tags2,
				'provide_class' => $classes,
				'provide_namespace' => $namespaces,
			);
		} else {
			$index = $this->parseAndIndex($src);
		}
		
		$classNames = array();
		$this->tagItems($classNames, $index['provide_class'], '/[^\\\\]+$/');
		$this->tagItems($index['tag'], $classNames);
		
		$this->tagItems($index['tag'], $index['provide_namespace']);
		
		return array(
			'namespace' => $index['provide_namespace'],
			'class' => $index['provide_class'],
			'tag' => $index['tag'],
			'tag2' => $index['tag2'],
		);
	}

	public function analyzeUse($path, $file) {
		$src = file_get_contents($path . $file);
		$index = $this->parseAndIndex($src);
		
		$classNames = array();
		$this->tagItems($classNames, $index['use_class'], '/[^\\\\]+$/');
		$this->tagItems($index['tag'], $classNames);
		
		$this->tagItems($index['tag'], $index['use_namespace']);
		
		return array(
			'namespace' => $index['use_namespace'],
			'class' => $index['use_class'],
			'tag' => $index['tag'],
		);
	}

	public function getName() {
		return 'PHP';
	}

	public function getExtension() {
		return '.php';
	}

}


class IndexNodeVisitor extends \PhpParser\NodeVisitorAbstract
{
	
	public $index;
	
	public function __construct() {
		$this->index = array(
			'tag' => array(),
			'provide_class' => array(),
			'provide_namespace' => array(),
			'use_class' => array(),
			'use_namespace' => array(),
		);
	}
	
	public function enterNode(\PhpParser\Node $node) {
		// Analyze provide
		if ($node instanceof \PhpParser\Node\Stmt\Class_) {
			Utils::array_add($this->index['provide_class'], $node->namespacedName->toString());
		}
		if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
			if ($node->name !== null)
				Utils::array_add($this->index['provide_namespace'], $node->name->toString());
		}
		
		// Analyze usage
		if ($node instanceof \PhpParser\Node\Stmt\Use_) {
			foreach ($node->uses as $use) {
				Utils::array_add($this->index['use_namespace'], $use->name->toString());
			}
		}
		if ($node instanceof \PhpParser\Node\Expr\New_ && $node->class instanceof \PhpParser\Node\Name\FullyQualified) {
			Utils::array_add($this->index['use_class'], $node->class->toString());
		}
		if ($node instanceof \PhpParser\Node\Param && !empty($node->type) && $node->type != 'array') {
			if (is_string($node->type))
				Utils::array_add($this->index['use_class'], $node->type);
			else
				Utils::array_add($this->index['use_class'], $node->type->toString());
		}
	}
}
