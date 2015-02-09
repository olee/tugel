<?php

namespace Tugel\TugelBundle\Model;

use Tugel\TugelBundle\Model\AbstractPlatform;

class PlatformManager {

	private $elements = array();

	public function add(AbstractPlatform $platform) {
		$this->elements[$platform->getName()] = $platform;
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
