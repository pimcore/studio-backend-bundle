<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Search\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DetailedConfiguration;

/**
 * @internal
 */
final readonly class DetailedConfigurationHydrator implements DetailedConfigurationHydratorInterface
{
    public function __construct(
        private ColumnReferenceServiceInterface $columnReferenceService,
    ) {
    }

    public function hydrate(
        SavedSearchConfiguration $data,
        array $users,
        array $roles
    ): DetailedConfiguration {
        $classId = $data->getClassId();
        $columns = $data->getColumns();
        $filter = $data->getFilter();
        if ($classId !== null) {
            $columns = $this->columnReferenceService->deriveIds($classId, $columns);
            $filter = $filter === null ? null : $this->columnReferenceService->deriveFilterIds($classId, $filter);
        }

        return new DetailedConfiguration(
            id: $data->getId(),
            ownerId: $data->getOwner(),
            name: $data->getName(),
            description: $data->getDescription(),
            shareGlobal: $data->isShareGlobal(),
            sharedUsers: $users,
            sharedRoles: $roles,
            createMenuShortcut: $data->isCreateMenuShortcut(),
            menuShortcutGroup: $data->getMenuShortcutGroup(),
            classId: $classId,
            elementType: $data->getElementType(),
            columns: $columns,
            filter: $filter,
            modificationDate: $data->getModificationDate()->getTimestamp(),
            creationDate: $data->getCreationDate()->getTimestamp(),
        );
    }
}
