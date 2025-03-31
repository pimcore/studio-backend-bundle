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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Collection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;

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

        return new Collection(
            id: $data->getId(),
            name: $data->getName(),
            description: $data->getDescription(),
        );
    }
}