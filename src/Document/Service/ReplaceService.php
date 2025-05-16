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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Service as DocumentService;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Folder;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ReplaceService implements ReplaceServiceInterface
{
    private DocumentService $coreDocumentService;

    public function __construct(
        private readonly DocumentServiceInterface $documentService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        $this->coreDocumentService = new DocumentService();
    }

    /**
     * {@inheritdoc}
     */
    public function replaceContents(int $sourceId, int $targetId): void
    {
        $user = $this->securityService->getCurrentUser();
        $source = $this->getValidDocument($user, $sourceId);
        $target = $this->getValidDocument($user, $targetId);
        $this->securityService->hasElementPermission($target, $user, ElementPermissions::CREATE_PERMISSION);

        try {
            if ($source instanceof PageSnippet && $source->getLatestVersion()) {
                $source = $source->getLatestVersion()->loadData();
                $source->setPublished(false);
            }

            $this->coreDocumentService->copyContents($target, $source);
        } catch (Exception $e) {
            throw new ElementSavingFailedException($targetId, $e->getMessage());
        }
    }

    /**
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function getValidDocument(UserInterface $user, int $id): Document
    {
        $element = $this->documentService->getDocumentElement($user, $id);
        if ($element instanceof Folder) {
            throw new InvalidElementTypeException(ElementTypes::TYPE_FOLDER);
        }

        return $element;
    }
}
