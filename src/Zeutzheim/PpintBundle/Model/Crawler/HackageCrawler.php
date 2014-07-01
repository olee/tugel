<?php

namespace Zeutzheim\PpintBundle\Model\Crawler;

use Zeutzheim\PpintBundle\Model\Crawler;

use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Version;

class HackageCrawler extends Crawler {
	
	public function downloadVersion(Version $version, $path) {
		return false;
	}
	
	public function setupPlatform(Platform $platform) {
		$lang = $this->getLanguage('haskell');
		if (!$lang) {
			$lang = new Language();
			$lang->setName('haskell');
			$lang->setExtension('.hs');
			$this->getEntityManager()->persist($lang);
		}
		$platform->addLanguage($lang);
	}
	
	//*******************************************************************
	
	public function getPlatformName() {
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
		return '@/package/[^"]*-([\d\.]*)"@i';
	}
	
	public function getMasterVersionTagRegex() {
		return null;
	}
	
}
