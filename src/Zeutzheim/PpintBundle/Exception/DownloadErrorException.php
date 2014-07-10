<?php

namespace Zeutzheim\PpintBundle\Exception;

use Zeutzheim\PpintBundle\Entity\Version;

/**
 * Thrown when a version could not be downloaded.
 *
 * @author Björn Zeutzheim <bjoern@zeutzheim-boppard.de>
 */
class DownloadErrorException extends \RuntimeException {
	
	/**
	 * Constructor.
	 *
	 * @param string $version The version that could not be downloaded
	 */
	public function __construct($version) {
		if ($version instanceof Version) {
			parent::__construct(sprintf('The version %s from %s/%s could not be downloaded.', $version, $version->getPackage()->getPlatform(), $version->getPackage()));
		} else {
			parent::__construct(sprintf('The version %s could not be downloaded.', $version));
		}
	}

}
