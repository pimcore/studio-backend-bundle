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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\BulkImportParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface BulkImportJobServiceInterface
{
    /**
     * @throws NotFoundException
     * @throws ApiInvalidArgumentException
     * @throws EnvironmentException
     */
    public function importItems(string $fileId, BulkImportParameters $parameters): int;
}
