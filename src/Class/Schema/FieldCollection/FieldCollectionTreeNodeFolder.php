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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'FieldCollectionTreeNodeFolder',
    title: 'Field Collection Tree Node Folder',
    required: ['id', 'title', 'icon', 'children'],
    type: 'object'
)]
final class FieldCollectionTreeNodeFolder implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id of folder with group_ prefix', type: 'string', example: 'group_News')]
        private readonly string $id,
        #[Property(description: 'Group name', type: 'string', example: 'News')]
        private readonly string $name,
        #[Property(description: 'icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,
        #[Property(description: 'Group', type: 'string', example: 'News')]
        private readonly string $group,
        #[Property(
            description: 'Child nodes',
            type: 'array',
            items: new Items(ref: FieldCollectionTreeNode::class)
        )]
        private readonly array $children = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
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
     * @return FieldCollectionTreeNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
