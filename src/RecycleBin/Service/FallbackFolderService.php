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
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function sprintf;

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

    public function hasMissingParent(ElementInterface $element): bool
    {
        $parentId = $element->getParentId();
        if ($parentId === null || $parentId === 0) {
            return true;
        }

        return $this->serviceResolver->getElementById($this->getElementType($element, true), $parentId) === null;
    }

    public function moveToFallbackFolder(ElementInterface $element, UserInterface $user): void
    {
        $elementType = $this->getElementType($element);
        $folder = $this->getFallbackFolder($elementType);

        try {
            $this->securityService->hasElementPermission($folder, $user, ElementPermissions::PUBLISH_PERMISSION);
        } catch (ForbiddenException $exception) {
            throw new EnvironmentException(
                sprintf('Missing publish permission on the fallback folder %s', self::FOLDER_PATH),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT->value,
                $exception
            );
        }

        $element->setParent($folder);
        $element->setKey($this->getUniqueKey($elementType, $element));
    }

    /**
     * @throws EnvironmentException
     */
    private function getFallbackFolder(string $elementType): ElementInterface
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
     * @throws EnvironmentException
     */
    private function getUniqueKey(string $elementType, ElementInterface $element): string
    {
        try {
            return match ($elementType) {
                ElementTypes::TYPE_ASSET => $this->assetServiceResolver->getUniqueKey($element),
                ElementTypes::TYPE_DOCUMENT => $this->documentServiceResolver->getUniqueKey($element),
                default => $this->dataObjectServiceResolver->getUniqueKey($element),
            };
        } catch (Exception $exception) {
            throw new EnvironmentException(
                $exception->getMessage(),
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE->value,
                $exception
            );
        }
    }
}
