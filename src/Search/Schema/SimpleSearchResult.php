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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\CustomAttributesTrait;

#[Schema(
    title: 'SimpleSearchResult',
    required: ['id', 'elementType', 'type', 'path', 'icon'],
    type: 'object'
)]
final class SimpleSearchResult implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;
    use CustomAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'int', example: '74')]
        private readonly int $id,
        #[Property(description: 'elementType', type: 'string', example: ElementTypes::TYPE_ASSET)]
        private readonly string $elementType,
        #[Property(description: 'type', type: 'string', example: 'image')]
        private readonly string $type,
        #[Property(description: 'path', type: 'string', example: '/path/to/asset')]
        private readonly string $path,
        #[Property(description: 'icon', type: ElementIcon::class)]
        private readonly ElementIcon $icon,

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

    public function getPath(): string
    {
        return $this->path;
    }

    public function getIcon(): ElementIcon
    {
        return $this->icon;
    }
}
