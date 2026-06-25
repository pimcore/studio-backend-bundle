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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * Normalized representation of a single user-owned configuration, regardless of its
 * concrete storage (Doctrine entity, configuration file, ...).
 */
#[Schema(
    title: 'Ownership Configuration',
    description: 'Configuration detail data',
    required: ['id', 'type', 'name', 'ownerId', 'ownerDeleted'],
    type: 'object',
)]
final class OwnershipConfiguration implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Unique identifier of the configuration', type: 'string', example: '42')]
        private readonly string $id,
        #[Property(description: 'Type identifier of the configuration', type: 'string', example: 'grid_configuration')]
        private readonly string $type,
        #[Property(description: 'Display name of the configuration', type: 'string', example: 'My grid view')]
        private readonly string $name,
        #[Property(description: 'User ID of the current owner', type: 'integer', example: 1)]
        private readonly int $ownerId,
        #[Property(
            description: 'Username of the current owner. Null when the owner has been deleted.',
            type: 'string',
            example: 'john_doe',
            nullable: true
        )]
        private readonly ?string $ownerName = null,
        #[Property(
            description: 'Whether the owner user no longer exists. When true the UI should show the owner id only.',
            type: 'boolean',
            example: false
        )]
        private readonly bool $ownerDeleted = false,
        #[Property(
            description: 'Creation date as a unix timestamp',
            type: 'integer',
            example: 1718000000,
            nullable: true
        )]
        private readonly ?int $creationDate = null,
        #[Property(
            description: 'Modification date as a unix timestamp',
            type: 'integer',
            example: 1718000000,
            nullable: true
        )]
        private readonly ?int $modificationDate = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getOwnerName(): ?string
    {
        return $this->ownerName;
    }

    public function isOwnerDeleted(): bool
    {
        return $this->ownerDeleted;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }
}
