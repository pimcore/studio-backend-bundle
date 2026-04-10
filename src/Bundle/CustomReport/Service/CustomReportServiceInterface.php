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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service;

use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ChartDataParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\DrillDownParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface CustomReportServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getChartData(ChartDataParameter $parameters): Collection;

    /**
     * @throws DatabaseException|NotFoundException
     */
    public function getDrillDownOptions(DrillDownParameter $parameters): array;
}
