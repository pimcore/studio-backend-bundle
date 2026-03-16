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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'FieldCollectionDetail',
    title: 'Field Collection Detail',
    required: ['key', 'title', 'group', 'parentClass', 'implementsInterfaces', 'blockedVarsForExport', 'isWriteable'],
    type: 'object'
)]
final class FieldCollectionDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'MyFieldCollection')]
        private readonly string $key,
        #[Property(description: 'Title', type: 'string', example: 'My Field Collection', nullable: true)]
        private readonly ?string $title,
        #[Property(description: 'Group', type: 'string', example: 'system', nullable: true)]
        private readonly ?string $group,
        #[Property(
            description: 'Namespace of parent class',
            type: 'string',
            example: 'App\\Model\\DataObject\\FieldCollection',
            nullable: true
        )]
        private readonly ?string $parentClass,
        #[Property(
            description: 'Interface implementations',
            type: 'string',
            example: 'App\\Model\\DataObject\\Interface',
            nullable: true
        )]
        private readonly ?string $implementsInterfaces,
        #[Property(
            description: 'Blocked variables for export',
            type: 'array',
            items: new Items(type: 'string'),
            example: []
        )]
        /** @var string[] */
        private readonly array $blockedVarsForExport,
        #[Property(
            description: 'Whether the field collection definition can be written to',
            type: 'boolean',
            example: true
        )]
        private readonly bool $isWriteable,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getParentClass(): ?string
    {
        return $this->parentClass;
    }

    public function getImplementsInterfaces(): ?string
    {
        return $this->implementsInterfaces;
    }

    /**
     * @return string[]
     */
    public function getBlockedVarsForExport(): array
    {
        return $this->blockedVarsForExport;
    }

    public function getIsWriteable(): bool
    {
        return $this->isWriteable;
    }
}
