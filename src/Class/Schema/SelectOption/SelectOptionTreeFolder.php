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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'SelectOptionTreeFolder',
    title: 'Select Option Tree Folder',
    required: ['id', 'name', 'icon', 'group', 'children'],
    type: 'object'
)]
final class SelectOptionTreeFolder implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id of select option', type: 'string', example: 'EventStatus')]
        private readonly string $id,
        #[Property(description: 'Text of select option', type: 'string', example: 'EventStatus')]
        private readonly string $name,
        #[Property(description: 'icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,
        #[Property(description: 'Group', type: 'string', example: 'system')]
        private readonly ?string $group = null,
        #[Property(
            description: 'Child nodes',
            type: 'array',
            items: new Items(ref: SelectOptionTree::class)
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

    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @return SelectOptionTree[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
