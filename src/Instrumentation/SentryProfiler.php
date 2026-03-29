<?php

namespace Frosh\SentryBundle\Instrumentation;

use Sentry\SentrySdk;
use Sentry\Tracing\Span;
use Sentry\Tracing\SpanContext;
use Shopware\Core\Profiling\Integration\ProfilerInterface;

class SentryProfiler implements ProfilerInterface
{
    /** @var array<string, list<Span>> */
    private array $spans = [];

    /** @var list<Span|null> */
    private array $previousSpans = [];

    /**
     * @param array<string, string> $tags
     */
    public function start(string $title, string $category, array $tags): void
    {
        $parent = SentrySdk::getCurrentHub()->getSpan();

        if ($parent === null) {
            return;
        }

        $context = new SpanContext();
        $context->setOp($title);
        $context->setTags($tags);

        $span = $parent->startChild($context);

        SentrySdk::getCurrentHub()->setSpan($span);

        $this->spans[$title][] = $span;
        $this->previousSpans[] = $parent;
    }

    public function stop(string $title): void
    {
        if (!empty($this->spans[$title])) {
            array_pop($this->spans[$title])->finish();
        }

        SentrySdk::getCurrentHub()->setSpan(array_pop($this->previousSpans));
    }
}
