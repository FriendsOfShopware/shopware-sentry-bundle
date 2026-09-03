<?php

namespace Frosh\SentryBundle\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\State\Scope;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UseShopwareExceptionIgnores implements IntegrationInterface
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function setupOnce(): void
    {
        $eventDispatcher = $this->eventDispatcher;

        Scope::addGlobalEventProcessor(static function (Event $event) use ($eventDispatcher): ?Event {
            $exceptions = $event->getExceptions();
            if (empty($exceptions)) {
                return $event;
            }
            $exceptions = $eventDispatcher->dispatch(new FilterExceptionEvent($exceptions))->getExceptions();
            $event->setExceptions($exceptions);

            return $event;
        });
    }
}
