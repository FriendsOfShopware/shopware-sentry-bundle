<?php

namespace Frosh\SentryBundle\Listener;

use Sentry\State\Scope;
use Shopware\Core\System\SalesChannel\Event\SalesChannelContextCreatedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Service\ResetInterface;

use function Sentry\configureScope;

class SalesChannelContextListener implements ResetInterface
{
    private ?SalesChannelContext $context = null;

    private bool $registered = false;

    public function __invoke(SalesChannelContextCreatedEvent $event): void
    {
        $this->context = $event->getSalesChannelContext();

        if (!$this->registered) {
            $this->registered = true;
            configureScope([$this, 'configureScopeWithContext']);
        }
    }

    /**
     * @internal
     */
    public function configureScopeWithContext(Scope $scope): void
    {
        if ($this->context === null) {
            return;
        }

        if ($customer = $this->context->getCustomer()) {
            $scope->setUser(['id' => $customer->getId()]);
        }

        $scope->setContext('Sales Channel', [
            'id' => $this->context->getSalesChannel()->getId(),
            'name' => $this->context->getSalesChannel()->getName(),
            'languageId' => $this->context->getSalesChannel()->getLanguageId(),
            'currencyId' => $this->context->getSalesChannel()->getCurrencyId(),
        ]);
    }

    public function reset(): void
    {
        $this->context = null;
    }
}
