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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Service\ExecutionEngine;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface ExportServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function generateExportFileForElements(
        ExportParameter $exportParameter,
        string $exportFormat,
        ?UserInterface $user = null,
    ): int;

    /**
     * @throws InvalidArgumentException
     */
    public function generateExportFileForFolders(
        int $folderId,
        ExportFolderParameter $exportParameter,
        string $exportFormat
    ): int;
}
