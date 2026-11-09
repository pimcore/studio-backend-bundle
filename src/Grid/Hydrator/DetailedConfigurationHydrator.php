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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;

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
        GridConfiguration $data,
        array $users,
        array $roles,
        bool $isFavorite
    ): DetailedConfiguration {

        $classId = $data->getClassId();
        $columns = $data->getColumns();
        if ($classId !== null) {
            $columns = $this->columnReferenceService->deriveIds($classId, $columns);
        }

        return new DetailedConfiguration(
            name: $data->getName(),
            description: $data->getDescription(),
            shareGlobal: $data->isShareGlobal(),
            saveFilter: $data->saveFilter(),
            setAsFavorite: $isFavorite,
            sharedUsers: $users,
            sharedRoles: $roles,
            columns: $columns,
            filter: $data->getFilter() ?? [],
            pageSize: $data->getPageSize(),
            modificationDate: $data->getModificationDate()->getTimestamp(),
            creationDate: $data->getCreationDate()->getTimestamp(),
            ownerId: $data->getOwner(),
            id: $data->getId(),
        );
    }
}
