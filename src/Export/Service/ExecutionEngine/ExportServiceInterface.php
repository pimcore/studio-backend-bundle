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
