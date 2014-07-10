<?php

namespace Zeutzheim\PpintBundle\Exception;

use Zeutzheim\PpintBundle\Entity\Version;

/**
 * Thrown when a version was not found.
 *
 * @author Björn Zeutzheim <bjoern@zeutzheim-boppard.de>
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
