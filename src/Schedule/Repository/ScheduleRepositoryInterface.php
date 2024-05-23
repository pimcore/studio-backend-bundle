<?php

namespace Pimcore\Bundle\StudioBackendBundle\Schedule\Repository;

interface ScheduleRepositoryInterface
{
    public function listSchedules(string $elementType, int $id): array;
}