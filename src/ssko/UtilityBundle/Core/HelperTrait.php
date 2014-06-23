<?php

namespace ssko\UtilityBundle\Core;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

// Service helpers
/*
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;
use Symfony\Component\Templating\Asset\PackageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
*/

trait HelperTrait {
	
	protected $em;
	protected $router;
	protected $templating;
	protected $session;
	
    //********************************************

	public static function getAllFormErrors($form) {
		$errors = $form->getErrors();
		foreach ($form as $child) {
			$errors = array_merge($errors, self::getAllFormErrors($child));
		}
		return array($form->getName() => $errors);
	}

	public function reportErrors($errors, $path = 'formerror') {
		foreach ($errors as $key => $error) {
			if (is_array($error)) {
				$this->reportFormErrors($error, $path . '_' . $key);
			} else {
				$this->getSession()->getFlashBag()->add($path, $error->getMessage());
			}
		}
	}

	public function reportAllFormErrors($form, $path = 'formerror') {
		$path .= '_' . $form->getName();
		$flashBag = $this->getSession()->getFlashBag();
		$translator = $this->getTranslator();
		foreach ($form->getErrors() as $error) {
			$flashBag->add($path, $translator->trans($error->getMessage()));
		}
		foreach ($form as $child) {
			$this->reportAllFormErrors($child, $path);
		}
	}

    //********************************************

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        if (!isset($this->em))
            $this->em = $this->container->get('doctrine.orm.entity_manager');
        return $this->em;
    }

    /**
     * @return \Symfony\Component\Routing\Router
     */
    public function getRouter() {
		if (!isset($this->router))
			$this->router = $this->container->get('router');
		return $this->router;
    }

	/**
	 * @return \Symfony\Bundle\TwigBundle\TwigEngine
	 */
	public function getTemplating() {
		if (!isset($this->templating))
			$this->templating = $this->container->get('templating');
		return $this->templating;
	}
	
	/**
	 * @return \Symfony\Component\HttpFoundation\Session\SessionInterface
	 */
	public function getSession() {
		if (!isset($this->session))
			$this->session = $this->container->get('session');
		return $this->session;
	}

	//********************************************

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    public function getTranslator() {
        return $this->container->get('translator');
    }

    /**
     * @return \Symfony\Component\Templating\Asset\PackageInterface
     */
    public function getAssetHelper() {
        return $this->container->get('templating.helper.assets');
    }

	/**
	 * @return \Symfony\Component\Form\FormFactoryInterface
	 */
	public function getFormFactory() {
		return $this->container->get('form.factory');
	}

    /**
     */
    public function getSecurityContext() {
        return $this->container->get('security.context');
    }

}
