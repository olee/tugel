<?php

namespace Tugel\TugelBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TagPass implements CompilerPassInterface {

	public function process(ContainerBuilder $container) {
		if ($container->hasDefinition('tugel.platform_manager')) {
			$definition = $container->getDefinition('tugel.platform_manager');
			$taggedServices = $container->findTaggedServiceIds('tugel.platform');
			foreach ($taggedServices as $id => $attributes)
				$definition->addMethodCall('add', array(new Reference($id)));
		}
		
		if ($container->hasDefinition('tugel.language_manager')) {
			$definition = $container->getDefinition('tugel.language_manager');
			$taggedServices = $container->findTaggedServiceIds('tugel.language');
			foreach ($taggedServices as $id => $attributes)
				$definition->addMethodCall('add', array(new Reference($id)));
		}
	}

}
