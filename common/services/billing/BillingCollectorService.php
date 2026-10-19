<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\services\billing\collector\DomainBillingCollector;
use common\services\billing\collector\HostingBillingCollector;
use common\services\billing\collector\MaintenanceBillingCollector;
use DateTimeImmutable;

final class BillingCollectorService
{

    public function __construct(
            private readonly DomainBillingCollector $domainCollector = new DomainBillingCollector(),
            private readonly HostingBillingCollector $hostingCollector = new HostingBillingCollector(),
            private readonly MaintenanceBillingCollector $maintenanceCollector = new MaintenanceBillingCollector(),
            private readonly BillingEventService $eventService = new BillingEventService()
    )
    {
        
    }

    public function collect(
            int $runId,
            ?DateTimeImmutable $today = null
    ): array
    {
        if ($runId <= 0) {
            throw new \InvalidArgumentException(
                            'A valid billing run ID is required.'
                    );
        }

        $today ??= new DateTimeImmutable('today');

        $renewals = array_merge(
                $this->domainCollector->collect(
                        $today
                ),
                $this->hostingCollector->collect(
                        $today
                ),
                $this->maintenanceCollector->collect(
                        $today
                )
        );

        $events = [];

        foreach ($renewals as $renewal) {
            $event = $this->eventService->create(
                    $runId,
                    $renewal
            );

            // An event created by an earlier billing run
            // must remain attached to that original run.
            if (
                    (int) $event->billing_run_id !== $runId
            ) {
                continue;
            }

            $events[] = $event;
        }

        return $events;
    }
}
