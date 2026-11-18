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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Model;

/**
 * @internal
 */
final readonly class CloneDescendants
{
    /**
     * @param int[] $folderIds ordered so that every folder comes after its parent folder
     * @param int[] $assetIds
     */
    public function __construct(
        private array $folderIds,
        private array $assetIds
    ) {
    }

    /**
     * @return int[]
     */
    public function getFolderIds(): array
    {
        return $this->folderIds;
    }

    /**
     * @return int[]
     */
    public function getAssetIds(): array
    {
        return $this->assetIds;
    }
}
