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

use DateTimeInterface;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface as DataObjectDataService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\ValidateObjectDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface as DocumentDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Document\DocumentFieldKeys;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Folder;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_map;
use function array_unique;
use function in_array;
use function is_array;
use function is_object;
use function ksort;
use function spl_object_id;
use function sprintf;

/**
 * @internal
 */
final readonly class UpdateService implements UpdateServiceInterface
{
    use ElementProviderTrait;
    use ValidateObjectDataTrait;

    private const string PUBLISHED_KEY = 'published';

    /**
     * Payload keys that a publish/unpublish task on a data object may carry without any permission
     * beyond publish/unpublish itself (like the classic admin, which lets publish write the edited data).
     */
    private const array PUBLICATION_KEYS = [
        ElementSaveServiceInterface::INDEX_TASK,
        self::PUBLISHED_KEY,
        self::USE_DRAFT_DATA_KEY,
        ElementSaveServiceInterface::INDEX_COAUTHOR_TYPE,
        ElementSaveServiceInterface::INDEX_COAUTHOR,
        self::EDITABLE_DATA_KEY,
    ];

    /**
     * Metadata keys that need their own permission next to a publish/unpublish task (mapping as in the
     * classic admin: rename -> key, settings -> parentId/locked; properties as for reading them).
     * Every other key needs `save`.
     */
    private const array METADATA_KEY_PERMISSIONS = [
        'key' => ElementPermissions::RENAME_PERMISSION,
        'parentId' => ElementPermissions::SETTINGS_PERMISSION,
        'locked' => ElementPermissions::SETTINGS_PERMISSION,
        PropertyRepositoryInterface::INDEX_KEY => ElementPermissions::PROPERTIES_PERMISSION,
    ];

    public function __construct(
        private AdapterLoaderInterface $adapterLoader,
        private DataObjectDataService $objectDataService,
        private DocumentDataService $documentDataService,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private ElementIndexServiceInterface $indexService,
        private ElementSaveServiceInterface $elementSaveService,
        private CoauthorServiceInterface $coauthorService,
    ) {
    }

    /**
     * @throws ElementSavingFailedException|FieldValidationFailedException|ForbiddenException
     * @throws InvalidArgumentException|NotFoundException
     */
    public function update(string $elementType, int $id, array $data): void
    {
        $user = $this->securityService->getCurrentUser();
        $task = $data[ElementSaveServiceInterface::INDEX_TASK] ?? null;
        $liveElement = $this->getElement($this->serviceResolver, $elementType, $id);
        $element = $this->getLatestElement($liveElement, $data, $task);

        if ($element instanceof AbstractObject) {
            $this->checkObjectWritePermissions($elementType, $liveElement, $element, $data, $task, $user);
        }

        if ($element instanceof Asset) {
            $this->securityService->hasElementPermission($element, $user, ElementPermissions::PUBLISH_PERMISSION);
        }

        if (isset($data[self::EDITABLE_DATA_KEY]) && $element instanceof Concrete) {
            $this->objectDataService->updateEditableData(
                $element,
                $data[self::EDITABLE_DATA_KEY],
                $user,
                ElementSaveTasks::appliesRemoteChanges($task)
            );
            unset($data[self::EDITABLE_DATA_KEY]);
        }

        if ($element instanceof Document && !$element instanceof Folder) {
            $this->documentDataService->updateDocumentData($element, $data, $user);
            unset($data[DocumentFieldKeys::EDITABLE_DATA->value], $data[DocumentFieldKeys::SETTINGS_DATA->value]);
        }

        foreach ($this->adapterLoader->loadAdapters($elementType) as $adapter) {
            $adapter->update($element, $data);
        }

        $coauthor = $this->coauthorService->extractFromPayload($data);

        try {
            $save = function () use ($element, $user, $task, $data): void {
                $this->elementSaveService->save($element, $user, $task);

                if (isset($data['index'])) {
                    $this->indexService->indexRelatedElements($element, $data['index']);
                }
            };

            $this->coauthorService->runWithCoauthor($coauthor, $save);
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
     * Persisting object data (save, autoSave, version or an untasked write) requires the workspace `save`
     * permission, and so does every write to a folder: folders have no publish state, so no other gate exists.
     *
     * A publish/unpublish task on a data object is gated by ElementSaveService / PublishAdapter. Besides the
     * edited data it may only carry metadata for which the user holds the matching permission (see
     * METADATA_KEY_PERMISSIONS); any other key needs `save`. The same holds for metadata that differs between
     * the draft loaded for `useDraftData` and the live object, unless the user holds `save` (creating such a
     * draft required it). Permissions are always checked against the live object.
     *
     * @throws ForbiddenException|NotFoundException
     */
    private function checkObjectWritePermissions(
        string $elementType,
        ElementInterface $liveElement,
        AbstractObject $element,
        array $data,
        ?string $task,
        UserInterface $user
    ): void {
        $live = $liveElement instanceof AbstractObject ? $liveElement : $element;
        $isPublicationTask = in_array(
            $task,
            [ElementSaveTasks::PUBLISH->value, ElementSaveTasks::UNPUBLISH->value],
            true
        );

        if (!$element instanceof Concrete || !$isPublicationTask) {
            $this->securityService->hasElementPermission($live, $user, ElementPermissions::SAVE_PERMISSION);

            return;
        }

        // Checked up front so that no edited data is applied for a user who may not publish at all
        $permissions = [
            $task === ElementSaveTasks::PUBLISH->value
                ? ElementPermissions::PUBLISH_PERMISSION
                : ElementPermissions::UNPUBLISH_PERMISSION,
        ];
        foreach (array_keys(array_diff_key($data, array_flip(self::PUBLICATION_KEYS))) as $key) {
            $permissions[] = self::METADATA_KEY_PERMISSIONS[$key] ?? ElementPermissions::SAVE_PERMISSION;
        }

        $this->securityService->hasElementPermissions($live, $user, array_unique($permissions));

        // Moving needs the create permission on the new parent, as in the classic admin
        $parentId = $data['parentId'] ?? null;
        if ($parentId !== null && $parentId !== $live->getParentId()) {
            $this->checkCreateOnNewParent($elementType, (int) $parentId, $user);
        }

        if ($live !== $element) {
            $this->checkDraftMetadataPermissions($elementType, $live, $element, $user);
        }
    }

    /**
     * Authorizes the metadata a draft would write on publishing. For the metadata itself `save` is sufficient: it
     * covers every field and was needed to create the draft. Without it the matching permission is required for
     * each difference. A move additionally always needs the create permission on the new parent.
     *
     * @throws ForbiddenException|NotFoundException
     */
    private function checkDraftMetadataPermissions(
        string $elementType,
        AbstractObject $live,
        AbstractObject $draft,
        UserInterface $user
    ): void {
        $changed = $this->getChangedMetadataKeys($live, $draft);
        if ($changed === []) {
            return;
        }

        $permissions = [];
        foreach ($changed as $key) {
            $permissions[] = self::METADATA_KEY_PERMISSIONS[$key] ?? ElementPermissions::SAVE_PERMISSION;
        }

        try {
            $this->securityService->hasElementPermissions($live, $user, array_unique($permissions));
        } catch (ForbiddenException $exception) {
            try {
                $this->securityService->hasElementPermission($live, $user, ElementPermissions::SAVE_PERMISSION);
            } catch (ForbiddenException) {
                throw $exception;
            }
        }

        // As for a parentId in the request, moving always needs the create permission on the new parent
        if (in_array('parentId', $changed, true)) {
            $this->checkCreateOnNewParent($elementType, (int) $draft->getParentId(), $user);
        }
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function checkCreateOnNewParent(string $elementType, int $parentId, UserInterface $user): void
    {
        $this->securityService->hasElementPermission(
            $this->getElement($this->serviceResolver, $elementType, $parentId),
            $user,
            ElementPermissions::CREATE_PERMISSION
        );
    }

    /**
     * @return list<string> keys (as in a request payload) of the metadata the draft changes compared to the live object
     */
    private function getChangedMetadataKeys(AbstractObject $live, AbstractObject $draft): array
    {
        $changed = [];
        if ($live->getKey() !== $draft->getKey()) {
            $changed[] = 'key';
        }

        if ($live->getParentId() !== $draft->getParentId()) {
            $changed[] = 'parentId';
        }

        if ($live->getLocked() !== $draft->getLocked()) {
            $changed[] = 'locked';
        }

        if ($live->getIndex() !== $draft->getIndex()) {
            $changed[] = 'index';
        }

        if ($live->getChildrenSortOrder() !== $draft->getChildrenSortOrder()) {
            $changed[] = 'childrenSortOrder';
        }

        if ($this->getPropertySignature($live) !== $this->getPropertySignature($draft)) {
            $changed[] = PropertyRepositoryInterface::INDEX_KEY;
        }

        return $changed;
    }

    /**
     * Reduces a property value to something comparable between the live object and a draft loaded from a
     * version, where equal values are distinct objects. Unknown objects never compare equal.
     */
    private function normalizePropertyValue(mixed $value): mixed
    {
        return match (true) {
            is_array($value) => array_map($this->normalizePropertyValue(...), $value),
            $value instanceof DateTimeInterface => 'date:' . $value->format('Y-m-d\TH:i:s.uP'),
            $value instanceof ElementInterface => 'element:' . $value::class . ':' . $value->getId(),
            is_object($value) => 'object:' . $value::class . '#' . spl_object_id($value),
            default => $value,
        };
    }

    /**
     * Own (not inherited) properties by name, reduced to comparable values.
     *
     * @return array<string, array<int, mixed>>
     */
    private function getPropertySignature(AbstractObject $object): array
    {
        $signature = [];
        foreach ($object->getProperties() as $name => $property) {
            if ($property->isInherited()) {
                continue;
            }

            $signature[(string) $name] = [
                $property->getType(),
                $property->getInheritable(),
                $this->normalizePropertyValue($property->getData()),
            ];
        }

        ksort($signature);

        return $signature;
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
