<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\Subscriber;

use Sentry\Options;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorefrontPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Options $sentryOptions,
        private readonly string $javascriptSdkVersion,
        private readonly bool $replayRecordingEnabled,
        private readonly float $replayRecordingSampleRate,
        private readonly bool $tracingEnabled,
        private readonly float $tracingSampleRate,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onRender',
        ];
    }

    public function onRender(StorefrontRenderEvent $event): void
    {
        if ($this->sentryOptions->getDsn() === null) {
            return;
        }

        if ($this->replayRecordingEnabled && $this->tracingEnabled) {
            $jsFile = 'bundle.tracing.replay.min.js';
        } elseif ($this->replayRecordingEnabled) {
            $jsFile = 'bundle.replay.min.js';
        } elseif ($this->tracingEnabled) {
            $jsFile = 'bundle.tracing.min.js';
        } else {
            $jsFile = 'bundle.min.js';
        }

        $event->setParameter('sentry', [
            'dsn' => $this->sentryOptions->getDsn(),
            'javascript_src' => sprintf('https://browser.sentry-cdn.com/%s/%s', $this->javascriptSdkVersion, $jsFile),
            'replay_recording' => [
                'enabled' => $this->replayRecordingEnabled,
                'sample_rate' => $this->replayRecordingSampleRate,
            ],
            'tracing' => [
                'enabled' => $this->tracingEnabled,
                'sample_rate' => $this->tracingSampleRate,
            ],
            'release' => $this->sentryOptions->getRelease(),
            'environment' => $this->sentryOptions->getEnvironment(),
        ]);
    }
}
