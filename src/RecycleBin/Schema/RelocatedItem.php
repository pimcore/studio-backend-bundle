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

/**
 * @internal
 */
#[Schema(
    schema: 'RecycleBinRelocatedItem',
    title: 'Recycle Bin Relocated Item',
    description: 'Element restored into the fallback folder because its parent is missing',
    required: ['id', 'elementType', 'path'],
    type: 'object'
)]
final class RelocatedItem implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the restored element', type: 'integer', example: 83)]
        private readonly int $id,
        #[Property(description: 'Element type', type: 'string', example: 'data-object')]
        private readonly string $elementType,
        #[Property(description: 'New full path of the element', type: 'string', example: '/__restored/my-car')]
        private readonly string $path,
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

    public function getPath(): string
    {
        return $this->path;
    }
}
