<?php

namespace ssko\UtilityBundle\Core;

use Symfony\Component\DependencyInjection\ContainerAware;

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

class ContainerAwareHelper extends ContainerAware {

	use HelperTrait;
	
	/**
	 * Constructor.
	 */
	public function __construct(ContainerInterface $container) {
		$this->setContainer($container);
	}

    //********************************************
    // Methods, that Controller already implements, but not ContainerAware
    
    /**
     * Get a user from the Security Context
     * @return mixed
     * @throws \LogicException If SecurityBundle is not available
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    public function getUser() {
        if (null === $token = $this->getSecurityContext()->getToken())
            return null;
        if (!is_object($user = $token->getUser()))
            return null;
        return $user;
    }
	
}
