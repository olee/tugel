<?php

namespace Zeutzheim\PpintBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use ssko\UtilityBundle\Core\ControllerHelperNT;

class DefaultController extends ControllerHelperNT {

	/**
	 * @Route("/", name="home")
	 * @Template()
	 */
	public function indexAction(Request $request) {

		$form = $this->get('form.factory')->createBuilder('form', null, array(
			'csrf_protection' => false,
		));
		$form->add('file', 'file', array(
			'label' => 'file',
			'required' => false,
		));
		$form->add('platform', 'entity', array(
			'label' => 'platform',
			'class' => 'ZeutzheimPpintBundle:Platform',
			'required' => false,
		));
		$form->add('namespaces', null, array(
			'label' => 'namespaces',
			'required' => false,
		));
		$form->add('classes', null, array(
			'label' => 'classes',
			'required' => false,
		));
		$form->add('languages', null, array(
			'label' => 'languages',
			'required' => false,
		));
		$form->add('tags', null, array(
			'label' => 'tags',
			'required' => false,
		));
		$form->add('submit', 'submit', array('label' => 'Suchen'));
		$form->setAction('#search-results');
		$form = $form->getForm();

		if ('POST' === $request->getMethod()) {
			$form->handleRequest($request);
			if ($form->isValid()) {
				$data = $form->getData();
				if ($data['file']) {
					/**
					 * @var \Symfony\Component\HttpFoundation\File\UploadedFile
					 */
					$file = $data['file'];
					$fn = $file->getClientOriginalName();
					$src = file_get_contents($file->getRealPath());
					$searchResults = $this->get('ppint.manager')->findPackagesBySource($fn, $src);
				} else {
					$searchResults = $this->get('ppint.manager')->findPackages($data['platform'], $data['namespaces'], $data['classes'], $data['languages'], $data['tags']);
				}
				$maxScore = 11;
				foreach ($searchResults as &$value) {
					$maxScore = max($value->_score, $maxScore);
				}
				if ($maxScore > 0) {
					foreach ($searchResults as &$value) {
						$value->_percentScore = $value->_score / $maxScore;
					}
				}
			} else {
				$this->reportAllFormErrors($form);
			}
		}
		
		$params = array(
			'searchForm' => $form->createView(),
			'platforms' => $this->getPlatformData(),
		);
		
		if (isset($searchResults))
		{
			$params['searchResults'] = $searchResults;
			$params['maxScore'] = $maxScore;
			$params['lastQuery'] = json_encode($this->get('ppint.manager')->lastQuery, JSON_PRETTY_PRINT);
		}
		
		return $params;
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
	public function aboutAction() {
		return array();
	}

}
