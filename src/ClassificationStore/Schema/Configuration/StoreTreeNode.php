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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ClassificationStoreConfigurationStoreTreeNode',
    title: 'Classification Store Configuration Store Tree Node',
    required: ['id', 'name', 'description'],
    type: 'object'
)]
final class StoreTreeNode implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the store', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Name of the store', type: 'string', example: 'My Store')]
        private readonly string $name,
        #[Property(
            description: 'Description of the store',
            type: 'string',
            example: 'Store description',
            nullable: true
        )]
        private readonly ?string $description = null,
        #[Property(
            description: 'Opaque identifier of the store, assigned by the system and never writable',
            type: 'string',
            example: '01K9T0ZQ0VX8W4S1R7J3A2B5C6',
            nullable: true
        )]
        private readonly ?string $identifier = null,
        #[Property(
            description: 'The engine the store stores its values with, as the store stores it; null where it stores'
                . ' none, which is the default engine, and null as well on a core that does not report an engine'
                . ' at all.',
            type: 'string',
            example: 'document',
            nullable: true
        )]
        private readonly ?string $engine = null,
        #[Property(
            description: 'The capabilities the store\'s engine reports, naming exactly those it supports. An empty'
                . ' array is an engine that reports none, which is a different answer. Null is every case where'
                . ' the question could not be put: a core that does not report an engine, no engine resolver on'
                . ' this installation, or an engine that failed to resolve. A client cannot tell those apart and'
                . ' must not infer core support from it.',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['MultiValue', 'Variants'],
            nullable: true
        )]
        private readonly ?array $engineCapabilities = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function getEngineCapabilities(): ?array
    {
        return $this->engineCapabilities;
    }
}
