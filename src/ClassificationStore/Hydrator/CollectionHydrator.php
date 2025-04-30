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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Collection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation;

/**
 * @internal
 */
final class CollectionHydrator implements CollectionHydratorInterface
{
    public function hydrate(CollectionConfig $data): Collection
    {
        if ($data->getId() === null) {
            throw new InvalidArgumentException('The collection id must not be empty.');
        }

        $groupIds = array_map(
            static fn (CollectionGroupRelation $relation) => $relation->getGroupId(),
            $data->getRelations()
        );

        return new Collection(
            id: $data->getId(),
            name: $data->getName(),
            description: $data->getDescription(),
            groups: $groupIds,
        );
    }
}
