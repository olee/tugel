<?php

namespace Zeutzheim\PpintBundle\Model;

use Zeutzheim\PpintBundle\Model\Language;

class LanguageManager {

	private $languages = array();

	public function __construct() {
	}

	public function addLanguage(Language $language) {
		$this->languages[$language->getName()] = $language;
	}

	/**
	 * @return array
	 */
	public function getLanguages() {
		return $this->languages;
	}
	
	/**
	 * @return Language
	 */
	public function getLanguage($name) {
		return $this->languages[$name];
	}

}
