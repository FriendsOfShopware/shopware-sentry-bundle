<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\Subscriber;

use Sentry\Options;
use Shopware\Core\Framework\Feature;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class StorefrontPageSubscriber implements EventSubscriberInterface
{
    private const HTTP_CACHE_ROUTE_ATTRIBUTE = '_httpCache';

    private const LOGGED_IN_CACHE_STATE = 'logged-in';

    /**
     * These feature flags serve cached pages to logged-in customers, see the CACHE_REWORK description in Shopware's feature.yaml.
     */
    private const FEATURE_FLAGS_CACHING_LOGGED_IN_PAGES = ['CACHE_REWORK', 'PERFORMANCE_TWEAKS'];

    /**
     * @param list<string> $httpCacheInvalidationStates
     */
    public function __construct(
        private readonly Options $sentryOptions,
        private readonly string $javascriptSdkVersion,
        private readonly bool $replayRecordingEnabled,
        private readonly float $replayRecordingSampleRate,
        private readonly bool $tracingEnabled,
        private readonly float $tracingSampleRate,
        private readonly bool $reportCustomerNumber,
        private readonly bool $httpCacheEnabled,
        private readonly array $httpCacheInvalidationStates,
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
            'user' => $this->getUser($event),
        ]);
    }

    /**
     * @return array{username: string}|null
     */
    private function getUser(StorefrontRenderEvent $event): ?array
    {
        $customer = $event->getSalesChannelContext()->getCustomer();

        if (!$this->reportCustomerNumber || $customer === null || $this->isPageSharedWithOtherCustomers($event->getRequest())) {
            return null;
        }

        return ['username' => $customer->getCustomerNumber()];
    }

    private function isPageSharedWithOtherCustomers(Request $request): bool
    {
        if (!$this->httpCacheEnabled || !$request->attributes->get(self::HTTP_CACHE_ROUTE_ATTRIBUTE)) {
            return false;
        }

        foreach (self::FEATURE_FLAGS_CACHING_LOGGED_IN_PAGES as $featureFlag) {
            if (Feature::has($featureFlag) && Feature::isActive($featureFlag)) {
                return true;
            }
        }

        return !\in_array(self::LOGGED_IN_CACHE_STATE, $this->httpCacheInvalidationStates, true);
    }
}
