<?php

namespace Zeutzheim\PpintBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

use JMS\Serializer\SerializationContext;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use ssko\UtilityBundle\Core\ControllerHelperNT;

use Zeutzheim\PpintBundle\Form\SearchType;
use Zeutzheim\PpintBundle\Form\SimpleSearchType;

use Zeutzheim\PpintBundle\Model\PackageManager;

class DefaultController extends ControllerHelperNT {

	public static function setArrayKeys(array $array, array $keys) {
		foreach ($keys as $key) {
			if (!isset($array[$key])) $array[$key] = null;
		}
		return $array;
	}
	
	
	public function handleQueryForm(Request $request, $form) {
		if ('POST' === $request->getMethod()) {
			$form->handleRequest($request);
			if ($form->isValid()) {
				$data = $form->getData();
				return $this->redirect($this->generateUrl('search', $data));
			}
		}
		return false;
	}
		
	/**
	 * @Route("/", name="home")
	 * @Template()
	 */
	public function indexAction(Request $request) {
		$form = $this->createForm(new SimpleSearchType());
		$queryFormResult = $this->handleQueryForm($request, $form);
		if ($queryFormResult)
			return $queryFormResult;
		
		return array(
			'searchForm' => $form->createView(),
		);
	}
		
	/**
	 * @Route("/search", name="search")
	 * @Template()
	 */
	public function searchAction(Request $request) {
		$data = $request->query->all();
		if (empty($data['q']))
			return $this->redirect($this->generateUrl('home'));
		
		$form = $this->createForm(new SimpleSearchType(), $data);
		$queryFormResult = $this->handleQueryForm($request, $form);
		if ($queryFormResult)
			return $queryFormResult;
		
		$params = array(
			'searchForm' => $form->createView(),
		);
		
		if (!empty($data['q'])) {
			$query = $data['q'];
			
			if (preg_match('/platform:([^\\s]+)/i', $query, $matches)) {
				$platform = $matches[1];
				$query = str_replace($matches[0], '', $query);
			} else {
				$platform = null;
			}
			
			if (preg_match('/language:([^\\s]+)/i', $query, $matches)) {
				$language = $matches[1];
				$query = str_replace($matches[0], '', $query);
			} else {
				$language = null;
			}
			
			/**
			 * @var PackageManager
			 */
			$pm = $this->get('ppint.package_manager');
				
			$searchResults = $pm->findPackages($platform, null, null, $language, $query, true);
			$params['searchResults'] = $searchResults;
			$params['lastQuery'] = json_encode($pm->lastQuery, JSON_PRETTY_PRINT);
			$params['lastQueryTime'] = $pm->lastQueryTime;
			$params['scores'] = array();
			$params['percentScores'] = array();
			foreach ($searchResults as $p) { $params['scores'][$p->getId()] = $p->_score; $params['percentScores'][$p->getId()] = $p->_percentScore; }

			if ($this->get('kernel')->isDebug()) {
				$searchResults = $pm->findPackages($platform, null, null, $language, $query);
				$params['searchResults2'] = $searchResults;
				$params['lastQuery2'] = json_encode($pm->lastQuery, JSON_PRETTY_PRINT);
				$params['lastQueryTime2'] = $pm->lastQueryTime;
				$params['scores2'] = array();
				$params['percentScores2'] = array();
				foreach ($searchResults as $p) { $params['scores2'][$p->getId()] = $p->_score; $params['percentScores2'][$p->getId()] = $p->_percentScore; }				
			}
		}
		return $params;
	}
		
	/**
	 * @Route("/search/json", name="search_json")
	 */
	public function searchJsonAction(Request $request) {
		$args = $request->query->all();
		if (count($args) == 0)
			return null;
		
		$args = DefaultController::setArrayKeys($args, array('platform', 'namespaces', 'classes', 'languages', 'tags'));
		$results = $this->get('ppint.package_manager')->findPackages($args['platform'], $args['namespaces'], $args['classes'], $args['languages'], $args['tags']);
		$data = array(
			'args' => $args,
			'results' => $results,
			'query' => $this->get('ppint.package_manager')->lastQuery,
		);
		
		$json = $this->container->get('serializer')->serialize($data, 'json', SerializationContext::create()->setGroups(array('Default'))->setSerializeNull(true));
		return new Response($json, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * @Route("/stats", name="stats")
	 * @Template()
	 */
	public function statsAction(Request $request) {
		$form = $this->createForm(new SimpleSearchType(), null);
		if ('POST' === $request->getMethod()) {
			$form->handleRequest($request);
			if ($form->isValid()) {
				$data = $form->getData();
				unset($data['submit']);
				return $this->redirect($this->generateUrl('search', $data));
			}
		}
		return array(
			'searchForm' => $form->createView(),
			'platforms' => $this->getPlatformData(),
		);
	}

	public function getPlatformData() {
		/**
		 * @var EntityRepository
		 */
		$packageRepo = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package');
		$platformData = array();
		foreach ($this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Platform')->findAll() as $platform) {
			$count = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->getQuery()->getSingleScalarResult();
			$indexedCount = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.version IS NOT NULL')->andWhere('pkg.error IS NULL')->getQuery()->getSingleScalarResult();
			$errorCount = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.error IS NOT NULL')->getQuery()->getSingleScalarResult();
			$lastAdded = $packageRepo->createQueryBuilder('pkg')->where('pkg.platform = ' . $platform->getId())->orderBy('pkg.addedDate', 'DESC')->setMaxResults(12)->getQuery()->getResult();
			$lastIndexed = $packageRepo->createQueryBuilder('pkg')->where('pkg.platform = ' . $platform->getId())->orderBy('pkg.indexedDate', 'DESC')->setMaxResults(12)->getQuery()->getResult();

			$platformData[$platform->getName()] = array(
				'count' => $count,
				'indexed_count' => $indexedCount,
				'error_count' => $errorCount,
				'last_added' => $lastAdded,
				'last_indexed' => $lastIndexed,
			);
		}
		return $platformData;
	}

	/**
	 * @Route("/about", name="about")
	 * @Template()
	 */
	public function aboutAction(Request $request) {
		$form = $this->createForm(new SimpleSearchType(), null);
		if ('POST' === $request->getMethod()) {
			$form->handleRequest($request);
			if ($form->isValid()) {
				$data = $form->getData();
				unset($data['submit']);
				return $this->redirect($this->generateUrl('search', $data));
			}
		}
		return array(
			'searchForm' => $form->createView(),
		);
	}

	/**
	 * @Route("/log")
	 */
	public function crawlLogAction() {
		$html = '<html><head>';
		//$html .= '<meta http-equiv="refresh" content="5">';
		$html .= '</head><body>';
		$html .= '<pre>' . $this->tailFile(WEB_DIRECTORY . '../var/logs/ppint.log', 1000) . '</pre>';
		$html .= '</body></html>';
		return new Response($html);
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
