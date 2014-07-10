<?php

namespace Zeutzheim\PpintBundle\Model;

use Zeutzheim\PpintBundle\Model\Platform;

class PlatformManager {

	/**
	 * @var Logger
	 */
	public $logger;

	private $platforms = array();

	public function addPlatform(Platform $platform) {
		$this->platforms[$platform->getName()] = $platform;
	}

	/**
	 * @return array
	 */
	public function getPlatforms() {
		return $this->platforms;
	}
	
	/**
	 * @return Platform
	 */
	public function getPlatform($name) {
		return $this->platforms[$name];
	}

}
