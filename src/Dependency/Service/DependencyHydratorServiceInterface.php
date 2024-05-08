<?php

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Service;

use Pimcore\Bundle\StudioBackendBundle\Dependency\Request\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Result\ListingResult;
use Pimcore\Model\UserInterface;

interface DependencyHydratorServiceInterface
{
    public function getHydratedDependencies(
        DependencyParameters $parameters,
        UserInterface $user
    ): ListingResult;
}