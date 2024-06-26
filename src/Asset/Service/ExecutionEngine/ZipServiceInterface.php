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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateZipParameter;
use Pimcore\Model\Asset;
use ZipArchive;

/**
 * @internal
 */
interface ZipServiceInterface
{
    public const ASSETS_INDEX = 'assets';

    public function getZipArchive(int $id): ?ZipArchive;

    public function addFile(ZipArchive $archive, Asset $asset): void;

    public function generateZipFile(CreateZipParameter $ids): string;

    public function getTempZipFilePath(int $id): string;
}
