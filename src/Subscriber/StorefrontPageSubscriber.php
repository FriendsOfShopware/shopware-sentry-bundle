<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\Subscriber;

use Sentry\Options;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly Options $sentryOptions,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onRender',
        ];
    }

    public function onRender(StorefrontRenderEvent $event): void
    {
        if ($this->sentryOptions->getDsn() == null
            || !$this->container->hasParameter('frosh_sentry.storefront.enabled')
            || !$this->container->getParameter('frosh_sentry.storefront.enabled')
        ) {
            return;
        }

        $jsSdkVersion = $this->container->getParameter('frosh_sentry.storefront.javascript_sdk_version');
        if (!is_string($jsSdkVersion)) {
            return;
        }

        $isReplayRecordingEnabled = $this->container->getParameter('frosh_sentry.storefront.replay_recording.enabled');
        $isPerformanceTracingEnabled = $this->container->getParameter('frosh_sentry.storefront.tracing.enabled') === true;

        if ($isReplayRecordingEnabled && $isPerformanceTracingEnabled) {
            $jsFile = 'bundle.tracing.replay.min.js';
        } elseif ($isReplayRecordingEnabled && !$isPerformanceTracingEnabled) { /* @phpstan-ignore-line booleanNot.alwaysTrue / keep this for better readability */
            $jsFile = 'bundle.replay.min.js';
        } elseif (!$isReplayRecordingEnabled && $isPerformanceTracingEnabled) {
            $jsFile = 'bundle.tracing.min.js';
        } elseif (!$isReplayRecordingEnabled && !$isPerformanceTracingEnabled) {
            $jsFile = 'bundle.min.js';
        } else {
            // this case should never happen
            return;
        }

        $replaySample = $this->container->getParameter('frosh_sentry.storefront.replay_recording.sample_rate');
        $tracingSample = $this->container->getParameter('frosh_sentry.storefront.tracing.sample_rate');

        $event->setParameter('sentry', [
            'dsn' => $this->sentryOptions->getDsn(),
            'javascript_src' => sprintf("https://browser.sentry-cdn.com/%s/%s", $jsSdkVersion, $jsFile),
            'replay_recording' => [
                'enabled' => $isReplayRecordingEnabled,
                'sample_rate' => is_numeric($replaySample) ? (float) $replaySample : 0.1,
            ],
            'tracing' => [
                'enabled' => $isPerformanceTracingEnabled,
                'sample_rate' => is_numeric($tracingSample) ? (float) $tracingSample : 0.1,
            ],
            'release' => $this->sentryOptions->getRelease(),
            'environment' => $this->sentryOptions->getEnvironment(),
        ]);
    }
}
