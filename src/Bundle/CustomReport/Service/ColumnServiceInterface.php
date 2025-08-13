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

use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportColumnInformation;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema\CustomReportDataSourceConfig;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface ColumnServiceInterface
{
    /**
     * @throws DatabaseException|NotFoundException
     *
     * @return CustomReportColumnInformation[]
     */
    public function getColumnConfig(string $name, CustomReportDataSourceConfig $dataSourceConfig): array;
}
