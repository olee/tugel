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
	
	/**
	 * @return array
	 */
	public function collapseIndex($index) {
		/*
		foreach ($index as &$types)
			foreach ($types as &$identifiers)
				$identifiers = array_unique($identifiers);
		$namespaces = '';
		$classes = '';
		foreach ($result as $lang => $types) {
			$namespaces .= implode(' ', $types['namespace']) . ' ';
			$classes .= implode(' ', $types['class']) . ' ';
		}
		return array(
			'languages' => implode(' ', array_keys($result)),
			'namespaces' => $namespaces,
			'classes' => $classes,
		);
		*/
		$result = array(
			'language' => implode(' ', array_keys($index)),
		);
		foreach ($index as $lang => $types) {
			foreach ($types as $type => $identifiers) {
				$identifiers = array_unique($identifiers);
				foreach ($identifiers as $identifier) {
					$result[$type] = (isset($result[$type]) ? $result[$type] . ' ' : '') . $lang . ':' . $identifier;
				}
			}
		}
		return $result;
	}
}
