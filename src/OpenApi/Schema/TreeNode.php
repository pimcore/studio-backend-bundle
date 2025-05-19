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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Tree Node',
    description: 'One node in the a tree',
    required: ['id', 'name', 'type', 'hasChildren'],
    type: 'object'
)]
final class TreeNode implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Unique Identifier', type: 'integer', example: '1')]
        private readonly int $id,
        #[Property(description: 'Name of the tree node', type: 'string', example: 'admin')]
        private readonly string $name,
        #[Property(description: 'Is ether folder or a specific item in the folder', type: 'string', example: 'user')]
        private readonly string $type,
        #[Property(description: 'If a folder has sub items', type: 'bool', example: true)]
        private readonly bool $hasChildren,
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

    public function getType(): string
    {
        return $this->type;
    }

    public function hasChildren(): bool
    {
        return $this->hasChildren;
    }
}
