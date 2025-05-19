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

use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportFolderParameter;
use Pimcore\Bundle\StudioBackendBundle\Export\MappedParameter\ExportParameter;

/**
 * @internal
 */
interface ExportServiceInterface
{
    public function generateExportFileForElements(ExportParameter $exportParameter, string $exportFormat): int;

    public function generateExportFileForFolders(ExportFolderParameter $exportParameter, string $exportFormat): int;
}
