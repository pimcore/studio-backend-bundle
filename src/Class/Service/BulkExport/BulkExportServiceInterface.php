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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkExport;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\BulkExportParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport\BulkExportAvailableItem;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\JsonExport;

/**
 * @internal
 */
interface BulkExportServiceInterface
{
    /**
     * @return BulkExportAvailableItem[]
     *
     * @throws EnvironmentException
     * @throws UserNotFoundException
     */
    public function getAvailableItems(): array;

    /**
     * @throws EnvironmentException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function exportItems(BulkExportParameters $parameters): JsonExport;
}
