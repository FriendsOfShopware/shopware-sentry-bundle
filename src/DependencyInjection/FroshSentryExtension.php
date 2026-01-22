<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\DependencyInjection;

use Frosh\SentryBundle\Subscriber\StorefrontPageSubscriber;
use Sentry\Options;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class FroshSentryExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{report_scheduled_tasks: bool, storefront?: array{enabled: bool, javascript_sdk_version: string, replay_recording?: array{enabled: bool, sample_rate: float}, tracing?: array{enabled: bool, sample_rate: float}}} $config */
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter('frosh_sentry.report_scheduled_tasks', $config['report_scheduled_tasks']);

        $this->registerStorefrontConfiguration($config['storefront'] ?? [], $container);
    }

    /**
     * @param array<mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    /**
     * @param array{enabled?: bool, javascript_sdk_version?: string, replay_recording?: array{enabled?: bool, sample_rate?: float}, tracing?: array{enabled?: bool, sample_rate?: float}} $config
     */
    private function registerStorefrontConfiguration(array $config, ContainerBuilder $container): void
    {
        if (!($config['enabled'] ?? false)) {
            return;
        }

        $container
            ->register(StorefrontPageSubscriber::class)
            ->setArguments([
                new Reference('sentry.client.options'),
                $config['javascript_sdk_version'] ?? '8.26.0',
                $config['replay_recording']['enabled'] ?? false,
                $config['replay_recording']['sample_rate'] ?? 0.1,
                $config['tracing']['enabled'] ?? false,
                $config['tracing']['sample_rate'] ?? 0.1,
            ])
            ->addTag('kernel.event_subscriber');
    }
}
