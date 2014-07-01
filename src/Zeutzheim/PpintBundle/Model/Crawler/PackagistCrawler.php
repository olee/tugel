<?php

namespace Zeutzheim\PpintBundle\Model\Crawler;

use Zeutzheim\PpintBundle\Model\Crawler;

use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Version;

class PackagistCrawler extends Crawler {
	
	public function downloadVersion(Version $version, $path) {
		$versionName = $version->getName();
		$json = $this->httpGet($this->getBaseUrl() . $version->getPackage()->getName() . '.json');
		$data = json_decode($json);
		
		//print_r($data->package->versions->$versionName->source);
		//print_r($data->package->versions->$versionName->dist);
		
		if (!file_exists($path))
			mkdir($path);
		
		exec('git clone --no-checkout ' . escapeshellarg($data->package->versions->$versionName->source->url) . ' ' . escapeshellarg($path) . 
			' && cd ' . escapeshellarg($path) . 
			' && git reset --hard ' . $data->package->versions->$versionName->source->reference);
		
		return false;
	}
	
	public function setupPlatform(Platform $platform) {
		$lang = $this->getLanguage('php');
		if (!$lang) {
			$lang = new Language();
			$lang->setName('php');
			$lang->setExtension('.php');
			$this->getEntityManager()->persist($lang);
		}
		$platform->addLanguage($lang);
	}
	
	//*******************************************************************
	
	public function getPlatformName() {
		return 'packagist';
	}
	
	public function getBaseUrl() {
		return 'https://packagist.org/packages/';
	}
	
	public function getCrawlUrl() {
		return 'https://packagist.org/feeds/releases.rss';
	}
	
	public function getMasterVersion() {
		return 'dev-master';
	}
	
	public function getPackageRegex() {
		return '@<link>https://packagist.org/packages/([^<]*)</link>@i';
	}
	
	public function getVersionRegex() {
		return '@<li (?=[^>]*class="version[^>]*).*id="([^"]*)"@i';
	}
	
	public function getMasterVersionTagRegex() {
		return '@class="source-reference">reference: (.*)<@i';
	}
	
}
