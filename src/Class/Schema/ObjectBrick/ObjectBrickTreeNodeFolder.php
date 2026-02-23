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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ObjectBrickTreeNodeFolder',
    title: 'Object Brick Tree Node Folder',
    required: ['key', 'name', 'icon', 'group', 'children'],
    type: 'object'
)]
final class ObjectBrickTreeNodeFolder implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key of folder with group_ prefix', type: 'string', example: 'group_Parts')]
        private readonly string $key,
        #[Property(description: 'Group name', type: 'string', example: 'Parts')]
        private readonly string $name,
        #[Property(description: 'icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,
        #[Property(description: 'Group', type: 'string', example: 'Parts')]
        private readonly string $group,
        #[Property(
            description: 'Child nodes',
            type: 'array',
            items: new Items(ref: ObjectBrickTreeNode::class)
        )]
        private readonly array $children = [],
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): ElementIcon
    {
        return $this->icon;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @return ObjectBrickTreeNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
