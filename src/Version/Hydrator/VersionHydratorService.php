<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Version\Hydrator;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Version\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Request\VersionParameters;
use Pimcore\Bundle\StudioBackendBundle\Version\Result\ListingResult;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DataObjectVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\DocumentVersion;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final readonly class VersionHydratorService implements VersionHydratorServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RepositoryInterface $repository,
        private ServiceResolverInterface $serviceResolver,
        private ServiceProviderInterface $versionHydratorLocator,
        private VersionHydratorInterface $versionHydrator,
    ) {
    }

    public function getHydratedVersions(
        VersionParameters $parameters,
        UserInterface $user
    ): ListingResult {
        $element = $this->getElement(
            $this->serviceResolver,
            $parameters->getElementType(),
            $parameters->getElementId(),
        );
        $scheduledTasks = $this->getScheduledTasks($element);
        $list = $this->repository->listVersions($element, $parameters, $user);
        $versions = [];
        $versionObjects = $list->load();
        foreach ($versionObjects as $versionObject) {
            $versions[] = $this->versionHydrator->hydrate($versionObject, $scheduledTasks);

        }

        return new ListingResult(
            $versions,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $list->getTotalCount()
        );
    }

    public function getHydratedVersionData(
        int $id,
        UserInterface $user
    ): AssetVersion|DataObjectVersion|DocumentVersion {
        $version = $this->repository->getVersionById($id);
        $element = $this->repository->getElementFromVersion($version, $user);

        return $this->hydrate(
            $element,
            $this->getElementClass($element)
        );
    }

    private function hydrate(
        ElementInterface $element,
        string $class
    ): AssetVersion|DocumentVersion|DataObjectVersion {
        if ($this->versionHydratorLocator->has($class)) {
            return $this->versionHydratorLocator->get($class)->hydrate($element);
        }

        throw new InvalidElementTypeException($class);
    }

    private function getScheduledTasks(ElementInterface $element): array
    {
        $scheduledTasks = $element->getScheduledTasks();
        $schedules = [];
        foreach ($scheduledTasks as $task) {
            if ($task->getActive()) {
                $schedules[$task->getVersion()] = $task->getDate();
            }
        }

        return $schedules;
    }
}
