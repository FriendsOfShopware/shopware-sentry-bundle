<?php

namespace Frosh\SentryBundle\Listener;

use Sentry\State\HubInterface;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * This listener overwrites the URL determined by Sentry to the original URL of the request.
 */
class FixRequestUrlListener
{
    public function __construct(private readonly HubInterface $hub)
    {

    }

    public function __invoke(RequestEvent $event): void
    {
        $span = $this->hub->getSpan();

        if ($span === null) {
            return;
        }

        if ($span->getOp() !== 'http.server') {
            return;
        }

        $request = $event->getRequest();

        $url = $request->attributes->get(RequestTransformer::ORIGINAL_REQUEST_URI);

        if ($url === null) {
            return;
        }

        $span->setData(['http.url' => $request->getSchemeAndHttpHost() . $url]);
    }
}
