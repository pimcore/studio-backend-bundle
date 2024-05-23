<?php

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Repository;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\Schedule\Task;

final readonly class ScheduleRepository implements ScheduleRepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(private ServiceResolverInterface $serviceResolver)
    {

    }

    /**
     * @return array<int, Task>
     */
    public function listSchedules(string $elementType, int $id): array
    {
        return $this->getElement($this->serviceResolver, $elementType, $id)->getScheduledTasks();
    }
}