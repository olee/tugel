<?php

namespace Tugel\TugelBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Tugel\TugelBundle\DependencyInjection\Compiler\TagPass;

class TugelBundle extends Bundle {
	
	public function build(ContainerBuilder $container) {
		parent::build($container);
		$container->addCompilerPass(new TagPass());
	}

}
