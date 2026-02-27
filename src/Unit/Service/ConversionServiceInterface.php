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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\ConvertAllUnitsParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\MappedParameter\ConvertUnitParameter;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\ConvertedQuantityValues;

/**
 * @internal
 */
interface ConversionServiceInterface
{
    /**
     * @throws DatabaseException
     * @throws NotFoundException
     */
    public function convertUnit(ConvertUnitParameter $parameters): float|int;

    /**
     * @throws DatabaseException
     * @throws NotFoundException
     */
    public function convertAllUnits(ConvertAllUnitsParameter $parameters): ConvertedQuantityValues;
}
