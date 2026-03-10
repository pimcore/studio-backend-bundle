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

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkExport\BulkExportAvailableItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\BulkExport\BulkExportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\BulkExportParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport\BulkExportAvailableItem;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema\JsonExport;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function is_string;
use function json_decode;
use function json_encode;

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
        $user = $this->securityService->getCurrentUser();
        $items = [];

        foreach (ClassDefinitionType::cases() as $type) {
            if (!$user->isAllowed($type->permission())) {
                continue;
            }

            array_push($items, ...$this->collectItemsForType($type));
        }

        return $items;
    }

    /**
     * @return BulkExportAvailableItem[]
     */
    private function collectItemsForType(ClassDefinitionType $type): array
    {
        $items = [];

        foreach ($this->getDefinitionsForType($type) as [$name, $displayName]) {
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

        return $items;
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    private function getDefinitionsForType(ClassDefinitionType $type): iterable
    {
        return match ($type) {
            ClassDefinitionType::FieldCollection => $this->getFieldCollectionDefinitions(),
            ClassDefinitionType::ClassDefinition => $this->getClassDefinitions(),
            ClassDefinitionType::CustomLayout => $this->getCustomLayoutDefinitions(),
            ClassDefinitionType::ObjectBrick => $this->getObjectBrickDefinitions(),
        };
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    private function getFieldCollectionDefinitions(): iterable
    {
        foreach ($this->fieldCollectionRepository->listFieldCollections() as $definition) {
            yield [$definition->getKey(), $definition->getKey()];
        }
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    private function getClassDefinitions(): iterable
    {
        foreach ($this->classDefinitionRepository->getClassDefinitionsSortedById() as $definition) {
            yield [$definition->getName(), $definition->getName()];
        }
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    private function getCustomLayoutDefinitions(): iterable
    {
        foreach ($this->customLayoutRepository->getAllCustomLayoutsIncludingBricks() as $layout) {
            if (!$layout instanceof CustomLayout) {
                continue;
            }

            yield [(string) $layout->getId(), $this->resolveCustomLayoutDisplayName($layout)];
        }
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    private function getObjectBrickDefinitions(): iterable
    {
        foreach ($this->objectBrickRepository->listObjectBricks() as $definition) {
            yield [$definition->getKey(), $definition->getKey()];
        }
    }

    private function resolveCustomLayoutDisplayName(CustomLayout $layout): string
    {
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

        return $className !== ''
            ? $className . ' / ' . $layout->getName()
            : $layout->getName();
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
        if (!$user->isAllowed(ClassDefinitionType::FieldCollection->permission())) {
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
        if (!$user->isAllowed(ClassDefinitionType::ClassDefinition->permission())) {
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
        if (!$user->isAllowed(ClassDefinitionType::CustomLayout->permission())) {
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
        if (!$user->isAllowed(ClassDefinitionType::ObjectBrick->permission())) {
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
