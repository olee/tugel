<?php

namespace Zeutzheim\PpintBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TagPass implements CompilerPassInterface {

	public function process(ContainerBuilder $container) {
		if ($container->hasDefinition('ppint.platform_manager')) {
			$definition = $container->getDefinition('ppint.platform_manager');
			$taggedServices = $container->findTaggedServiceIds('ppint.platform');
			foreach ($taggedServices as $id => $attributes)
				$definition->addMethodCall('addPlatform', array(new Reference($id)));
		}
		
		if ($container->hasDefinition('ppint.language_manager')) {
			$definition = $container->getDefinition('ppint.language_manager');
			$taggedServices = $container->findTaggedServiceIds('ppint.language');
			foreach ($taggedServices as $id => $attributes)
				$definition->addMethodCall('addLanguage', array(new Reference($id)));
		}
	}

}
