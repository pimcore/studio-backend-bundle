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
    schema: 'ClassificationStoreConfigurationCollectionDetail',
    title: 'Classification Store Configuration Collection Detail',
    required: ['id', 'name', 'storeId', 'description', 'creationDate', 'modificationDate'],
    type: 'object'
)]
final class CollectionDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the collection', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Name of the collection', type: 'string', example: 'My Collection')]
        private readonly string $name,
        #[Property(description: 'ID of the store this collection belongs to', type: 'integer', example: 1)]
        private readonly int $storeId,
        #[Property(description: 'Description of the collection', type: 'string', example: 'Collection description')]
        private readonly ?string $description = null,
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
