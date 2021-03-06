<?php

namespace Tugel\TugelBundle\Exception;

use Tugel\TugelBundle\Entity\Version;

/**
 * Thrown when a version was not found.
 */
class VersionNotFoundException extends \RuntimeException {
	
	/**
	 * Constructor.
	 *
	 * @param string $version The version that was not found
	 */
	public function __construct($version) {
		if ($version instanceof Version) {
			parent::__construct(sprintf('The version %s from %s/%s could not be found.', $version, $version->getPackage()->getPlatform(), $version->getPackage()));
		} else {
			parent::__construct(sprintf('The version %s could not be found.', $version));
		}
	}

}
