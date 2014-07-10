<?php

namespace Zeutzheim\PpintBundle\Exception;

use Zeutzheim\PpintBundle\Entity\Package;

/**
 * Thrown when a package was not found.
 *
 * @author Björn Zeutzheim <bjoern@zeutzheim-boppard.de>
 */
class PackageNotFoundException extends \RuntimeException {
	
	/**
	 * Constructor.
	 *
	 * @param string $package The package that was not found
	 */
	public function __construct($package) {
		if ($package instanceof Package) {
			parent::__construct(sprintf('The package %s from platform %s could not be found.', $package, $package->getPlatform()));
		} else {
			parent::__construct(sprintf('The package %s could not be found.', $package));
		}
	}

}
