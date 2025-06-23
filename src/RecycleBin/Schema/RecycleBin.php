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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'RecycleBin',
    title: 'Recycle Bin',
    required: ['id', 'amount', 'date', 'deletedBy', 'path', 'subtype', 'type'],
    type: 'object'
)]
final class RecycleBin implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Amount', type: 'integer', example: 1)]
        private readonly int $amount,
        #[Property(description: 'Date', type: 'integer', example: 1617267600)]
        private readonly int $date,
        #[Property(description: 'Deleted By', type: 'string', example: 'admin')]
        private readonly string $deletedBy,
        #[Property(description: 'Path', type: 'string', example: '/path/to/element')]
        private readonly string $path,
        #[Property(description: 'Subtype', type: 'string', example: 'folder')]
        private readonly string $subtype,
        #[Property(description: 'Type', type: 'string', example: 'asset')]
        private readonly string $type,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function getDeletedBy(): string
    {
        return $this->deletedBy;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSubtype(): string
    {
        return $this->subtype;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
