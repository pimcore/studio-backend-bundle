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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectFolderResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\UserInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;

/**
 * @internal
 */
final readonly class ElementFolderService implements ElementFolderServiceInterface
{
    public function __construct(
        private AssetResolverInterface $assetResolver,
        private DataObjectFolderResolverInterface $dataObjectFolderResolver,
        private DocumentResolverInterface $documentResolver,
        private ElementServiceInterface $elementService,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    public function createFolderByType(
        int $parentId,
        string $elementType,
        string $folderName,
        UserInterface $user
    ): void {
        $parent = $this->elementService->getAllowedElementById($elementType, $parentId, $user);
        $key = $this->serviceResolver->getValidKey($folderName, $elementType);
        $existingElement = $this->serviceResolver->getElementByPath(
            $elementType,
            $parent->getRealFullPath() . '/' . $key
        );

        if ($existingElement) {
            throw new ElementSavingFailedException(null, 'Folder already exists');
        }

        if (!$parent->isAllowed(ElementPermissions::CREATE_PERMISSION)) {
            throw new ForbiddenException(
                sprintf(
                    'Missing %s permission on parent element %s',
                    ElementPermissions::CREATE_PERMISSION,
                    $parentId
                )
            );
        }

        $this->createFolder(
            $parentId,
            $elementType,
            $key,
            [
                'type' => ElementTypes::TYPE_FOLDER,
                'userOwner' => $user->getId(),
                'userModification' => $user->getId(),
            ]
        );

    }

    /**
     * @throws InvalidElementTypeException
     */
    private function createFolder(
        int $parentId,
        string $elementType,
        string $key,
        array $data
    ): void {
        match (true) {
            $elementType === ElementTypes::TYPE_ASSET => $this->createAssetFolder($parentId, $key, $data),
            $elementType === ElementTypes::TYPE_OBJECT => $this->createDataObjectFolder($parentId, $key, $data),
            $elementType === ElementTypes::TYPE_DOCUMENT => $this->createDocumentFolder($parentId, $key, $data),
            default => throw new InvalidElementTypeException($elementType),
        };
    }

    private function createAssetFolder(
        int $parentId,
        string $key,
        array $data
    ): void {
        $data['filename'] = $key;
        $this->assetResolver->create(
            $parentId,
            $data
        );
    }

    private function createDataObjectFolder(
        int $parentId,
        string $key,
        array $data
    ): void {
        $data['key'] = $key;
        $data['creationDate'] = time();
        $data['published'] = true;
        $data['parentId'] = $parentId;
        $this->dataObjectFolderResolver->create(
            $data
        );
    }

    private function createDocumentFolder(
        int $parentId,
        string $key,
        array $data
    ): void {
        $data['key'] = $key;
        $data['published'] = true;
        $this->documentResolver->create(
            $parentId,
            $data
        );
    }
}
