<?php

declare(strict_types=1);

namespace Frosh\SentryBundle\Subscriber;

use Sentry\MonitorConfig;
use Sentry\MonitorScheduleUnit;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSet;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\ChangeSetAware;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskDefinition;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sentry\CheckInStatus;
use Sentry\MonitorSchedule;

use function Sentry\captureCheckIn;

class ScheduledTaskSubscriber implements EventSubscriberInterface
{
    private ?ScheduledTaskCollection $scheduledTaskCollection = null;

    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     */
    public function __construct(
        private readonly EntityRepository $scheduledTaskRepository,
        private readonly bool $reportScheduledTasks,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'triggerChangeSet',
            'scheduled_task.written' => 'onScheduledTaskWritten',
        ];
    }

    public function triggerChangeSet(PreWriteValidationEvent $event): void
    {
        if (!$this->reportScheduledTasks) {
            return;
        }

        foreach ($event->getCommands() as $command) {
            if (!$command instanceof ChangeSetAware) {
                continue;
            }

            if ($command->getEntityName() !== ScheduledTaskDefinition::ENTITY_NAME) {
                continue;
            }

            $command->requestChangeSet();
        }
    }

    public function onScheduledTaskWritten(EntityWrittenEvent $event): void
    {
        if (!$this->reportScheduledTasks) {
            return;
        }

        $this->fetchScheduledTaskCollection();

        foreach ($event->getWriteResults() as $writeResult) {
            $scheduledTaskId = $writeResult->getPrimaryKey();
            if (!\is_string($scheduledTaskId)) {
                continue;
            }

            $scheduledTask = $this->scheduledTaskCollection?->get($scheduledTaskId);
            if (!$scheduledTask instanceof ScheduledTaskEntity) {
                continue;
            }

            $changeSet = $writeResult->getChangeSet();
            if (!$changeSet instanceof ChangeSet) {
                continue;
            }

            $checkInStatus = match ($changeSet->getAfter('status')) {
                ScheduledTaskDefinition::STATUS_RUNNING => CheckInStatus::inProgress(),
                ScheduledTaskDefinition::STATUS_SCHEDULED => CheckInStatus::ok(),
                ScheduledTaskDefinition::STATUS_FAILED => CheckInStatus::error(),
                default => null,
            };

            if ($checkInStatus !== null) {
                $this->captureCheckIn($scheduledTask, $checkInStatus);
            }
        }
    }

    private function captureCheckIn(ScheduledTaskEntity $scheduledTask, CheckInStatus $status): void
    {
        if ($status === CheckInStatus::inProgress()) {
            $scheduledTask->removeExtension('sentryCheckInId');
            $checkInId = $this->getCheckInId($scheduledTask);
            $scheduledTask->addArrayExtension('sentryCheckInId', [$checkInId]);
        } else {
            $checkInId = $this->getCheckInId($scheduledTask);
            captureCheckIn(slug: $scheduledTask->getName(), status: $status, checkInId: $checkInId);
        }
    }

    private function getCheckInId(ScheduledTaskEntity $scheduledTask): ?string
    {
        $extension = $scheduledTask->getExtension('sentryCheckInId');
        if ($extension instanceof ArrayStruct) {
            return \is_string($extension->offsetGet(0)) ? $extension->offsetGet(0) : null;
        }

        return captureCheckIn(
            slug: $scheduledTask->getName(),
            status: CheckInStatus::inProgress(),
            monitorConfig: $this->monitorConfig($scheduledTask),
        );
    }

    private function monitorConfig(ScheduledTaskEntity $scheduledTask): MonitorConfig
    {
        $interval = max(1, (int) ($scheduledTask->getRunInterval() / 60));
        $monitorSchedule = MonitorSchedule::interval($interval, MonitorScheduleUnit::minute());

        return new MonitorConfig($monitorSchedule);
    }

    private function fetchScheduledTaskCollection(): void
    {
        if ($this->scheduledTaskCollection instanceof ScheduledTaskCollection) {
            return;
        }

        $context = new Context(new SystemSource());
        $criteria = new Criteria();

        $this->scheduledTaskCollection = $this->scheduledTaskRepository->search($criteria, $context)->getEntities();
    }
}
