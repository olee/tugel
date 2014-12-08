<?php

namespace Zeutzheim\PpintBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Zeutzheim\PpintBundle\Exception\PackageNotFoundException;
use Zeutzheim\PpintBundle\Exception\VersionNotFoundException;
use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\Platform;

use Zeutzheim\PpintBundle\Entity\Platform as PlatformEntity;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

class PyPi extends Platform {

	public function doDownloadVersion(Version $version, $path) {
	    return false;
        
		$fn = $version->getPackage()->getUrl() . '-' . $version->getName() . '.tar.gz';
		$url = 'http://hackage.haskell.org/package/' . $version->getPackage()->getUrl() . '-' . $version->getName() . '/' . $fn;

		if (!$this->downloadFile($url, $path . $fn))
			throw new DownloadErrorException($version);
		
		// Extract files
		exec('tar -zxf ' . escapeshellarg($path . $fn) . ' --strip-components=1 -C ' . escapeshellarg($path) . ' && rm ' . escapeshellarg($path . $fn), $output, $success);
		if ($success !== 0)
			throw new DownloadErrorException($version);
		
		return true;
	}

	//*******************************************************************

	public function getName() {
		return 'pypi';
	}

	public function getBaseUrl() {
		return 'https://pypi.python.org/pypi/';
	}

	public function getCrawlUrl() {
		return 'https://pypi.python.org/pypi?%3Aaction=rss';
	}

	public function getMasterVersion() {
		return null;
	}

	public function getPackageRegex() {
		return '@<link>http://pypi.python.org/pypi/([^/]+)/[^/<]+</link>@i';
	}

	public function getPackageData(Package $package) {
        $json = $this->httpGet($this->getBaseUrl() . $package->getName() . '/json');
        if ($json === false) {
            // echo 'Error downloading data'; exit;
            return false;
        }
        
        $data = json_decode($json, TRUE);
        if ($data === null) {
            // echo 'Error reading data'; exit;
            return false;
        }
        
        // Get basic package information
        $pkgData = array(
            //'description' => $data['info']['description'],
            'description' => $data['info']['summary'],
            'versions' => array_keys($data['releases']),
        );

        return $pkgData;
	}

}
