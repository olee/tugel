<?php

namespace Zeutzheim\PpintBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Doctrine\ORM\EntityManager;

use ssko\UtilityBundle\Core\ControllerHelperNT;
use Zeutzheim\PpintBundle\Entity\Language;
use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

/**
 * @Route("/")
 */
class CrawlerController extends ControllerHelperNT {

	private $packageQb;
	private $versionQb;

	public function getPlatform($name, $lang = null, $baseUrl = null, $crawlUrl = null, $crawlType = null) {
		$entity = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Platform')->findOneByName($name);
		if (!$entity) {
			$entity = new Platform();
			$entity->setName($name);
			if ($lang) {
				if (!is_array($lang))
					$lang = array($lang);
				foreach ($lang as $l) {
					if (!is_object($l)) {
						$l = $this->getLanguage($l);
					}
					if (!$entity->getLanguages()->contains($l))
						$entity->addLanguage($l);
				}
			}
			if ($baseUrl)
				$entity->setBaseUrl($baseUrl);
			if ($crawlUrl)
				$entity->setCrawlUrl($crawlUrl);
			if ($crawlType)
				$entity->setCrawlType($crawlType);
			$this->getEntityManager()->persist($entity);
		}
		return $entity;
	}

	public function getLanguage($name, $ext = null) {
		$entity = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Language')->findOneByName($name);
		if (!$entity) {
			$entity = new Language();
			$entity->setName($name);
			if ($ext)
				$entity->setExtension($ext);
			$this->getEntityManager()->persist($entity);
		}
		return $entity;
	}

	public function getPackage($platform, $url) {
		if (!$this->packageQb) {
			$this->packageQb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('e');
			$this->packageQb->where('e.platform = ?1');
			$this->packageQb->andWhere('e.url = ?2');
		}
		return $this->packageQb->setParameters(array(
			1 => $platform->getId(),
			2 => $url
		))->getQuery()->getOneOrNullResult();
	}

	public function getVersion($package, $name) {
		if (!$this->versionQb) {
			$this->versionQb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Version')->createQueryBuilder('v');
			$this->versionQb->where('v.package = ?1');
			$this->versionQb->andWhere('v.name = ?2');
		}
		return $this->versionQb->setParameters(array(
			1 => $package->getId(),
			2 => $name
		))->getQuery()->getOneOrNullResult();
	}

	private function downloadFile($url) {
		$file = fopen($url, "rb");
		if (!$file)
			return false;

		$result = '';
		while (!feof($file)) {
			$result .= fread($file, 1024 * 8);
		}
		fclose($file);

		return $result;
	}

	/**
	 * @Route("/crawl")
	 */
	public function crawlAction() {
		$platform = $this->getPlatform('packagist', array($this->getLanguage('php', '.php')), 'https://packagist.org/packages/', 'https://packagist.org/feeds/releases.rss', 'rss');

		$packages = array();
		
		echo "<pre>";

		if ($platform->getCrawlType() == 'rss') {
			$src = $this->downloadFile($platform->getCrawlUrl());

			preg_match_all('@<link>([^<]*)</link>@i', $src, $matches);
			foreach ($matches[1] as $url) {
				$url = preg_replace('@' . $platform->getBaseUrl() . '@', '', $url, -1, $count);
				if (!$count)
					continue;
				
				$package = $this->getPackage($platform, $url);
				if (!$package) {
					$package = new Package();
					$package->setName($url);
					$package->setUrl($url);
					$package->setPlatform($platform);
					$this->getEntityManager()->persist($package);

					echo 'A ' . $package->getName() . ' from ' . $url . "\n";
				} else {
					echo 'U ' . $package->getName() . "\n";
				}
				
				$src = $this->downloadFile($platform->getBaseUrl() . $package->getName());
				preg_match_all('@<li (?=[^>]*class="version[^>]*).*id="([^"]*)"@i', $src, $matches);
				foreach ($matches[1] as $versionName) {
					$version = $this->getVersion($package, $versionName);
					if (!$version) {
						$version = new Version();
						$version->setName($versionName);
						$version->setPackage($package);
						$this->getEntityManager()->persist($version);
	
						echo "  AV " . $version->getName() . "\n";
					} else {
						echo "  UV " . $version->getName() . "\n";
					}
					$version->setLastModifiedDate(new \DateTime());
				}
				/*
				*/
				$this->getEntityManager()->flush();
			}
		} else {
			//$doc = new \DOMDocument();
		}
		
		foreach ($packages as $package) {
		}

		return new Response('OK');
	}

}
