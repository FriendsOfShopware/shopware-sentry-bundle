<?php

namespace Frosh\SentryBundle;

use Frosh\SentryBundle\DependencyInjection\FroshSentryExtension;
use Frosh\SentryBundle\Instrumentation\SentryProfiler;
use Frosh\SentryBundle\Integration\UseShopwareExceptionIgnores;
use Frosh\SentryBundle\Listener\FixRequestUrlListener;
use Frosh\SentryBundle\Listener\SalesChannelContextListener;
use Frosh\SentryBundle\Subscriber\FilterShopwareExceptionIgnoresSubscriber;
use Frosh\SentryBundle\Subscriber\FlowLogSubscriber;
use Frosh\SentryBundle\Subscriber\ScheduledTaskSubscriber;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Sentry\State\HubInterface;

class ShopwareSentryBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $this->loadConfiguration($container);

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
            ->addArgument(new Reference('event_dispatcher'));

        $container
            ->register(ScheduledTaskSubscriber::class)
            ->addArgument(new Reference('scheduled_task.repository'))
            ->addArgument('%frosh_sentry.report_scheduled_tasks%')
            ->addTag('kernel.event_subscriber');

        $container
            ->register(FilterShopwareExceptionIgnoresSubscriber::class)
            ->addArgument('%frosh_sentry.exclude_exceptions%')
            ->addTag('kernel.event_subscriber');

        $container
            ->register(FlowLogSubscriber::class)
            ->addTag('kernel.event_subscriber');

        $container->addCompilerPass(new CompilerPass\ExceptionConfigCompilerPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new FroshSentryExtension();
    }

    private function loadConfiguration(ContainerBuilder $container): void
    {
        $locator = new FileLocator('Resources/config');
        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);

        $configLoader = new DelegatingLoader($resolver);
        $confDir = \rtrim($this->getPath(), '/') . '/Resources/config';
        $configLoader->load($confDir . '/{packages}/*.yaml', 'glob');

    }
}
