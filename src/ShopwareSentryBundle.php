<?php

namespace Frosh\SentryBundle;

use Frosh\SentryBundle\Instrumentation\SentryProfiler;
use Frosh\SentryBundle\Integration\UseShopwareExceptionIgnores;
use Frosh\SentryBundle\Listener\FixRequestUrlListener;
use Frosh\SentryBundle\Listener\SalesChannelContextListener;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Sentry\State\HubInterface;

class ShopwareSentryBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->register(SalesChannelContextListener::class)
            ->addTag('kernel.event_listener', ['event' => SalesChannelContextCreatedEvent::class, 'method' => '__invoke'])
            ->addTag('kernel.reset', ['method' => 'reset']);

        $container
            ->register(FixRequestUrlListener::class)
            ->addArgument(new Reference(HubInterface::class))
            ->addTag('kernel.event_listener', ['event' => RequestEvent::class, 'method' => '__invoke', 'priority' => 3]);

        $container
            ->register(SentryProfiler::class)
            ->addTag('shopware.profiler', ['integration' => 'Sentry']);

        $container
            ->register(UseShopwareExceptionIgnores::class)
            ->addArgument('%frosh_sentry.exclude_exceptions%');

        $container->addCompilerPass(new CompilerPass\ExceptionConfigCompilerPass());
    }
}
