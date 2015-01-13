<?php

namespace Tugel\TugelBundle\Exception;

use Tugel\TugelBundle\Entity\Package;

/**
 * Thrown when a package could not be downloaded.
 */
class DownloadErrorException extends \RuntimeException {
	
	/**
	 * Constructor.
	 *
	 * @param string $package The package that could not be downloaded
	 */
	public function __construct($package) {
		if ($package instanceof Package) {
			parent::__construct(sprintf('The package %s:%s could not be downloaded.', $package->getPlatform(), $package));
		} else {
			parent::__construct(sprintf('The package %s could not be downloaded.', $package));
		}
	}

}
