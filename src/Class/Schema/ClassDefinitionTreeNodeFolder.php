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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    schema: 'ClassDefinitionTreeNodeFolder',
    title: 'Class Definition Tree Node Folder',
    required: ['children'],
    type: 'object'
)]
final class ClassDefinitionTreeNodeFolder extends ClassDefinitionList
{
    public function __construct(
        string $id,
        string $name,
        string $title,
        ElementIcon $icon,
        ?string $group = null,
        #[Property(
            description: 'Child nodes',
            type: 'array',
            items: new Items(ref: ClassDefinitionTreeNode::class)
        )]
        private readonly array $children = [],
    ) {
        parent::__construct($id, $name, $title, $icon, $group);
    }

    /**
     * @return ClassDefinitionTreeNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
