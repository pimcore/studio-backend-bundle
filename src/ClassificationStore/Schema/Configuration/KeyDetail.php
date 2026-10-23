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
    schema: 'ClassificationStoreConfigurationKeyDetail',
    title: 'Classification Store Configuration Key Detail',
    required: [
        'id', 'name', 'storeId', 'type', 'enabled', 'description',
        'definition', 'creationDate', 'modificationDate',
    ],
    type: 'object'
)]
final class KeyDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the key', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Name of the key', type: 'string', example: 'My Key')]
        private readonly string $name,
        #[Property(description: 'ID of the store this key belongs to', type: 'integer', example: 1)]
        private readonly int $storeId,
        #[Property(description: 'Data type of the key', type: 'string', example: 'input')]
        private readonly string $type,
        #[Property(description: 'Whether the key is enabled', type: 'boolean', example: true)]
        private readonly bool $enabled,
        #[Property(description: 'Description of the key', type: 'string', example: 'Key description')]
        private readonly ?string $description = null,
        #[Property(description: 'Definition of the key', type: 'object', nullable: true)]
        private readonly ?array $definition = [],
        #[Property(description: 'Creation date as Unix timestamp', type: 'integer', example: 1734567890)]
        private readonly ?int $creationDate = null,
        #[Property(description: 'Modification date as Unix timestamp', type: 'integer', example: 1734567890)]
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

    public function getType(): string
    {
        return $this->type;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDefinition(): ?array
    {
        return $this->definition;
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
