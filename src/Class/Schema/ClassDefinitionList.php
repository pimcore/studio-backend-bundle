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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'ClassDefinitionList',
    title: 'Class Definition List Item',
    required: [
        'id',
        'name',
        'title',
        'icon',
        'group',
    ],
    type: 'object'
)]
final class ClassDefinitionList implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id of class definition', type: 'string', example: 'AP')]
        private readonly string $id,
        #[Property(description: 'Name of class definition', type: 'string', example: 'AccessoryPart')]
        private readonly string $name,
        #[Property(description: 'Title', type: 'string', example: 'Accessory Part')]
        private readonly string $title,
        #[Property(description: 'icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,
        #[Property(description: 'Group', type: 'string', example: 'system')]
        private readonly ?string $group = null
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIcon(): ElementIcon
    {
        return $this->icon;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }
}
