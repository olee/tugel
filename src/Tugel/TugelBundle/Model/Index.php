<?php

namespace Tugel\TugelBundle\Model;

use Tugel\TugelBundle\Util\Utils;

class Index {

	public $classes = array();
	
	public $namespaces = array();
	
	public $languages = array();
	
	public $tags = array();

	public function addLanguage($language) {
		//Utils::array_add($this->languages, $language);
		$this->languages[$language] = 1;
		return $this;
	}

	public function addNamespace($namespace) {
		//Utils::array_add($this->namespaces, $namespace);
		$this->namespaces[$namespace] = 1;
		return $this;
	}

	public function addClass($class) {
		Utils::array_add($this->classes, $class);
		return $this;
	}

	public function addTag($tag) {
		Utils::array_add($this->tags, $tag);
		return $this;
	}

	public function tagData() {
		foreach ($this->classes as $tag => $count)
			Utils::array_add($this->tags, $tag, $count);
		foreach ($this->namespaces as $tag => $count)
			Utils::array_add($this->tags, $tag); //, $count);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getLanguages() {
		return $this->languages;
	}

	/**
	 * @return array
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}

	/**
	 * @return array
	 */
	public function getTags() {
		return $this->tags;
	}

	/**
	 * @return string
	 */
	public function getLanguagesString() {
		return $this->joinIndex($this->languages);
	}

	/**
	 * @return string
	 */
	public function getNamespacesString() {
		return $this->joinIndex($this->namespaces);
	}

	/**
	 * @return string
	 */
	public function getClassesString() {
		return $this->joinIndex($this->classes);
	}
	
	/**
	 * @return string
	 */
	public function getTagsString() {
		return $this->joinIndex($this->tags);
	}
	
	/**
	 * @return string
	 */
	public function joinIndex(array $index) {
		$result = '';
		foreach ($index as $key => $count)
			for ($i = 0; $i < $count; $i++) 
				$result .= $key . ' ';
		return $result;
	}

}