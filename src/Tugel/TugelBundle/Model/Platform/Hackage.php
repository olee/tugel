<?php

namespace Tugel\TugelBundle\Model\Platform;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

use Tugel\TugelBundle\Exception\DownloadErrorException;

use Tugel\TugelBundle\Model\AbstractPlatform;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

class Hackage extends AbstractPlatform {

	protected function doDownload(Package $package, $path) {
		$fn = $package->getName() . '-' . $package->getVersion() . '.tar.gz';
		$url = 'http://hackage.haskell.org/package/' . $package->getName() . '-' . $package->getVersion() . '/' . $fn;

		if (!$this->downloadFile($url, $path . $fn))
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		
		// Extract files
		$cmd = 'tar -xzof ' . escapeshellarg($this->preparePath($path) . $fn) . ' --strip-components=1 -C ' . escapeshellarg($this->preparePath($path)) . ' && chmod -Rf 775 ' . escapeshellarg($this->preparePath($path)) . ' && rm ' . escapeshellarg($this->preparePath($path) . $fn);
		exec($cmd, $output, $success);
		if ($success !== 0)
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
	}

	//*******************************************************************

	public function getName() {
		return 'hackage';
	}

	public function getCrawlUrl() {
		return 'https://hackage.haskell.org/packages/recent.rss';
	}

	public function getMasterVersion() {
		return null;
	}

	public function getPackageRegex() {
		return '@<link>http://hackage.haskell.org/package/([^<]*)-[\d\.]*</link>@i';
	}

	public function getPackageUrl(Package $package) {
		return 'https://hackage.haskell.org/package/' . $package->getName();
	}

	protected function doGetPackageData(Package $package) {
		$src = $this->httpGet('https://hackage.haskell.org/package/' . $package->getName() . '/' . $package->getName() . '.cabal');
		if ($src === false) {
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
		}
		
		$package->data = array();
		
		// Get version
		if (!preg_match('@^version\s*:\s*([^\s]+)@mi', $src, $matches)) {
			//print_r($src); exit;
			return AbstractPlatform::ERR_PACKAGEDATA_NOT_FOUND;
		}
		$package->data[AbstractPlatform::PKG_VERSION] = $matches[1];
		
		// Get description
		if (preg_match('@^description\s*:\s*(.*(?:.*\r?\n\s+)*)@mi', $src, $matches)) {
			$desc = $matches[1];
			$package->data[AbstractPlatform::PKG_DESC] = preg_replace('@\n\s+\.?@', "\n", $desc);
		}
		
		// Get license
		if (preg_match('@^license\s*:\s*(.*(?:.*\r?\n\s+)*)@mi', $src, $matches)) {
			$desc = $matches[1];
			$package->data[AbstractPlatform::PKG_LICENSE] = preg_replace('@\n\s+\.?@', ", ", $desc);
		}
	}

}
