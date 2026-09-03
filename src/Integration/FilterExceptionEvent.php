<?php

namespace Frosh\SentryBundle\Integration;

use Sentry\ExceptionDataBag;
use Symfony\Contracts\EventDispatcher\Event;

class FilterExceptionEvent extends Event
{

    /**
     * @param ExceptionDataBag[] $exceptions
     */
    public function __construct(private array $exceptions)
    {
    }

    /**
     * @return ExceptionDataBag[]
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * @param ExceptionDataBag[] $exceptions
     */
    public function setExceptions(array $exceptions): void
    {
        $this->exceptions = $exceptions;
    }



}
