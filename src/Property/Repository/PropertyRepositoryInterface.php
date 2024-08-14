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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\FilterParameter;
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

    public function listProperties(FilterParameter $parameters): PropertiesListing;

    public function updateElementProperties(ElementInterface $element, array $data): void;

    /**
     * @throws NotFoundException
     */
    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): void;

    /**
     * @throws NotFoundException
     */
    public function deletePredefinedProperty(string $id): void;
}
