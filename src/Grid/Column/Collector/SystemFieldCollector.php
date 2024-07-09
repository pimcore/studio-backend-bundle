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
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\SystemColumnServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use function array_key_exists;

/**
 * @internal
 */
final readonly class SystemFieldCollector implements ColumnCollectorInterface
{
    public function __construct(
        private SystemColumnServiceInterface $systemColumnService,
    ) {
    }

    public function getCollectorName(): string
    {
        return 'system';
    }

    /**
     * @param ColumnDefinitionInterface[] $availableColumnDefinitions
     *
     * @return Column[]
     */
    public function getColumnDefinitions(array $availableColumnDefinitions): array
    {
        $systemColumns = $this->systemColumnService->getSystemColumnsForAssets();
        $columns = [];
        foreach ($systemColumns as $columnKey => $type) {
            $type = $this->getConcatedType($type);
            if (!array_key_exists($type, $availableColumnDefinitions)) {
                continue;
            }

            $column = new Column(
                key: $columnKey,
                group: $this->getCollectorName(),
                sortable: $availableColumnDefinitions[$type]->isSortable(),
                editable: false,
                localizable: false,
                locale: null,
                type: $availableColumnDefinitions[$type]->getType(),
                config: []
            );

            $columns[] = $column;
        }

        return $columns;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }

    private function getConcatedType($type): string
    {
        return $this->getCollectorName() . '.' . $type;
    }
}
