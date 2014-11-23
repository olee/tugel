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

		$form = $this->get('form.factory')->createBuilder('form');
		$form->add('file', 'file', array(
			'label' => 'file',
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
					$searchResults = $this->get('ppint.manager')->findPackages($data['namespaces'], $data['classes'], $data['languages'], $data['tags']);
				}
			} else {
				$this->reportAllFormErrors($form);
			}
		}

		return array(
			'searchForm' => $form->createView(),
			'platforms' => $this->getPlatformData(),
			'searchResults' => isset($searchResults) ? $searchResults : null,
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
			$indexedCount = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.indexed = true')->getQuery()->getSingleScalarResult();
			$errorCount = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.error = true')->getQuery()->getSingleScalarResult();
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
