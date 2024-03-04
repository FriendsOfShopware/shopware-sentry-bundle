<?php

namespace Frosh\SentryBundle\Instrumentation;

use Sentry\SentrySdk;
use Sentry\Tracing\Span;
use Sentry\Tracing\SpanContext;
use Shopware\Core\Profiling\Integration\ProfilerInterface;

class SentryProfiler implements ProfilerInterface
{
    /**
     * @var array<string, Span>
     */
    private array $spans = [];

    /** @var array<Span> */
    private array $previousSpans = [];

    public function start(string $title, string $category, array $tags): void
    {
        $parent = SentrySdk::getCurrentHub()->getSpan();

        if ($parent !== null) {
            $context = new SpanContext();
            $context->setOp($title);
            $context->setTags(array_values($tags));
            $span = $parent->startChild($context);

            SentrySdk::getCurrentHub()->setSpan($span);

            $this->spans[$title] = $span;
            $this->previousSpans[] = $parent;
        }
    }

    public function stop(string $title): void
    {
        if (isset($this->spans[$title])) {
            $this->spans[$title]->finish();
            unset($this->spans[$title]);
        }

        SentrySdk::getCurrentHub()->setSpan(array_pop($this->previousSpans));
    }
}
