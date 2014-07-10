<?php

namespace Zeutzheim\PpintBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Zeutzheim\PpintBundle\Exception\PackageNotFoundException;
use Zeutzheim\PpintBundle\Exception\VersionNotFoundException;
use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\Platform;

use Zeutzheim\PpintBundle\Entity\Platform as PlatformEntity;
use Zeutzheim\PpintBundle\Entity\Version;

class Hackage extends Platform {

	public function downloadVersion(Version $version, $path) {
		try {
			$fn = $version->getPackage()->getUrl() . '-' . $version->getName() . '.tar.gz';
			$url = 'http://hackage.haskell.org/package/' . $version->getPackage()->getUrl() . '-' . $version->getName() . '/' . $fn;
	
			if (!$this->downloadFile($url, $path . $fn))
				throw new DownloadErrorException($version);
			
			// Extract files
			exec('tar -zxf ' . escapeshellarg($path . $fn) . ' --strip-components=1 -C ' . escapeshellarg($path) . ' && rm ' . escapeshellarg($path . $fn), $output, $success);
			if ($success !== 0)
				throw new DownloadErrorException($version);
			
			return true;
		} catch (PackageNotFoundException $e) {
			$version->getPackage()->setError(true);
			$version->setError(true);
			$this->getEntityManager()->remove($version->getPackage());
			$this->getEntityManager()->flush();
		} catch (VersionNotFoundException $e) {
			$version->setError(true);
			$this->getEntityManager()->remove($version);
			$this->getEntityManager()->flush();
		} catch (DownloadErrorException $e) {
			$version->setError(true);
			$this->getEntityManager()->flush();
		}
			
		return false;
	}

	//*******************************************************************

	public function getName() {
		return 'hackage';
	}

	public function getBaseUrl() {
		return 'https://hackage.haskell.org/package/';
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

	public function getVersionRegex() {
		return '@href="/package/[^"]*-([\\d\\.]*\d)@i';
	}

	public function getMasterVersionTagRegex() {
		return null;
	}

}
