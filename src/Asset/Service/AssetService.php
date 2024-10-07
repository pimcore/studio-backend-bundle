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

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\AssetEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Pimcore\Model\Asset as AssetModel;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class AssetService implements AssetServiceInterface
{
    use ElementProviderTrait;
    use UserPermissionTrait;

    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private AssetServiceResolverInterface $assetServiceResolver,
        private FilterServiceProviderInterface $filterServiceProvider,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * @throws InvalidFilterServiceTypeException|SearchException|InvalidQueryTypeException|InvalidFilterTypeException
     */
    public function getAssets(ElementParameters $parameters): Collection
    {
        /** @var OpenSearchFilterInterface $filterService */
        $filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);

        $assetQuery = $filterService->applyFilters(
            $parameters,
            ElementTypes::TYPE_ASSET
        );

        $assetQuery->orderByPath('asc');

        $result = $this->assetSearchService->searchAssets($assetQuery);

        $items = $result->getItems();

        foreach ($items as $item) {
            $this->dispatchEventAndReturnAsset($item);
        }

        return new Collection($result->getTotalItems(), $items);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAsset(
        int $id,
        bool $checkPermissionsForCurrentUser = true
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video {

        $asset = $this->assetSearchService->getAssetById(
            $id,
            $this->getUserForPermissionCheck($this->securityService, $checkPermissionsForCurrentUser)
        );

        return $this->dispatchEventAndReturnAsset($asset);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetForUser(
        int $id,
        UserInterface $user
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video
    {
        $asset = $this->assetSearchService->getAssetById($id, $user);

        return $this->dispatchEventAndReturnAsset($asset);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetFolder(int $id, bool $checkPermissionsForCurrentUser = true): AssetFolder
    {
        $asset = $this->assetSearchService->getAssetById(
            $id,
            $this->getUserForPermissionCheck($this->securityService, $checkPermissionsForCurrentUser)
        );

        if (!$asset instanceof AssetFolder) {
            throw new NotFoundException(ElementTypes::TYPE_FOLDER, $id);
        }

        return $this->dispatchEventAndReturnAsset($asset);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function getAssetFolderForUser(int $id, UserInterface $user): AssetFolder
    {
        $asset = $this->assetSearchService->getAssetById($id, $user);

        if (!$asset instanceof AssetFolder) {
            throw new NotFoundException(ElementTypes::TYPE_FOLDER, $id);
        }

        return $this->dispatchEventAndReturnAsset($asset);
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function assetFolderExists(int $id, bool $checkPermissionsForCurrentUser = true): bool
    {
        try {
            $this->getAssetFolder($id, $checkPermissionsForCurrentUser);

            return true;
        } catch (NotFoundException) {
            return false;
        }
    }

    /**
     * @throws SearchException|NotFoundException
     */
    public function assetFolderExistsForUser(int $id, UserInterface $user): bool
    {
        try {
            $this->getAssetFolderForUser($id, $user);

            return true;
        } catch (NotFoundException) {
            return false;
        }
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAssetElement(
        UserInterface $user,
        int $assetId,
    ): AssetModel {
        $asset = $this->getElement($this->serviceResolver, ElementTypes::TYPE_ASSET, $assetId);
        $this->securityService->hasElementPermission($asset, $user, ElementPermissions::VIEW_PERMISSION);

        if (!$asset instanceof AssetModel) {
            throw new InvalidElementTypeException($asset->getType());
        }

        return $asset;
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getAssetElementByPath(
        UserInterface $user,
        string $path,
    ): AssetModel {
        $asset = $this->getElementByPath($this->serviceResolver, ElementTypes::TYPE_ASSET, $path);
        $this->securityService->hasElementPermission($asset, $user, ElementPermissions::VIEW_PERMISSION);

        if (!$asset instanceof AssetModel) {
            throw new InvalidElementTypeException($asset->getType());
        }

        return $asset;
    }

    public function getUniqueAssetName(string $targetPath, string $filename): string
    {
        $pathInfo = pathinfo($filename);
        $extension = empty($pathInfo['extension']) ? '' : '.' . $pathInfo['extension'];
        $count = 1;

        if ($targetPath === '/') {
            $targetPath = '';
        }

        while (true) {
            if ($this->assetServiceResolver->pathExists($targetPath . '/' . $filename)) {
                $filename = $pathInfo['filename'] . '_' . $count . $extension;
                $count++;
            } else {
                return $filename;
            }
        }
    }

    private function dispatchEventAndReturnAsset(
        Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video $asset
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video
    {
        $this->eventDispatcher->dispatch(
            new AssetEvent($asset),
            AssetEvent::EVENT_NAME
        );

        return $asset;
    }
}
