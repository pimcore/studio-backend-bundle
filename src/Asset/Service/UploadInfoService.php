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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetBatchInfo;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class UploadInfoService implements UploadInfoServiceInterface
{
    public function __construct(
        private AssetServiceInterface $assetService,
        private AssetServiceResolverInterface $assetServiceResolver,
    ) {
    }

    /**
     * @param array<string> $fileNames
     *
     * @return array<AssetBatchInfo>
     *
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function filesExist(
        int $parentId,
        array $fileNames,
        UserInterface $user
    ): array {
        $parent = $this->assetService->getAssetElement($user, $parentId);
        $parentPath = $parent->getRealFullPath();
        $result = [];

        foreach ($fileNames as $fileName) {
            $filePath = $parentPath . '/' . $fileName;

            if ($this->assetServiceResolver->pathExists($filePath, ElementTypes::TYPE_ASSET) === false) {
                $result[] = new AssetBatchInfo($fileName, false);

                continue;
            }

            // A single file the user may not view must not fail the whole batch, so the
            // denial is reported on its own entry instead of aborting the request.
            try {
                $asset = $this->assetService->getAssetElementByPath($user, $filePath);
            } catch (ForbiddenException) {
                $result[] = new AssetBatchInfo($fileName, false, null, true);

                continue;
            }

            $result[] = new AssetBatchInfo($fileName, true, $asset->getId());
        }

        return $result;
    }
}
