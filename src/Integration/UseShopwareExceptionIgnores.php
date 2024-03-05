<?php

namespace Frosh\SentryBundle\Integration;

use Sentry\Event;
use Sentry\Integration\IntegrationInterface;
use Sentry\State\Scope;

class UseShopwareExceptionIgnores implements IntegrationInterface
{
    public function __construct(private readonly array $exceptions)
    {
    }

    public function setupOnce(): void
    {
        $exceptions = $this->exceptions;

        Scope::addGlobalEventProcessor(function (Event $event) use($exceptions): ?Event {
            $eventExceptions = $event->getExceptions()[0] ?? null;

            if ($eventExceptions === null) {
                return $event;
            }

            $config = $exceptions[$eventExceptions->getType()] ?? [];

            if (!isset($config['log_level'])) {
                return $event;
            }

            if ($config['log_level'] === 'notice') {
                return null;
            }

            return $event;
        });
    }
}
