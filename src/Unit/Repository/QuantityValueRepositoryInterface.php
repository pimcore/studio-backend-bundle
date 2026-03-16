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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Repository;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\QuantityValue\Unit\Listing;

/**
 * @internal
 */
interface QuantityValueRepositoryInterface
{
    public function getUnitById(string $id): ?Unit;

    public function unitExists(string $id): bool;

    /**
     * @return Unit[]
     */
    public function getUnitList(): array;

    /**
     * @return Unit[]
     */
    public function getUnitListByBaseUnit(string $baseUnitId, string $fromUnitId): array;

    public function getUnitListing(FilterParameter $parameters): Listing;
}
