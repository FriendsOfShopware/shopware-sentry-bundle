<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\Exception;

use Monolog\Level;
use Throwable;

class FlowEventException extends \Exception
{
    public function __construct(public readonly string $eventName = '', ?Level $level = null, ?Throwable $previous = null, public readonly mixed $context = null)
    {
        $message = sprintf('Flow event %s level %s occurred', $this->eventName, $level?->toPsrLogLevel() ?? 'unknown');
        parent::__construct($message, $level?->value ?? 0, $previous);
    }

}
