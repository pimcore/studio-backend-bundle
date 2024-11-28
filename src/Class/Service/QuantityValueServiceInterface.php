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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConvertAllParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConvertedQuantityValues;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConvertParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\QuantityValueUnit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

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
    public function convertUnit(ConvertParameters $parameters): float|int;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function convertAllUnits(ConvertAllParameters $parameters): ConvertedQuantityValues;
}
