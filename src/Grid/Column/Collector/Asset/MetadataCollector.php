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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\Asset;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FrontendType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Repository\MetadataRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Service\MetadataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use function array_key_exists;

/**
 * @internal
 */
final readonly class MetadataCollector implements ColumnCollectorInterface
{
    public function __construct(
        private MetadataRepositoryInterface $metadataRepository,
    ) {
    }

    public function getCollectorName(): string
    {
        return 'metadata';
    }

    /**
     * @param ColumnDefinitionInterface[] $availableColumnDefinitions
     *
     * @return ColumnConfiguration[]
     */
    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
        return array_merge(
            $this->getDefaultMetadata(),
            $this->getPredefinedMetadata($availableColumnDefinitions)
        );
    }

    /**
     *
     * @return ColumnConfiguration[]
     */
    private function getDefaultMetadata(): array
    {
        $defaultMetadata = MetadataServiceInterface::DEFAULT_METADATA;
        $columns = [];
        foreach ($defaultMetadata as $metadata) {
            $columns[] = new ColumnConfiguration(
                key: $metadata,
                group: 'default_metadata',
                sortable: true,
                editable: true,
                exportable: true,
                filterable: true,
                localizable: true,
                locale: null,
                type: 'metadata.input',
                frontendType: FrontendType::INPUT->value,
                config: []
            );
        }

        return $columns;
    }

    /**
     * @param ColumnDefinitionInterface[] $availableColumnDefinitions
     *
     * @return ColumnConfiguration[]
     */
    private function getPredefinedMetadata(array $availableColumnDefinitions): array
    {
        $predefinedMetadata = $this->metadataRepository->getAllPredefinedMetadata();
        $columns = [];

        foreach ($predefinedMetadata as $item) {
            $type = $this->concatType($item->getType());
            if (!array_key_exists($type, $availableColumnDefinitions)) {
                continue;
            }

            $columns[] = new ColumnConfiguration(
                key: $item->getName(),
                group: 'predefined_metadata',
                sortable: $availableColumnDefinitions[$type]->isSortable(),
                editable: true,
                exportable: $availableColumnDefinitions[$type]->isExportable(),
                filterable: $availableColumnDefinitions[$type]->isFilterable(),
                localizable: true,
                locale: null,
                type: $type,
                frontendType: $availableColumnDefinitions[$type]->getFrontendType(),
                config: $availableColumnDefinitions[$type]->getConfig($item->getConfig())
            );
        }

        return $columns;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }

    private function concatType(string $type): string
    {
        return $this->getCollectorName() . '.' . $type;
    }
}
