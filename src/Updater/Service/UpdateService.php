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

namespace Pimcore\Bundle\StudioBackendBundle\Updater\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface as DataObjectDataService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface as DocumentDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Folder;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use function in_array;
use function sprintf;

/**
 * @internal
 */
final readonly class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;
    use ValidateObjectDataTrait;

    public function __construct(
        private AdapterLoaderInterface $adapterLoader,
        private DataObjectDataService $objectDataService,
        private DocumentDataService $documentDataService,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private ElementIndexServiceInterface $indexService,
        private ElementSaveServiceInterface $elementSaveService,
    ) {
    }

    /**
     * @throws ElementSavingFailedException|FieldValidationFailedException|ForbiddenException|NotFoundException
     */
    public function update(string $elementType, int $id, array $data): void
    {
        $user = $this->securityService->getCurrentUser();
        $task = $data[ElementSaveServiceInterface::INDEX_TASK] ?? null;
        $element = $this->getLatestElement($this->getElement($this->serviceResolver, $elementType, $id), $data, $task);

        if ($element instanceof Concrete && $this->requiresSavePermission($task)) {
            $this->securityService->hasElementPermission($element, $user, ElementPermissions::SAVE_PERMISSION);
        }

        if ($element instanceof Asset) {
            $this->securityService->hasElementPermission($element, $user, ElementPermissions::PUBLISH_PERMISSION);
        }

        if (isset($data[self::EDITABLE_DATA_KEY]) && $element instanceof Concrete) {
            $this->objectDataService->updateEditableData($element, $data[self::EDITABLE_DATA_KEY], $user);
            unset($data[self::EDITABLE_DATA_KEY]);
        }

        if ($element instanceof Document && !$element instanceof Folder) {
            $this->documentDataService->updateDocumentData($element, $data, $user);
            unset($data[DocumentFieldKeys::EDITABLE_DATA->value], $data[DocumentFieldKeys::SETTINGS_DATA->value]);
        }

        foreach ($this->adapterLoader->loadAdapters($elementType) as $adapter) {
            $adapter->update($element, $data);
        }

        try {
            $this->elementSaveService->save($element, $user, $task);

            if (isset($data['index'])) {
                $this->indexService->indexRelatedElements($element, $data['index']);
            }
        } catch (DuplicateFullPathException) {
            throw new ElementExistsException(
                message: sprintf('Element with full path [%s] already exists', $element->getRealFullPath())
            );
        } catch (ValidationException $e) {
            throw new FieldValidationFailedException($e->getMessage(), previous: $e);
        } catch (ForbiddenException $e) {
            // Publish/unpublish permission is checked inside elementSaveService->save();
            // re-throw so it stays a 403 instead of being masked as a 500 below.
            throw $e;
        } catch (Exception $e) {
            throw new ElementSavingFailedException($id, $e->getMessage());
        }
    }

    /**
     * Persisting object data (save, autoSave, version or an untasked write) requires the workspace
     * `save` permission. Publishing/unpublishing is gated separately by ElementSaveService.
     */
    private function requiresSavePermission(?string $task): bool
    {
        return !in_array(
            $task,
            [ElementSaveTasks::PUBLISH->value, ElementSaveTasks::UNPUBLISH->value],
            true
        );
    }

    private function getLatestElement(
        mixed $element,
        array $data,
        ?string $task
    ): mixed {
        if (($data[self::USE_DRAFT_DATA_KEY] ?? false) !== true) {
            return $element;
        }

        $draftElement = $this->getDraftElement($element);
        if ($element === $draftElement) {
            return $element;
        }

        if ($draftElement instanceof Concrete && $element instanceof Concrete) {
            $this->objectDataService->handleDraftData($draftElement, $element, $task);
        }

        return $draftElement;
    }

    private function getDraftElement(ElementInterface $element): ElementInterface
    {
        if (!$element instanceof Concrete && !$element instanceof PageSnippet) {
            return $element;
        }

        $version = $this->getLatestVersionForUser($element, $this->securityService->getCurrentUser());

        return $this->getVersionData($element, $version);
    }
}
