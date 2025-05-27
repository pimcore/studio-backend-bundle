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
use Pimcore\Bundle\StaticResolverBundle\Lib\Cache\RuntimeCacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Util\Trait\DocumentClassTrait;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\NavigationProperties;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Folder;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Service as DocumentService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Pimcore\Resolver\ResolverInterface;
use function in_array;

/**
 * @internal
 */
final class ReplaceService implements ReplaceServiceInterface
{
    use DocumentClassTrait;

    private const array RESTRICTED_PARAMS = ['children', 'siblings', 'scheduledTasks', 'controller', 'template'];

    public function __construct(
        private readonly DocumentServiceInterface $documentService,
        private readonly ResolverInterface $classResolver,
        private readonly SecurityServiceInterface $securityService,
        private readonly RuntimeCacheResolverInterface $cacheResolver,
    ) {
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

            /** @var User $user */
            (new DocumentService($user))->copyContents($target, $source);
        } catch (Exception $e) {
            throw new ElementSavingFailedException($targetId, $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertType(int $id, string $type): void
    {
        $user = $this->securityService->getCurrentUser();
        $document = $this->getValidDocument($user, $id, true);
        $newClass = $this->getClassByType($type, $this->classResolver);

        try {
            /** @var Document $newDocument */
            $newDocument = new $newClass();

            // overwrite internal store to avoid the "duplicate full path" error
            $this->cacheResolver->set('document_' . $document->getId(), $newDocument);

            $this->setNewTypeProperties($newDocument, $document);
            $this->removeNavigationProperties($newDocument, $type);

            $newDocument->setType($type);
            $newDocument->save();
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage(), $e);
        }
    }

    /**
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     */
    private function getValidDocument(UserInterface $user, int $id, bool $allowFolder = false): Document
    {
        $element = $this->documentService->getDocumentElement($user, $id);
        if (!$allowFolder && $element instanceof Folder) {
            throw new InvalidElementTypeException(ElementTypes::TYPE_FOLDER);
        }

        return $element;
    }

    private function setNewTypeProperties(Document $newDocument, Document $document): void
    {
        $props = $document->getObjectVars();
        foreach ($props as $key => $value) {
            if (in_array($key, self::RESTRICTED_PARAMS, true)) {
                continue;
            }
            $newDocument->setValue($key, $value);
        }
    }

    private function removeNavigationProperties(Document $newDocument, string $type): void
    {
        if ($type === DocumentTypes::HARDLINK->value || $type === ElementTypes::TYPE_FOLDER) {
            foreach (NavigationProperties::values() as $property) {
                $newDocument->removeProperty('navigation_' . $property);
            }
        }
    }
}
