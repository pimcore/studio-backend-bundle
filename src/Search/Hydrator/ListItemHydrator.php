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

use Pimcore\Bundle\StudioBackendBundle\Entity\Search\SavedSearchConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\ConfigurationListItem;

/**
 * @internal
 */
final readonly class ListItemHydrator implements ListItemHydratorInterface
{
    public function hydrate(SavedSearchConfiguration $data, bool $owner): ConfigurationListItem
    {
        return new ConfigurationListItem(
            id: $data->getId(),
            name: $data->getName(),
            description: $data->getDescription(),
            owner: $owner,
            modificationDate: $data->getModificationDate()->getTimestamp(),
            creationDate: $data->getCreationDate()->getTimestamp(),
        );
    }
}
