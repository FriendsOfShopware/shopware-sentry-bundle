<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\Subscriber;

use Frosh\SentryBundle\Exception\FlowEventException;
use Monolog\Level;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Event\FlowLogEvent;
use Shopware\Core\Framework\Log\LogAware;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function Sentry\captureException;

class FlowLogSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [FlowLogEvent::NAME => 'sendFlowEvent'];
    }

    public function sendFlowEvent(FlowLogEvent $event): void
    {
        $innerEvent = $event->getEvent();

        $additionalData = [];
        $logLevel = Level::Debug;

        if ($innerEvent instanceof LogAware) {
            $logLevel = $innerEvent->getLogLevel();
            $additionalData = $innerEvent->getLogData();
        }

        // Fallback for Shopware 6.5 which returns an integer for LogAware::getLogLevel()
        if (is_int($logLevel)) {
            $logLevel = Level::tryFrom($logLevel);
        }

        if ($logLevel === null || $logLevel->isLowerThan(Level::Warning)) {
            return;
        }

        $nestedException = null;
        if (method_exists($innerEvent, 'getThrowable')) {
            $nestedException = $innerEvent->getThrowable();
        }

        captureException(new FlowEventException($innerEvent->getName(), $logLevel, $nestedException, $additionalData));
    }

}
