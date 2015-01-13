<?php

namespace Tugel\TugelBundle\Exception;

/**
 * Thrown when a platform was not found.
 */
class PlatformNotFoundException extends \RuntimeException {
	
	/**
	 * Constructor.
	 *
	 * @param string $platform The platform that was not found
	 */
	public function __construct($platform) {
		parent::__construct(sprintf('The platform %s could not be found.', $package));
	}

}
