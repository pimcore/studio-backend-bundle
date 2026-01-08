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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetType;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentType;

/**
 * @internal
 */
interface ElementServiceInterface
{
    /**
     * @return AssetType[]
     */
    public function getAssetTypes(): array;

    /**
     * @return DocumentType[]
     */
    public function getDocumentTypes(): array;
}
