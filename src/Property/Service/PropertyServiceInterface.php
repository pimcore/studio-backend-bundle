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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\ElementProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;

/**
 * @internal
 */
interface PropertyServiceInterface
{
    /**
     * @throws NotWriteableException
     */
    public function createPredefinedProperty(): PredefinedProperty;

    /**
     * @throws NotFoundException
     */
    public function getPredefinedProperty(string $id): PredefinedProperty;

    /**
     * @return array<int, PredefinedProperty>
     */
    public function getPredefinedProperties(PropertiesParameters $parameters): array;

    /**
     * @throws NotFoundException|ForbiddenException
     *
     * @return array<int, ElementProperty>
     */
    public function getElementProperties(string $elementType, int $id): array;

    /**
     * @throws NotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): PredefinedProperty;

    /**
     * @throws NotFoundException
     */
    public function deletePredefinedProperty(string $id): void;
}
