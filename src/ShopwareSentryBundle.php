<?php

namespace Frosh\SentryBundle;

use Frosh\SentryBundle\Instrumentation\SentryProfiler;
use Frosh\SentryBundle\Listener\SalesChannelContextListener;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ShopwareSentryBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->register(SalesChannelContextListener::class)
            ->addTag('kernel.event_listener', ['event' => SalesChannelContextCreatedEvent::class, 'method' => '__invoke'])
            ->addTag('kernel.reset', ['method' => 'reset']);

        $container
            ->register(SentryProfiler::class)
            ->addTag('shopware.profiler', ['integration' => 'Sentry']);
    }
}
