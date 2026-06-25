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
 * Builds a normalized OwnershipConfiguration row from already-resolved owner data, deriving the
 * "owner deleted" flag from a null owner name. Owner names are resolved in bulk by the caller
 * (see UserServiceInterface::getUserNamesByIds) to avoid per-row user lookups.
 */
interface OwnershipConfigurationHydratorInterface
{
    public function hydrate(
        string $id,
        string $type,
        string $name,
        int $ownerId,
        ?string $ownerName,
        ?int $creationDate = null,
        ?int $modificationDate = null,
    ): OwnershipConfiguration;
}
