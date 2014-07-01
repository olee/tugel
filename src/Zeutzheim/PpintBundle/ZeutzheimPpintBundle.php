<?php

namespace Zeutzheim\PpintBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Zeutzheim\PpintBundle\DependencyInjection\Compiler\CrawlerTagPass;

class ZeutzheimPpintBundle extends Bundle {
	
	public function build(ContainerBuilder $container) {
		parent::build($container);
		$container->addCompilerPass(new CrawlerTagPass());
	}

}
