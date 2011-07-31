<?php

namespace BeSimple\I18nRoutingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideRoutingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $routerReal = $container->findDefinition('router');
        $arguments  = $routerReal->getArguments();
        
        $container->setAlias('router', 'i18n_routing.router');


        $i18nRoutingRouter = $container->findDefinition('i18n_routing.router');
        $i18nRoutingRouter->replaceArgument(3, $arguments[1]);
    }
}
