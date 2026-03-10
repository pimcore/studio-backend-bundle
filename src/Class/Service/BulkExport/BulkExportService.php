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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkExport;

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkExport\BulkExportAvailableItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\BulkExport\BulkExportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\BulkExportParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\JsonExport;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function json_decode;
use function json_encode;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final readonly class BulkExportService implements BulkExportServiceInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private FieldCollectionRepositoryInterface $fieldCollectionRepository,
        private ObjectBrickRepositoryInterface $objectBrickRepository,
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private BulkExportHydratorInterface $bulkExportHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableItems(): array
    {
        $items = [];
        $user = $this->securityService->getCurrentUser();

        if ($user->isAllowed(UserPermissions::FIELD_COLLECTIONS->value)) {
            foreach ($this->fieldCollectionRepository->listFieldCollections() as $definition) {
                $item = $this->bulkExportHydrator->hydrateAvailableItem(
                    ClassDefinitionType::FieldCollection->value,
                    $definition->getKey(),
                    $definition->getKey(),
                    ClassDefinitionType::FieldCollection->icon()
                );
                $this->eventDispatcher->dispatch(
                    new BulkExportAvailableItemEvent($item),
                    BulkExportAvailableItemEvent::EVENT_NAME
                );
                $items[] = $item;
            }
        }

        if ($user->isAllowed(UserPermissions::CLASS_DEFINITION->value)) {
            foreach ($this->classDefinitionRepository->getClassDefinitionsSortedById() as $definition) {
                $item = $this->bulkExportHydrator->hydrateAvailableItem(
                    ClassDefinitionType::ClassDefinition->value,
                    $definition->getName(),
                    $definition->getName(),
                    ClassDefinitionType::ClassDefinition->icon()
                );
                $this->eventDispatcher->dispatch(
                    new BulkExportAvailableItemEvent($item),
                    BulkExportAvailableItemEvent::EVENT_NAME
                );
                $items[] = $item;
            }
        }

        if ($user->isAllowed(UserPermissions::CLASS_DEFINITION->value)) {
            foreach ($this->customLayoutRepository->getAllCustomLayoutsIncludingBricks() as $layout) {
                if (!$layout instanceof CustomLayout) {
                    continue;
                }

                $className = '';
                if ($layout->getClassId()) {
                    try {
                        $classDefinition = $this->classDefinitionRepository->getClassDefinitionById(
                            $layout->getClassId()
                        );
                        $className = $classDefinition->getName();
                    } catch (NotFoundException) {
                        // class isn't found, leave className empty
                    }
                }

                $displayName = $className !== ''
                    ? $className . ' / ' . $layout->getName()
                    : $layout->getName();

                $item = $this->bulkExportHydrator->hydrateAvailableItem(
                    ClassDefinitionType::CustomLayout->value,
                    (string) $layout->getId(),
                    $displayName,
                    ClassDefinitionType::CustomLayout->icon()
                );
                $this->eventDispatcher->dispatch(
                    new BulkExportAvailableItemEvent($item),
                    BulkExportAvailableItemEvent::EVENT_NAME
                );
                $items[] = $item;
            }
        }

        if ($user->isAllowed(UserPermissions::OBJECT_BRICKS->value)) {
            foreach ($this->objectBrickRepository->listObjectBricks() as $definition) {
                $item = $this->bulkExportHydrator->hydrateAvailableItem(
                    ClassDefinitionType::ObjectBrick->value,
                    $definition->getKey(),
                    $definition->getKey(),
                    ClassDefinitionType::ObjectBrick->icon()
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

    /**
     * {@inheritdoc}
     */
    public function exportItems(BulkExportParameters $parameters): JsonExport
    {
        $user = $this->securityService->getCurrentUser();
        $exportData = [
            ClassDefinitionType::FieldCollection->value => [],
            ClassDefinitionType::ClassDefinition->value => [],
            ClassDefinitionType::CustomLayout->value => [],
            ClassDefinitionType::ObjectBrick->value => [],
        ];

        foreach ($parameters->getItems() as $item) {
            $type = $item['type'] ?? null;
            $name = $item['name'] ?? null;

            if (!is_string($type) || !is_string($name) || $type === '' || $name === '') {
                continue;
            }

            $classDefinitionType = ClassDefinitionType::tryFrom($type);
            if ($classDefinitionType === null) {
                continue;
            }

            match ($classDefinitionType) {
                ClassDefinitionType::FieldCollection => $this->exportFieldCollection(
                    $name,
                    $user,
                    $exportData
                ),
                ClassDefinitionType::ClassDefinition => $this->exportClass(
                    $name,
                    $user,
                    $exportData
                ),
                ClassDefinitionType::CustomLayout => $this->exportCustomLayout(
                    $name,
                    $user,
                    $exportData
                ),
                ClassDefinitionType::ObjectBrick => $this->exportObjectBrick(
                    $name,
                    $user,
                    $exportData
                ),
            };
        }

        try {
            $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiInvalidArgumentException(
                'Export data could not be encoded to JSON: ' . $e->getMessage(),
                $e,
            );
        }

        return new JsonExport($json, 'bulk_export.json');
    }

    private function exportFieldCollection(
        string $key,
        mixed $user,
        array &$exportData
    ): void {
        if (!$user->isAllowed(UserPermissions::FIELD_COLLECTIONS->value)) {
            return;
        }

        $definition = $this->fieldCollectionRepository->getFieldCollectionByKey($key);
        $json = $this->fieldCollectionRepository->exportAsJson($definition);
        $data = $this->decodeExportJson($json);
        $data['key'] = $key;
        $exportData[ClassDefinitionType::FieldCollection->value][] = $data;
    }

    private function exportClass(
        string $name,
        mixed $user,
        array &$exportData
    ): void {
        if (!$user->isAllowed(UserPermissions::CLASS_DEFINITION->value)) {
            return;
        }

        $definition = $this->classDefinitionRepository->getClassDefinition($name);
        $json = $this->classDefinitionRepository->exportAsJson($definition);
        $data = $this->decodeExportJson($json);
        $data['name'] = $name;
        $exportData[ClassDefinitionType::ClassDefinition->value][] = $data;
    }

    private function exportCustomLayout(
        string $layoutId,
        mixed $user,
        array &$exportData
    ): void {
        if (!$user->isAllowed(UserPermissions::CLASS_DEFINITION->value)) {
            return;
        }

        $layout = $this->customLayoutRepository->getCustomLayout($layoutId);
        $json = $this->customLayoutRepository->exportCustomLayoutAsJson($layout);
        $data = $this->decodeExportJson($json);

        $className = '';
        if ($layout->getClassId()) {
            try {
                $classDefinition = $this->classDefinitionRepository->getClassDefinitionById(
                    $layout->getClassId()
                );
                $className = $classDefinition->getName();
            } catch (NotFoundException) {
                // class isn't found, leave className empty
            }
        }

        $data['originalId'] = $layoutId;
        $data['name'] = $layout->getName();
        $data['className'] = $className;
        $exportData[ClassDefinitionType::CustomLayout->value][] = $data;
    }

    private function exportObjectBrick(
        string $key,
        mixed $user,
        array &$exportData
    ): void {
        if (!$user->isAllowed(UserPermissions::OBJECT_BRICKS->value)) {
            return;
        }

        $definition = $this->objectBrickRepository->getObjectBrickByKey($key);
        $json = $this->objectBrickRepository->exportAsJson($definition);
        $data = $this->decodeExportJson($json);
        $data['key'] = $key;
        $exportData[ClassDefinitionType::ObjectBrick->value][] = $data;
    }

    private function decodeExportJson(string $json): array
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiInvalidArgumentException(
                'Export data does not contain valid string for JSON: ' . $e->getMessage(),
                $e,
            );
        }
    }
}
