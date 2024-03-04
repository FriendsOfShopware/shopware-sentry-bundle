<?php

namespace Frosh\SentryBundle\CompilerPass;

use Shopware\Core\Framework\DependencyInjection\CompilerPass\CompilerPassConfigTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExceptionConfigCompilerPass implements CompilerPassInterface
{
    use CompilerPassConfigTrait;

    public function process(ContainerBuilder $container): void
    {
        $config = $this->getConfig($container, 'framework');

        $container->setParameter('frosh_sentry.exclude_exceptions', $config['exceptions']);
    }
}
