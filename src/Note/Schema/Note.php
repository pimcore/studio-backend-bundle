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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Note',
    required: ['id', 'type', 'cId', 'cType', 'cPath', 'date', 'title', 'description', 'locked', 'data'],
    type: 'object'
)]
final class Note implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'integer', example: 666)]
        private readonly int $id,
        #[Property(description: 'type', type: 'string', example: 'Type of note')]
        private readonly string $type,
        #[Property(description: 'Id of element', type: 'integer', example: 667)]
        private readonly int $cId,
        #[Property(description: 'Type of element', type: 'string', example: 'asset')]
        private readonly string $cType,
        #[Property(description: 'Path of element', type: 'string', example: '/path/to/element')]
        private readonly string $cPath,
        #[Property(description: 'Creation date of note', type: 'integer', example: 1634025600)]
        private readonly int $date,
        #[Property(description: 'title', type: 'string', example: 'Title of note')]
        private readonly string $title,
        #[Property(description: 'description', type: 'string', example: 'This is a description')]
        private readonly string $description,
        #[Property(description: 'Locked', type: 'boolean', example: false)]
        private readonly bool $locked,
        #[Property(
            description: 'Data of note',
            type: 'array',
            items: new Items(
                anyOf: [
                    new Schema(type: 'string'),
                    new Schema(type: 'number'),
                    new Schema(type: 'boolean'),
                    new Schema(type: 'object'),
                ]
            ),
            example: 'Can be pretty much anything',
        )]
        private readonly array $data,
        #[Property(description: 'User ID', type: 'integer', example: 1)]
        private readonly ?int $userId,
        #[Property(description: 'Username', type: 'string', example: 'shaquille.oatmeal')]
        private readonly ?string $userName
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCId(): int
    {
        return $this->cId;
    }

    public function getCType(): string
    {
        return $this->cType;
    }

    public function getCPath(): string
    {
        return $this->cPath;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }
}
