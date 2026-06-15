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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\CreateUnitParameters;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\UpdateUnitParameters;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\QuantityValueUnit;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
interface QuantityValueServiceInterface
{
    /**
     * @return QuantityValueUnit[]
     */
    public function listUnits(): array;

    /**
     * @return Collection<QuantityValueUnit>
     *
     * @throws InvalidArgumentException
     */
    public function listUnitCollection(CollectionFilterParameter $parameters): Collection;

    /**
     * @throws InvalidArgumentException
     * @throws EnvironmentException
     */
    public function createUnit(CreateUnitParameters $parameters): QuantityValueUnit;

    /**
     * @throws NotFoundException
     * @throws EnvironmentException
     */
    public function updateUnit(string $id, UpdateUnitParameters $parameters): QuantityValueUnit;

    /**
     * @throws NotFoundException
     * @throws EnvironmentException
     */
    public function deleteUnit(string $id): void;

    /**
     * @throws InvalidArgumentException
     * @throws EnvironmentException
     */
    public function importUnits(string $json): void;

    /**
     * @throws EnvironmentException
     */
    public function exportUnits(): Response;
}
