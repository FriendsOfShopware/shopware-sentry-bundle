<?php

namespace Frosh\SentryBundle\Subscriber;

use Frosh\SentryBundle\Integration\FilterExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterShopwareExceptionIgnoresSubscriber implements EventSubscriberInterface
{

    public function __construct(private readonly array $shopwareExceptionConfig)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            FilterExceptionEvent::class => 'filterSentryExceptions',
        ];
    }


    public function filterSentryExceptions(FilterExceptionEvent $event): void
    {
        $filteredExceptions = [];
        foreach ($event->getExceptions() as $exception) {

            $exceptionLogLevel = $this->shopwareExceptionConfig[$exception->getType()]['log_level'] ?? null;

            if ($exceptionLogLevel === 'notice') {
                continue;
            }

            $filteredExceptions[] = $exception;
        }

        $event->setExceptions($filteredExceptions);


    }
}
