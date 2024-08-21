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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FrontendType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Repository\MetadataRepositoryInterface;
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
        $defaultMetadata = ['title', 'alt', 'copyright'];
        $columns = [];
        foreach ($defaultMetadata as $metadata) {
            $columns[] = new ColumnConfiguration(
                key: $metadata,
                group: 'default_metadata',
                sortable: true,
                editable: true,
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
                localizable: false,
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
