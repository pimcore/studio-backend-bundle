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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * Implement and tag a service with OWNERSHIP_PROVIDER_TAG to expose a new
 * user-owned configuration type (tab) in the ownership management area.
 * The implementation may live in any bundle that depends on the StudioBackendBundle.
 */
interface OwnershipProviderInterface
{
    /**
     * Unique, machine-readable type identifier used in the API routes (e.g. "grid_configuration").
     */
    public function getType(): string;

    /**
     * Translation key for the tab label.
     */
    public function getLabel(): string;

    /**
     * Icon identifier for the tab.
     */
    public function getIcon(): string;

    /**
     * Higher priority types are listed first.
     */
    public function getSortPriority(): int;

    /**
     * @return Collection<OwnershipConfiguration>
     */
    public function listConfigurations(OwnershipListQuery $query): Collection;

    /**
     * @param string[] $ids
     *
     * @throws NotFoundException
     */
    public function reassignOwner(array $ids, int $newOwnerId): void;

    /**
     * @param string[] $ids
     *
     * @throws NotFoundException
     */
    public function delete(array $ids): void;
}
