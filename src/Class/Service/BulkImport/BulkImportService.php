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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport;

use const JSON_THROW_ON_ERROR;
use Exception;
use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinition\CustomLayout\CustomLayoutResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkExport\BulkExportAvailableItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkImport\BulkImportPrepareEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\BulkExport\BulkExportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport\BulkExportAvailableItem;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkImport\BulkImportPrepareResponse;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldCollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use function is_array;
use function json_encode;
use function sprintf;

/**
 * @internal
 */
final readonly class BulkImportService implements BulkImportServiceInterface
{
    private const array PERMISSION_MAP = [
        ClassDefinitionType::FieldCollection->value => UserPermissions::FIELD_COLLECTIONS->value,
        ClassDefinitionType::ClassDefinition->value => UserPermissions::CLASS_DEFINITION->value,
        ClassDefinitionType::CustomLayout->value => UserPermissions::CLASS_DEFINITION->value,
        ClassDefinitionType::ObjectBrick->value => UserPermissions::OBJECT_BRICKS->value,
    ];

    public function __construct(
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private FieldCollectionRepositoryInterface $fieldCollectionRepository,
        private ObjectBrickRepositoryInterface $objectBrickRepository,
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private CustomLayoutResolverInterface $customLayoutResolver,
        private EventDispatcherInterface $eventDispatcher,
        private BulkImportFileServiceInterface $bulkImportFileService,
        private BulkExportHydratorInterface $bulkExportHydrator,
        private BulkImportDataResolver $bulkImportDataResolver,
    ) {
    }

    public function prepareImport(UploadedFile $file): BulkImportPrepareResponse
    {
        $fileId = $this->bulkImportFileService->storeFile($file);
        $fileData = $this->bulkImportFileService->readFileData($fileId);

        $items = $this->buildAvailableItems($fileData);

        $response = new BulkImportPrepareResponse($fileId, $items);
        $this->eventDispatcher->dispatch(
            new BulkImportPrepareEvent($response),
            BulkImportPrepareEvent::EVENT_NAME
        );

        return $response;
    }

    public function importSingleItem(
        ClassDefinitionType $type,
        string $name,
        array $exportEntry,
        UserInterface $user,
    ): void {
        if (!$user->isAllowed(self::PERMISSION_MAP[$type->value])) {
            throw new EnvironmentException(
                sprintf('Access denied for importing %s "%s"', $type->value, $name)
            );
        }

        match ($type) {
            ClassDefinitionType::FieldCollection => $this->importFieldCollection(
                $name,
                $exportEntry
            ),
            ClassDefinitionType::ClassDefinition => $this->importClass($name, $exportEntry),
            ClassDefinitionType::CustomLayout => $this->importCustomLayout(
                $name,
                $exportEntry,
                $user
            ),
            ClassDefinitionType::ObjectBrick => $this->importObjectBrick(
                $name,
                $exportEntry
            ),
        };
    }

    /**
     * @return BulkExportAvailableItem[]
     */
    private function buildAvailableItems(array $fileData): array
    {
        $items = [];

        foreach (ClassDefinitionType::importOrder() as $type) {
            $dataForType = $fileData[$type->value] ?? [];

            if (!is_array($dataForType) || empty($dataForType)) {
                continue;
            }

            foreach ($dataForType as $exportEntry) {
                if (!is_array($exportEntry)) {
                    continue;
                }

                $name = $this->bulkImportDataResolver->resolveEntryName($type, $exportEntry);
                if ($name === null) {
                    continue;
                }

                $displayName = $name;
                if ($type === ClassDefinitionType::CustomLayout) {
                    $className = $exportEntry['className'] ?? '';
                    if ($className !== '') {
                        $displayName = $className . ' / ' . $name;
                    }
                }

                $item = $this->bulkExportHydrator->hydrateAvailableItem(
                    $type->value,
                    $name,
                    $displayName,
                    $type->icon()
                );
                $this->eventDispatcher->dispatch(
                    new BulkExportAvailableItemEvent($item),
                    BulkExportAvailableItemEvent::EVENT_NAME
                );
                $items[] = $item;
            }
        }

        return $items;
    }

    private function prepareImportJson(array $exportEntry): string
    {
        unset(
            $exportEntry['creationDate'],
            $exportEntry['modificationDate'],
            $exportEntry['userOwner'],
            $exportEntry['userModification'],
        );

        try {
            return json_encode($exportEntry, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'Import file does not contain valid JSON: ' . $e->getMessage(),
                $e,
            );
        }
    }

    private function importFieldCollection(string $key, array $exportEntry): void
    {
        $json = $this->prepareImportJson($exportEntry);

        try {
            $definition = $this->fieldCollectionRepository->getFieldCollectionByKey($key);
        } catch (NotFoundException) {
            $definition = new FieldCollectionDefinition();
            $definition->setKey($key);
        }

        $this->fieldCollectionRepository->importFromJson($definition, $json);
    }

    private function importClass(string $name, array $exportEntry): void
    {
        $json = $this->prepareImportJson($exportEntry);

        try {
            $definition = $this->classDefinitionRepository->getClassDefinition($name);
        } catch (NotFoundException) {
            $definition = $this->classDefinitionResolver->create();
            $definition->setName($name);
        }

        $this->classDefinitionRepository->importFromJson($definition, $json);
    }

    private function importCustomLayout(
        string $name,
        array $exportEntry,
        UserInterface $user,
    ): void {
        $className = $exportEntry['className'] ?? '';

        if ($className === '') {
            throw new EnvironmentException(
                sprintf('Missing className for custom layout "%s"', $name)
            );
        }

        $classDefinition = $this->classDefinitionRepository->getClassDefinition($className);
        $classId = $classDefinition->getId();

        $layout = $this->customLayoutResolver->getByNameAndClassId($name, $classId);

        if (!$layout instanceof CustomLayout) {
            $layout = $this->customLayoutResolver->create([
                'name' => $name,
                'userOwner' => $user->getId(),
                'classId' => $classId,
            ]);

            try {
                $layout->save();
            } catch (Exception $e) {
                throw new ElementSavingFailedException(
                    id: null,
                    error: sprintf('Failed to create custom layout "%s": %s', $name, $e->getMessage()),
                    previous: $e
                );
            }

        }

        $json = $this->prepareImportJson($exportEntry);

        try {
            $this->customLayoutRepository->importCustomLayoutFromJson($layout, $json);
        } catch (JsonEncodingException $e) {
            throw new InvalidArgumentException(
                'Layout does not contain valid JSON: ' . $e->getMessage(),
                $e,
            );
        }
    }

    private function importObjectBrick(string $key, array $exportEntry): void
    {
        $json = $this->prepareImportJson($exportEntry);

        try {
            $definition = $this->objectBrickRepository->getObjectBrickByKey($key);
        } catch (NotFoundException) {
            $definition = new ObjectBrickDefinition();
            $definition->setKey($key);
        }

        $this->objectBrickRepository->importFromJson($definition, $json);
    }
}
