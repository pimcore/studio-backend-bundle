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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertiesListing;

/**
 * @internal
 */
interface PropertyRepositoryInterface
{
    public const INDEX_KEY = 'properties';

    /**
     * @throws NotWriteableException
     */
    public function createPredefinedProperty(): Predefined;

    /**
     * @throws NotFoundException
     */
    public function getPredefinedProperty(string $id): Predefined;

    public function listProperties(PropertiesParameters $parameters): PropertiesListing;

    public function updateElementProperties(ElementInterface $element, array $data): void;

    /**
     * @throws NotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): Predefined;

    /**
     * @throws NotFoundException
     */
    public function deletePredefinedProperty(string $id): void;
}
