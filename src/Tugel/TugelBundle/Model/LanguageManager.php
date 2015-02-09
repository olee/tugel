<?php

namespace Tugel\TugelBundle\Model;

use Tugel\TugelBundle\Model\Language;

class LanguageManager {

	private $elements = array();

	public function add(Language $language) {
		$this->elements[$language->getName()] = $language;
	}

	/**
	 * @return array
	 */
	public function all() {
		return $this->elements;
	}
	
	/**
	 * @return AbstractPlatform
	 */
	public function get($name) {
		return isset($this->elements[$name]) ? $this->elements[$name] : null;
	}

}
