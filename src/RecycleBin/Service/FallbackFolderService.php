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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function dirname;
use function pathinfo;
use function sprintf;
use function strrpos;
use function substr;

/**
 * @internal
 */
final readonly class FallbackFolderService implements FallbackFolderServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private AssetServiceResolverInterface $assetServiceResolver,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private DocumentServiceResolverInterface $documentServiceResolver,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    public function hasMissingParent(ElementInterface $element, string $originalPath): bool
    {
        $parentId = $element->getParentId();
        if ($parentId === null || $parentId === 0) {
            return true;
        }

        // with a logged-in user, core saves the data object placeholder under the root when the parent is gone
        if ($parentId === 1) {
            return dirname($originalPath) !== '/';
        }

        return $this->serviceResolver->getElementById($this->getElementType($element, true), $parentId) === null;
    }

    public function moveToFallbackFolder(ElementInterface $element, UserInterface $user, string $originalPath): void
    {
        $elementType = $this->getElementType($element);
        $coreType = $this->getElementType($element, true);
        $folder = $this->serviceResolver->getElementByPath($coreType, self::FOLDER_PATH);
        if ($folder instanceof Asset && !$folder instanceof AssetFolder) {
            throw new EnvironmentException(
                sprintf('The path of the fallback folder %s is taken by an asset file', self::FOLDER_PATH),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT->value
            );
        }

        // a folder that does not exist yet takes the root's permissions, so a denied restore creates nothing
        $this->checkPublishPermission($folder ?? $this->getRoot($coreType), $user);
        $folder ??= $this->createFallbackFolder($elementType);

        $element->setParent($folder);
        // core appends _restore while the original path is taken, that clash is not in the fallback folder
        $freeKey = $this->getFreeKey($element, $coreType, $this->getOriginalKey($originalPath));
        if ($freeKey !== (string) $element->getKey()) {
            $element->setKey($freeKey);
        }
    }

    private function getOriginalKey(string $originalPath): string
    {
        $position = strrpos($originalPath, '/');

        return $position === false ? $originalPath : substr($originalPath, $position + 1);
    }

    /**
     * @throws EnvironmentException
     */
    private function checkPublishPermission(ElementInterface $element, UserInterface $user): void
    {
        try {
            $this->securityService->hasElementPermission($element, $user, ElementPermissions::PUBLISH_PERMISSION);
        } catch (ForbiddenException $exception) {
            throw new EnvironmentException(
                sprintf('Missing publish permission for the fallback folder %s', self::FOLDER_PATH),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT->value,
                $exception
            );
        }
    }

    /**
     * @throws EnvironmentException
     */
    private function getRoot(string $coreType): ElementInterface
    {
        $root = $this->serviceResolver->getElementById($coreType, 1);
        if ($root === null) {
            throw new EnvironmentException(
                sprintf('Root of type %s not found', $coreType),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT->value
            );
        }

        return $root;
    }

    /**
     * @throws EnvironmentException
     */
    private function createFallbackFolder(string $elementType): ElementInterface
    {
        try {
            $folder = match ($elementType) {
                ElementTypes::TYPE_ASSET => $this->assetServiceResolver->createFolderByPath(self::FOLDER_PATH),
                ElementTypes::TYPE_DOCUMENT => $this->documentServiceResolver->createFolderByPath(self::FOLDER_PATH),
                default => $this->dataObjectServiceResolver->createFolderByPath(self::FOLDER_PATH),
            };
        } catch (Exception $exception) {
            throw new EnvironmentException(
                $exception->getMessage(),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT->value,
                $exception
            );
        }

        if ($folder === null) {
            throw new EnvironmentException(
                sprintf('Fallback folder %s could not be created', self::FOLDER_PATH),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT->value
            );
        }

        return $folder;
    }

    /**
     * Core's unique key helper is not used: for an asset file without extension it fails on a taken name.
     */
    private function getFreeKey(ElementInterface $element, string $coreType, string $key): string
    {
        $freeKey = $key;
        $number = 0;
        while ($this->isKeyTaken($element, $coreType, $freeKey)) {
            $number++;
            $freeKey = $this->getNumberedKey($element, $key, $number);
        }

        return $freeKey;
    }

    private function isKeyTaken(ElementInterface $element, string $coreType, string $key): bool
    {
        $existing = $this->serviceResolver->getElementByPath($coreType, self::FOLDER_PATH . '/' . $key);

        // the data object restore saves a placeholder with the same ID first
        return $existing !== null && $existing->getId() !== $element->getId();
    }

    private function getNumberedKey(ElementInterface $element, string $key, int $number): string
    {
        $suffix = '_' . $number;
        if (!$element instanceof Asset || $element instanceof AssetFolder) {
            return $key . $suffix;
        }

        $pathInfo = pathinfo($key);
        $extension = ($pathInfo['extension'] ?? '') === '' ? '' : '.' . $pathInfo['extension'];

        return $pathInfo['filename'] . $suffix . $extension;
    }
}
