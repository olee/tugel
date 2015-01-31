<?php

namespace Tugel\TugelBundle\Model\Platform;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use Tugel\TugelBundle\Exception\PackageNotFoundException;
use Tugel\TugelBundle\Exception\VersionNotFoundException;
use Tugel\TugelBundle\Exception\DownloadErrorException;

use Tugel\TugelBundle\Model\AbstractPlatform;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class Maven extends AbstractPlatform {

	function getPackageNameParts(Package $package) {
		return preg_split('/:/', $package->getName());
	}

	function getMavenDownloadUrl($group, $artifact, $version, $file) {
		return 'http://search.maven.org/remotecontent?filepath=' . str_replace('.', '/', $group) . '/' . $artifact . '/' . $version . '/' . $artifact . '-' . $version . $file;
	}

	protected function doDownload(Package $package, $path) {
		$parts = $this->getPackageNameParts($package);
		$url = $this->getMavenDownloadUrl($parts[0], $parts[1], $package->getVersion(), '.jar');
		$fn = $parts[1] . '.jar';
		
		if (!$this->downloadFile($url, $path . $fn)) {
			//echo "Download failed: $url\n"; //exit;
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		}

		if (!$this->extractZip($path, $fn, true)) {
			echo "Extraction failed\n";
			//exit;
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		}
	}

	//*******************************************************************

	public function getName() {
		return 'maven';
	}

	public function getCrawlUrl() {
		return 'http://mvnrepository.com/feeds/rss2.0.xml';
	}

	public function getMasterVersion() {
		return 'dev-master';
	}

	public function getPackageRegex() {
		return '@<link>https://packagist.org/packages/([^<]*)</link>@i';
	}

	public function getPackageUrl(Package $package) {
		$parts = preg_split('/:/', $package->getName());
		if (count($parts) < 2)
			return 'http://search.maven.org';
		if (!$package->getVersion()) {
			return 'http://mvnrepository.com/artifact/' . $parts[0] . '/' . $parts[1];
		}
		return 'http://search.maven.org/#artifactdetails|' . $parts[0] . '|' . $parts[1] . '|' . $package->getVersion() . '|';
	}
	
	protected function doGetPackageData(Package $package) {
		$parts = $this->getPackageNameParts($package);
		$url = 'http://search.maven.org/solrsearch/select?q=g:' . $parts[0] . '%20AND%20a:' . $parts[1] . '&wt=json';
		$json = $this->httpGet($url);
		if (!$json) {
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
		}

		$data = json_decode($json);
		if (!$data || empty($data->response->docs)) {
			return AbstractPlatform::ERR_PACKAGEDATA_NOT_FOUND;
		}

		// Assemble package information
		$package->data = array(
			AbstractPlatform::PKG_VERSION => $data->response->docs[0]->latestVersion,
			AbstractPlatform::PKG_DEPENDENCIES => array(),
		);
		
		// Get POM file
		$url = $this->getMavenDownloadUrl($parts[0], $parts[1], $data->response->docs[0]->latestVersion, '.pom');
		$xml = $this->httpGet($url);
		if (!$xml) {
			$this->log('Could not fetch POM', $package, Logger::ERROR);
			return;
		}
		$serializer = new Serializer(array(new GetSetMethodNormalizer()), array(new XmlEncoder()));
		$pom = $serializer->decode($xml, 'xml');
		
		// Get dependencies
		if (!empty($pom['dependencies']) && !empty($pom['dependencies']['dependency'])) {
			$src = $pom['dependencies']['dependency'];
			if (!isset($src[0]))
				$src = array($src);
			foreach ($src as $v)
				$package->data[AbstractPlatform::PKG_DEPENDENCIES][$v['groupId'] . ':' . $v['artifactId']] = empty($v['version']) ? '' : $v['version'];
		}
		
		if (isset($pom['name'])) {
			$package->data[AbstractPlatform::PKG_DESCRIPTION] = $pom['name'];
		}
		
		// Get license
		if (!empty($pom['licenses']) && !empty($pom['licenses']['license'])) {
			$src = $pom['licenses']['license'];
			if (!isset($src[0]))
				$src = array($src);
			$package->data[AbstractPlatform::PKG_LICENSE] = join(', ', array_map(function($v) {
				return !empty($v['name']) ? $v['name'] : null;
			}, $src));
		}
	}

}
