<?php

namespace Zeutzheim\PpintBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Doctrine\ORM\EntityManager;

use ssko\UtilityBundle\Core\ControllerHelperNT;

use Zeutzheim\PpintBundle\Model\Platform;

/**
 * @Route("/")
 */
class PlatformController extends ControllerHelperNT {

	/**
	 * @return Zeutzheim\PpintBundle\Model\Platform
	 */
	public function getPlatform() {
		return $this->get('ppint.platform');
	}

	/**
	 * @Route("/crawl/log")
	 */
	public function crawlLogAction() {
		$html = '<html><head>';
		//$html .= '<meta http-equiv="refresh" content="5">';
		$html .= '</head><body>';
		$html .= '<pre>' . $this->tailFile(WEB_DIRECTORY . '../var/logs/ppint.log', 1000) . '</pre>';
		$html .= '</body></html>';
		return new Response($html);
	}

	/**
	 * @Route("/crawl/packagist")
	 */
	public function crawlPackagist() {
		$platform = $this->getPlatform()->getPlatform('packagist');
		if (!$platform)
			throw new \RuntimeException();

		$this->getPlatform()->logger->info('-- started crawling packagist --');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', ));

		set_time_limit(60 * 60);

		$letter = 'j';
		while ($letter != 'z') {
			for ($page = 1; $page <= 200; $page++) {
				$this->getPlatform()->logger->info('LETTER ' . $letter . ' PAGE ' . $page);
	
				curl_setopt($ch, CURLOPT_URL, 'https://packagist.org/search/?search_query%5Bquery%5D=' . $letter . '&page=' . $page);
				$src = curl_exec($ch);
				$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($status != 200)
					break;
	
				preg_match_all('@/packages/([^"]*)"@i', $src, $matches);
				$packages = array_unique($matches[1]);
				foreach ($packages as $packageUri) {
					$package = $this->getPlatform()->getPackage($platform, $packageUri);
					if (!$package) {
						$package = new \Zeutzheim\PpintBundle\Entity\Package();
						$package->setName($packageUri);
						$package->setUrl($packageUri);
						$package->setPlatform($platform);
						$this->getEntityManager()->persist($package);
	
						$this->getPlatform()->logger->info('A ' . $package->getPlatform()->getName() . ' ' . $package->getName());
					} else {
						//$package->setCrawled(false);
						//$this->getPlatform()->logger->info('U ' . $package->getPlatform()->getName() . ' ' . $package->getName());
					}
				}
	
				$this->getEntityManager()->flush();
				$this->getEntityManager()->clear();
				$platform = $this->getEntityManager()->merge($platform);
			}
			$letter++;
		}
		curl_close($ch);

		//$this->getEntityManager()->flush();
		$this->getPlatform()->logger->info('---- finished crawling packagist ----');

		exit ;
		return new Response('finished');
	}

	/**
	 * @Route("/crawl")
	 */
	public function crawlAction() {
		@unlink(WEB_DIRECTORY . '../var/logs/ppint.last.log');

		$this->getPlatform()->crawlPlatforms();
		$this->getPlatform()->crawlPackages();

		$html = '<html><head>';
		//$html .= '<meta http-equiv="refresh" content="5">';
		$html .= '</head><body>';
		$html .= '<pre>' . file_get_contents(WEB_DIRECTORY . '../var/logs/ppint.last.log') . '</pre>';
		$html .= '</body></html>';
		return new Response($html);
	}

	/**
	 * @Route("/crawl/platform/{platform}")
	 */
	public function crawlPlatformAction($platform = null) {
		$this->getPlatform()->crawlPlatforms();
		$log = $this->tailFile(WEB_DIRECTORY . '../var/logs/ppint.log', 200);
		return new Response('<pre>' . $log . '</pre>');
	}

	/**
	 * @Route("/crawl/package/{package}")
	 */
	public function crawlPackageAction($package = null) {
		$this->getPlatform()->crawlPackages(null, 20);
		$log = $this->tailFile(WEB_DIRECTORY . '../var/logs/ppint.log', 200);
		return new Response('<pre>' . $log . '</pre>');
	}

	public function tailFile($filepath, $lines = 1, $adaptive = true) {
		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false)
			return false;
		// Sets buffer size
		if (!$adaptive)
			$buffer = 4096;
		else
			$buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
		// Jump to last character
		fseek($f, -1, SEEK_END);
		if (fread($f, 1) != "\n")
			$lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';
		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {
			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);
			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);
			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;
			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");
		}
		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {
			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);
		}
		// Close file and return
		fclose($f);
		return trim($output);

	}

}
