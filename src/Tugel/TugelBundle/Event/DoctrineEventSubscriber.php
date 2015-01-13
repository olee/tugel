<?php

namespace Tugel\TugelBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\DependencyInjection\ContainerAware;

use Tugel\TugelBundle\Entity\Package;
use Tugel\TugelBundle\Entity\Platform;

class DoctrineEventSubscriber extends ContainerAware implements EventSubscriber {

	/**
	 * Constructor
	 */
	public function __construct($service_container) {
		$this->setContainer($service_container);
		$this->changedProducts = array();
		$this->deletedProducts = array();
	}

	public function getSubscribedEvents() {
		return array(
			//'postFlush',
			//'prePersist',
			//'preUpdate',
			//'preRemove',
			//'postPersist',
			//'postUpdate',
			//'postRemove',
			'postLoad',
		);
	}
	
	public function postLoad(LifecycleEventArgs $args) {
		/**
		 * @var Platform
		 */
		$entity = $args->getEntity();
		if ($entity instanceof Platform) {
			$entity->platform = $this->container->get('tugel.platform_manager')->getPlatform($entity->getName());
		};
	}

}
