<?php

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Service;

interface DependencyHydratorServiceInterface
{
    public function getHydratedDependenciesForElement(string $elementType, int $elementId): array;
}