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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ClassificationStoreConfigurationSubGroupDetail',
    title: 'Classification Store Configuration Sub Group Detail',
    required: ['id', 'name', 'storeId', 'identifier', 'description', 'creationDate', 'modificationDate'],
    type: 'object'
)]
final class SubGroupDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the sub group', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Name of the sub group', type: 'string', example: 'My Sub Group')]
        private readonly string $name,
        #[Property(description: 'ID of the store this sub group belongs to', type: 'integer', example: 1)]
        private readonly int $storeId,
        #[Property(
            description: 'Immutable identifier a key assignment carries to name this sub group',
            type: 'string',
            example: '01JBZ8QF6K3M4N5P6R7S8T9V0W',
            nullable: true,
            readOnly: true
        )]
        private readonly ?string $identifier = null,
        #[Property(
            description: 'Description of the sub group',
            type: 'string',
            example: 'Sub group description',
            nullable: true
        )]
        private readonly ?string $description = null,
        #[Property(
            description: 'Creation date as Unix timestamp',
            type: 'integer',
            example: 1734567890,
            nullable: true
        )]
        private readonly ?int $creationDate = null,
        #[Property(
            description: 'Modification date as Unix timestamp',
            type: 'integer',
            example: 1734567890,
            nullable: true
        )]
        private readonly ?int $modificationDate = null,
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

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
