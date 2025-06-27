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

namespace Pimcore\Bundle\StudioBackendBundle\Email\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'EmailLogObjectParameterData',
    required: ['id', 'type', 'class', 'path'],
    type: 'object'
)]
final readonly class ObjectParameter
{
    public function __construct(
        #[Property(description: 'id', type: 'int', example: 1020)]
        private int $id,
        #[Property(description: 'elementType', type: 'string', example: 'object')]
        private string $elementType,
        #[Property(description: 'type', type: 'string', example: 'object')]
        private string $type,
        #[Property(description: 'class', type: 'string', example: 'AppBundle\\Model\\MyObject')]
        private string $class,
        #[Property(description: 'path', type: 'string', example: '/path/to/object')]
        private string $path,
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

    public function getClass(): string
    {
        return $this->class;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
