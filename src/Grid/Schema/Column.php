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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\AdvancedColumnConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\RelationFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\SimpleFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\StaticTextConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\Transformer;

/**
 * Contains all data that is needed to get all the data for the column.
 *
 */
#[Schema(
    title: 'Grid Column Request',
    required: ['type'],
    type: 'object'
)]
final readonly class Column
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'id')]
        private string $key,
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private ?string $locale,
        #[Property(description: 'Type', type: 'string', example: 'system.id')]
        private string $type,
        #[Property(description: 'Group', type: 'array', items: new Items(type: 'string'), example: ['system'])]
        private ?array $group,
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
        private bool $applyFallbackLanguages = false,
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

    public function getGroup(): ?array
    {
        return $this->group;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getApplyFallbackLanguages(): bool
    {
        return $this->applyFallbackLanguages;
    }

    public function getAdvancedColumnConfig(): AdvancedColumnConfig
    {
        $configs = [];
        if (!isset($this->config['advancedColumns'])) {
            throw new InvalidArgumentException('Advanced column config is not set');
        }

        foreach ($this->config['advancedColumns'] as $advancedColumn) {
            if ($advancedColumn['key'] === 'relationField') {
                $configs[] = new RelationFieldConfig(
                    relation: $advancedColumn['config']['relation'],
                    field: $advancedColumn['config']['field'],
                );

                continue;
            }

            if ($advancedColumn['key'] === 'simpleField') {
                $configs[] = new SimpleFieldConfig(
                    field: $advancedColumn['config']['field'],
                );

                continue;
            }

            if ($advancedColumn['key'] === 'staticText') {
                $configs[] = new StaticTextConfig(
                    text: $advancedColumn['config']['text'],
                );
            }
        }

        return new AdvancedColumnConfig(
            $configs,
            $this->getTransformers()
        );
    }

    /**
     *
     * @return Transformer[]
     */
    private function getTransformers(): array
    {
        $transformers = [];
        if (isset($this->config['transformers'])) {
            foreach ($this->config['transformers'] as $transformer) {
                if (isset($transformer['key'])) {

                    // Inject locale into original config array if not set
                    if ($this->getLocale() !== null) {
                        $transformer['config']['locale'] = $this->getLocale();
                    }

                    $transformers[] = new Transformer(
                        key: $transformer['key'],
                        config: $transformer['config'] ?? []
                    );
                }
            }
        }

        return $transformers;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->getKey(),
            'locale' => $this->getLocale(),
            'type' => $this->getType(),
            'group' => $this->getGroup(),
            'config' => $this->getConfig(),
        ];
    }
}
