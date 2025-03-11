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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\AdvancedColumnConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\RelationFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\SimpleFieldConfig;

/**
 * Contains all data that is needed to get all the data for the column.
 *
 * @internal
 */
#[Schema(
    title: 'Grid Column Request',
    required: ['key', 'type', 'config'],
    type: 'object'
)]
final readonly class Column
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'id')]
        private string $key,
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private ?string $locale,
        #[Property(description: 'Type', type: 'string', example: 'system.integer')]
        private string $type,
        #[Property(description: 'Group', type: 'string', example: 'system')]
        private ?string $group,
        #[Property(
            description: 'Config',
            type: 'array',
            items: new Items(
                anyOf: [
                    new Schema(type: 'string'),
                    new Schema(ref: AdvancedColumnConfig::class),
                ]
            ),
            example: ['key' => 'value'])]
        private array $config,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getAdvancedColumnConfig(): AdvancedColumnConfig
    {
        $configs = [];
        if (!isset($this->config['advancedColumns'])) {
            throw new InvalidArgumentException('Advanced column config is not set');
        }

        foreach ($this->config['advancedColumns'] as $advancedColumn) {
            if (isset($advancedColumn['field']) && isset($advancedColumn['relation'])) {
                $configs[] = new RelationFieldConfig(
                    relation: $advancedColumn['relation'],
                    field: $advancedColumn['field'],
                );

                continue;
            }

            if (isset($advancedColumn['field'])) {
                $configs[] = new SimpleFieldConfig(
                    field: $advancedColumn['field'],
                );
            }
        }

        return new AdvancedColumnConfig($configs);
    }
}
