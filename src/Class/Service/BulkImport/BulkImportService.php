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

use Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkExport\BulkExportAvailableItemEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\BulkImport\BulkImportPrepareEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\BulkExport\BulkExportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkExport\BulkExportAvailableItem;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\BulkImport\BulkImportPrepareResponse;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
final readonly class BulkImportService implements BulkImportServiceInterface
{
    public function __construct(
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

            array_push($items, ...$this->collectItemsForType($type, $dataForType));
        }

        return $items;
    }

    /**
     * @return BulkExportAvailableItem[]
     */
    private function collectItemsForType(ClassDefinitionType $type, array $dataForType): array
    {
        $items = [];

        foreach ($dataForType as $exportEntry) {
            if (!is_array($exportEntry)) {
                continue;
            }

            $name = $this->bulkImportDataResolver->resolveEntryName($type, $exportEntry);
            if ($name === null) {
                continue;
            }

            $item = $this->bulkExportHydrator->hydrateAvailableItem(
                $type->value,
                $name,
                $this->resolveDisplayName($type, $name, $exportEntry),
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

    private function resolveDisplayName(
        ClassDefinitionType $type,
        string $name,
        array $exportEntry,
    ): string {
        if ($type !== ClassDefinitionType::CustomLayout) {
            return $name;
        }

        $className = $exportEntry['className'] ?? '';

        return $className !== '' ? $className . ' / ' . $name : $name;
    }
}
