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

namespace Pimcore\Bundle\StudioBackendBundle\Property;

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Request\UpdateElementProperties;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\UpdatePredefinedProperty;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Property\Predefined;
use Pimcore\Model\Property\Predefined\Listing as PropertiesListing;

/**
 * @internal
 */
interface RepositoryInterface
{
    public function listProperties(PropertiesParameters $parameters): PropertiesListing;

    public function updatePredefinedProperty(string $id, UpdatePredefinedProperty $property): Predefined;

    /**
     * @throws ElementSavingFailedException
     */
    public function updateElementProperties(
        string $elementType,
        int $id,
        UpdateElementProperties $items
    ): void;

    public function deletePredefinedProperty(string $id): void;
}
