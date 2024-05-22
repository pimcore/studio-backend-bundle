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

namespace Pimcore\Bundle\StudioBackendBundle\Note\Schema;

use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'Note',
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
            type: 'object',
            example: 'Can be pretty much anything',
            additionalProperties: new AdditionalProperties(
                oneOf: [
                    new Schema(type: 'string'),
                    new Schema(type: 'number'),
                    new Schema(type: 'boolean'),
                    new Schema(type: 'object'),
                    new Schema(type: 'array', items: new Items()),
                ]
            )
        )]
        private readonly array $data,
        #[Property(description: 'User ID', type: 'integer', example: 1)]
        private readonly ?int $userId,
        #[Property(description: 'Username', type: 'string', example: 'shaquille.oatmeal')]
        private readonly ?string $userName
    )
    {
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
