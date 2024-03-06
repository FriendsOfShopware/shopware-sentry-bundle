<?php

namespace Frosh\SentryBundle\CompilerPass;

use Symfony\Bundle\FrameworkBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExceptionConfigCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $config = $this->getConfig($container, 'framework');

        $container->setParameter('frosh_sentry.exclude_exceptions', $config['exceptions']);
    }

    /**
     * @return array<mixed>
     */
    private function getConfig(ContainerBuilder $container, string $bundle): array
    {
        return (new Processor())
            ->processConfiguration(
                new Configuration($container->getParameter('kernel.debug')),
                $container->getExtensionConfig($bundle)
            );
    }
}
