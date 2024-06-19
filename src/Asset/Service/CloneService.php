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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class CloneService implements CloneServiceInterface
{
    public function __construct(
        private AssetQueryProviderInterface $assetQueryProvider,
        private AssetServiceInterface $assetService,
        private AssetSearchServiceInterface $assetSearchService,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function cloneAssetRecursively(
        int $sourceId,
        int $parentId
    ): void
    {
        $user = $this->securityService->getCurrentUser();
        $parent = $this->assetService->getAssetElement(
            $user,
            $sourceId,
        );
        $newParent = $this->cloneElement(
            $sourceId,
            $parentId,
            $this->securityService->getCurrentUser()
        );


        if ($parent->hasChildren())
        {
            $query = $this->assetQueryProvider->createAssetQuery();
            $query->filterPath($parent->getRealFullPath(), true, false);
            $ids = $this->assetSearchService->fetchAssetIds($query);

            //trigger GEE job to clone all children and assign to the new parent
        }
    }

    private function cloneElement(
        int $sourceId,
        int $parentId,
        UserInterface $user
    ): Asset
    {
        $source = $this->assetService->getAssetElement(
            $user,
            $sourceId,
        );
        $target = $this->assetService->getAssetElement(
            $user,
            $parentId,
        );

        if (!$target->isAllowed(ElementPermissions::CREATE_PERMISSION)) {
            throw new ForbiddenException(
                sprintf('Missing permissions on target element %s', $parentId));
        }

        try {
            return (new AssetService())->copyAsChild(
                $target,
                $source,
            );
        } catch (Exception $e) {
            throw new ElementSavingFailedException(
                null,
                $e->getMessage()
            );
        }
    }

}