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
		
	/**
	 * @Route("/", name="home")
	 * @Template
	 */
	public function indexAction(Request $request) {
		$params = $this->handleQueryForm($request);
		if ($params instanceof Response)
			return $params;
		
		return $params;
	}
	
	/**
	 * @Route("/search", name="search")
	 * @Template
	 */
	public function searchAction(Request $request) {
		$data = $request->query->all();
		if (empty($data['q']))
			return $this->redirect($this->generateUrl('home'));
		
		$params = $this->handleQueryForm($request, $data);
		if ($params instanceof Response)
			return $params;
		
		if (!empty($data['q'])) {
			$pm = $this->getPackageManager();
			$query = $pm->parseQuery($data['q']);
			$results = $pm->find($query, 10, $request->query->get('page', 0) * 10);
			
			$params['results'] = $results;
			$params['query'] = $query;
			$params['el_query'] = json_encode($pm->lastQuery, JSON_PRETTY_PRINT);
			$params['time'] = $pm->lastQueryTime;
		}
		return $this->render('TugelBundle:Default:search.html.twig', $params);
	}
	
	/**
	 * @Route("/search/{platform}", name="search_platform")
	 * @Template
	 */
	public function searchPlatformAction(Request $request, $platform) {
		$data = $request->query->all();
		$params = $this->handleQueryForm($request, $data);
		if ($params instanceof Response)
			return $params;
		
		if (empty($data['q']))
			$params['query'] = array('platform' => $platform);
		
		if (!empty($data['q'])) {
			$pm = $this->getPackageManager();
			$query = $pm->parseQuery($data['q']);
			$query['platform'] = $platform;
			$results = $pm->find($query, 10, $request->query->get('page', 0) * 10);
			
			$params['results'] = $results;
			$params['query'] = $query;
			$params['el_query'] = json_encode($pm->lastQuery, JSON_PRETTY_PRINT);
			$params['time'] = $pm->lastQueryTime;
		}
		return $this->render('TugelBundle:Default:search.html.twig', $params);
	}
	
	/**
	 * @Route("/info/{id}", name="info", requirements={"id"="\d+"})
	 * @Template
	 */
	public function infoAction(Request $request, $id = null) {
		$params = $this->handleQueryForm($request);
		if ($params instanceof Response)
			return $params;

		if ($id == null)
			return $this->redirect($this->generateUrl('home'));
		
		$package = $this->getPackageManager()->getPackage($id);
		if (!$package)
			return $this->redirect($this->generateUrl('home'));
		
		$params['package'] = $package;
		return $params;
	}
		
	/**
	 * @Route("/info/{platform}/{package}", name="info_named", requirements={"package"=".*"})
	 * @Template
	 */
	public function infoNamedAction(Request $request, $platform, $package) {
		$params = $this->handleQueryForm($request);
		if ($params instanceof Response)
			return $params;
		
		//echo "$platform\n$package\n"; exit;
		if ($platform == null || $package == null)
			return $this->redirect($this->generateUrl('home'));
		
		$platform = $this->getPackageManager()->getPlatformManager()->getPlatform($platform);
		$pkg = $platform->getPackage($package);
		if (!$pkg)
			return $this->redirect($this->generateUrl('home'));
		
		$params['package'] = $pkg;
		return $this->render('TugelBundle:Default:info.html.twig', $params);
	}
	
	/**
	 * @Route("/search.json", name="search_json")
	 */
	public function searchJsonAction(Request $request) {
		$groups = array('Default');
		if (!$request->query->has('q')) {
			$result = array(
				'success' => false,
				'message' => 'Missing query',
			); 
		} else {
			$pm = $this->getPackageManager();
			$query = $pm->parseQuery($request->query->get('q'));
			if ($request->query->has('g'))
				$groups[] = strtolower($request->query->get('g'));
			$results = $pm->find($query, $request->query->get('c', 10), $request->query->get('p', 0) * $request->query->get('c', 10));

			$result = array(
				'success' => true,
				'query' => $query,
				'results' => $results,
				'el_query' => $pm->lastQuery,
			);
		}
		
		$json = $this->container->get('serializer')->serialize($result, 'json', SerializationContext::create()->setGroups($groups)->setSerializeNull(true));
		return new Response($json, 200, array('Content-Type' => 'application/json'));
	}

	/**
	 * @Route("/stats", name="stats")
	 * @Template
	 */
	public function statsAction(Request $request) {
		$params = $this->handleQueryForm($request);
		if ($params instanceof Response)
			return $params;
		return array_merge($params, $this->getPackageManager()->getStats());
	}

	/**
	 * @Route("/about", name="about")
	 * @Template
	 */
	public function aboutAction(Request $request) {
		$params = $this->handleQueryForm($request);
		if ($params instanceof Response)
			return $params;
		return $params;
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

	public function handleQueryForm(Request $request, $data = null) {
		$form = $this->createForm(new SimpleSearchType(), $data);
		if ('POST' === $request->getMethod()) {
			$form->handleRequest($request);
			if ($form->isValid()) {
				$data = $form->getData();
				if (empty($data['q']))
					return $this->redirect($this->generateUrl('home'));
				return $this->redirect($this->generateUrl('search', $data));
			}
		} else {
			return array('searchForm' => $form->createView());
		}
	}
	
	/**
	 * @return PackageManager
	 */
	public function getPackageManager() {
		return $this->get('tugel.package_manager');
	}

	/**
	 * @Route("/form_test", name="form_test")
	 * @Template
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
	
}