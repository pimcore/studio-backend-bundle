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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\ConvertAllUnitsParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\ConvertUnitParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\ConvertedQuantityValues;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\QuantityValueUnit;

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
     * @throws DatabaseException|NotFoundException
     */
    public function convertUnit(ConvertUnitParameter $parameters): float|int;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function convertAllUnits(ConvertAllUnitsParameter $parameters): ConvertedQuantityValues;
}
