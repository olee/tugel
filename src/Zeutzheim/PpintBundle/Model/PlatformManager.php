<?php

namespace Zeutzheim\PpintBundle\Model;

use Zeutzheim\PpintBundle\Model\AbstractPlatform;

class PlatformManager {

	/**
	 * @var Logger
	 */
	public $logger;

	private $platforms = array();

	public function addPlatform(AbstractPlatform $platform) {
		$this->platforms[$platform->getName()] = $platform;
	}

	/**
	 * @return array
	 */
	public function getPlatforms() {
		return $this->platforms;
	}
	
	/**
	 * @return AbstractPlatform
	 */
	public function getPlatform($name) {
		return $this->platforms[$name];
	}

}
