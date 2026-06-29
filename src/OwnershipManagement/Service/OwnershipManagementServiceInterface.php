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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter\DeleteParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter\ReassignOwnerParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\ConfigurationType;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface OwnershipManagementServiceInterface
{
    /**
     * @return Collection<ConfigurationType>
     *
     * @throws ForbiddenException
     */
    public function getAvailableTypes(): Collection;

    /**
     * @return Collection<OwnershipConfiguration>
     *
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException
     */
    public function listConfigurations(string $type, CollectionFilterParameter $parameters): Collection;

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException
     */
    public function reassignOwner(string $type, ReassignOwnerParameter $parameter): ?int;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function delete(string $type, DeleteParameter $parameter): ?int;
}
