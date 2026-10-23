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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'SimpleSearchDetail',
    required: [
        'id',
        'elementType',
        'type',
        'userOwner',
        'userOwnerName',
        'userModification',
        'userModificationName',
        'creationDate',
        'modificationDate',
    ],
    type: 'object'
)]
class SimpleSearchPreview implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id', type: 'int', example: '74')]
        private readonly int $id,
        #[Property(description: 'elementType', type: 'string', example: ElementTypes::TYPE_ASSET)]
        private readonly string $elementType,
        #[Property(description: 'Type', type: 'string', example: 'image')]
        private readonly string $type,
        #[Property(description: 'Id of owner', type: 'integer', example: 1)]
        private readonly ?int $userOwner,
        #[Property(description: 'Name of owner', type: 'string', example: 'admin')]
        private readonly ?string $userOwnerName,
        #[Property(description: 'Id of the user that modified the element', type: 'integer', example: 1)]
        private readonly ?int $userModification,
        #[Property(description: 'Name of the user that modified the element', type: 'integer', example: 'admin')]
        private readonly ?string $userModificationName,
        #[Property(description: 'Creation date', type: 'integer', example: 221846400)]
        private readonly ?int $creationDate,
        #[Property(description: 'Modification date', type: 'integer', example: 327417600)]
        private readonly ?int $modificationDate,
    ) {

    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    public function getUserOwnerName(): ?string
    {
        return $this->userOwnerName;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    public function getUserModificationName(): ?string
    {
        return $this->userModificationName;
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
