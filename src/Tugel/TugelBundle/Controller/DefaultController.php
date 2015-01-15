<?php

namespace Tugel\TugelBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

use JMS\Serializer\SerializationContext;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use ssko\UtilityBundle\Core\ControllerHelperNT;

use Tugel\TugelBundle\Form\SearchType;
use Tugel\TugelBundle\Form\SimpleSearchType;

use Tugel\TugelBundle\Model\PackageManager;

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
	 * @Route("/form_test", name="form_test")
	 * @Template()
	 */
	public function formTestAction(Request $request) {
        $builder = $this->createFormBuilder();
        $choices = array('choice a', 'choice b', 'choice c');
        $form = $builder
            ->add('submit2', 'submit', array('attr' => array('formnovalidate' => true)))
            ->add('submit3', 'submit', array('attr' => array('formnovalidate' => true)))
            ->add('text1', 'text', array(
                'constraints' => new Assert\NotBlank(),
                'attr' => array('placeholder' => 'not blank constraints')
            ))
            ->add('textarea', 'textarea')
            ->add('email', 'email')
            ->add('integer', 'integer')
            ->add('money', 'money', array(
                'currency' => 'EUR',
            ))
            ->add('money2', 'money', array(
                'currency' => 'USD',
            ))
            ->add(
                $builder->create('sub-form', 'form')
                    ->add('subformemail1', 'email', array(
                        'constraints' => array(new Assert\NotBlank(), new Assert\Email()),
                        'attr' => array('placeholder' => 'email constraints'),
                        'label' => 'A custom label : ',
                    ))
                    ->add('subformtext1', 'text')
            )

            ->add('number', 'number')
            ->add('password', 'password')
            ->add('percent', 'percent')
            ->add('search', 'search')
            ->add('url', 'url')

            ->add('choice1', 'choice', array(
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('choice1B', 'choice', array(
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'label_attr' => array('class' => 'checkbox-inline'),
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('choice1C', 'choice', array(
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'label' => false,
                'label_attr' => array('class' => 'checkbox-inline'),
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('choice2', 'choice', array(
                'choices' => $choices,
                'multiple' => false,
                'expanded' => true,
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('choice3', 'choice', array(
                'choices' => $choices,
                'multiple' => true,
                'expanded' => false,
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('choice4', 'choice', array(
                'choices' => $choices,
                'multiple' => false,
                'expanded' => false,
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('checkbox', 'checkbox', array(
                'constraints' => new Assert\True(),
            ))
            ->add('radio', 'radio', array(
                'constraints' => new Assert\True(),
            ))

            ->add('country', 'country')
            ->add('language', 'language')
            ->add('locale', 'locale')
            ->add('timezone', 'timezone', array(
                // I know, this do not make sens, but I want an error
                'constraints' => new Assert\True(),
            ))
            ->add('date', 'date', array(
                // I know, this do not make sens, but I want an error
                'constraints' => new Assert\True(),
            ))
            ->add('date_single_text', 'date', array(
                'widget' => 'single_text',
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('datetime', 'datetime', array(
                // I know, this do not make sens, but I want an error
                'constraints' => new Assert\True(),
            ))
            ->add('datetime_single_text', 'datetime', array(
                'widget' => 'single_text',
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('time', 'time', array(
                // I know, this do not make sens, but I want an error
                'constraints' => new Assert\True(),
            ))
            ->add('time_single_text', 'time', array(
                'widget' => 'single_text',
                'constraints' => new Assert\NotBlank(),
            ))
            ->add('birthday', 'birthday', array(
                // I know, this do not make sens, but I want an error
                'constraints' => new Assert\True(),
            ))
            ->add('file', 'file')
            ->add('password_repeated', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array('required' => true),
                'first_options' => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
            ))
            ->add('submit', 'submit', array('attr' => array('formnovalidate' => true)))
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->redirect($this->generateUrl('test'));
            } else {
                $form->addError(new FormError('This is a global error'));
            }
        }
		return array(
			'form' => $form->createView(),
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
			$query = $this->getQueryData($data['q']);
			
			/**
			 * @var PackageManager
			 */
			$pm = $this->get('tugel.package_manager');
			
			$results = $pm->findPackages($query['platform'], null, null, $query['language'], $query['query']);
			$params['results'] = $results;
			$params['query'] = $query;
			$params['el_query'] = json_encode($pm->lastQuery, JSON_PRETTY_PRINT);
			$params['time'] = $pm->lastQueryTime;
		}
		return $params;
	}
		
	/**
	 * @Route("/search.json", name="search_json")
	 */
	public function searchJsonAction(Request $request) {
		$data = $request->query->all();
		if (empty($data['q'])) {
			$result = array(
				'success' => false,
				'message' => 'Missing query',
			); 
		} else {
			$query = $this->getQueryData($data['q']);
			$pm = $this->get('tugel.package_manager');
			$results = $pm->findPackages($query['platform'], null, null, $query['language'], $query['query']);
			
			$result = array(
				'query' => $query,
				'results' => $results,
				'el_query' => $pm->lastQuery,
			);
		}
		
		$json = $this->container->get('serializer')->serialize($result, 'json', SerializationContext::create()->setGroups(array('Default'))->setSerializeNull(true));
		return new Response($json, 200, array('Content-Type' => 'application/json'));
	}
	
	public function getQueryData($rawQuery) {
		$query = $rawQuery;
		
		if (preg_match('/platform:([^\\s]+)/i', $query, $matches)) {
			$platform = $matches[1];
			$query = trim(str_replace($matches[0], '', $query));
		} else {
			$platform = null;
		}
		
		if (preg_match('/language:([^\\s]+)/i', $query, $matches)) {
			$language = $matches[1];
			$query = trim(str_replace($matches[0], '', $query));
		} else {
			$language = null;
		}
		
		return array(
			'raw' => $rawQuery,
			'query' => $query,
			'platform' => $platform,
			'language' => $language,
		);
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
		$packageRepo = $this->getEntityManager()->getRepository('TugelBundle:Package');
		$platformData = array();
		foreach ($this->getEntityManager()->getRepository('TugelBundle:Platform')->findAll() as $platform) {
			$count = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->getQuery()->getSingleScalarResult();
			$indexedCount = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.version IS NOT NULL')->andWhere('pkg.error IS NULL')->getQuery()->getSingleScalarResult();
			$errorCount = (int)$packageRepo->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.error IS NOT NULL')->getQuery()->getSingleScalarResult();
			$lastAdded = $packageRepo->createQueryBuilder('pkg')->where('pkg.platform = ' . $platform->getId())->orderBy('pkg.addedDate', 'DESC')->setMaxResults(4)->getQuery()->getResult();
			$lastIndexed = $packageRepo->createQueryBuilder('pkg')->where('pkg.platform = ' . $platform->getId())->orderBy('pkg.indexedDate', 'DESC')->setMaxResults(8)->getQuery()->getResult();

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
		$html .= '<pre>' . $this->tailFile(WEB_DIRECTORY . '../var/logs/tugel.log', 1000) . '</pre>';
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