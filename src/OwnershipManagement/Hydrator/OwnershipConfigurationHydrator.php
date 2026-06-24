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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;

/**
 * @internal
 */
final readonly class OwnershipConfigurationHydrator implements OwnershipConfigurationHydratorInterface
{
    public function hydrate(
        string $id,
        string $type,
        string $name,
        int $ownerId,
        ?string $ownerName,
        ?int $creationDate = null,
        ?int $modificationDate = null,
    ): OwnershipConfiguration {
        return new OwnershipConfiguration(
            $id,
            $type,
            $name,
            $ownerId,
            $ownerName,
            $ownerName === null,
            $creationDate,
            $modificationDate,
        );
    }
}
