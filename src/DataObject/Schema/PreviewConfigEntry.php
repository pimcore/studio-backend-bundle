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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Preview Config Entry',
    required: ['name', 'label', 'values', 'defaultValue'],
    type: 'object',
)]
final class PreviewConfigEntry implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Parameter name', type: 'string', example: 'locale')]
        private readonly string $name,
        #[Property(description: 'Display label', type: 'string', example: 'Locale')]
        private readonly string $label,
        #[Property(
            description: 'Available values as key-value pairs',
            type: 'array',
            items: new Items(
                properties: [
                    new Property(property: 'key', type: 'string', example: 'English (en)'),
                    new Property(property: 'value', type: 'string', example: 'en'),
                ],
                type: 'object',
            ),
        )]
        private readonly array $values,
        #[Property(description: 'Default selected value', type: 'string', example: 'en')]
        private readonly string $defaultValue,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }
}
