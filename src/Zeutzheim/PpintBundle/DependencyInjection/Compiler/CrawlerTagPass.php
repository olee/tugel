<?php

namespace Zeutzheim\PpintBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CrawlerTagPass implements CompilerPassInterface {

	public function process(ContainerBuilder $container) {
		if ($container->hasDefinition('ppint.crawler_manager')) {
			$definition = $container->getDefinition('ppint.crawler_manager');

			$taggedServices = $container->findTaggedServiceIds('ppint.crawler');
			foreach ($taggedServices as $id => $attributes)
				$definition->addMethodCall('addCrawler', array(new Reference($id)));
		}
	}

}
