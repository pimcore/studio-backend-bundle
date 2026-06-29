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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'Ownership Configuration Type',
    description: 'A manageable user-owned configuration type, represented as a tab in the ownership management area.',
    required: ['type', 'label', 'icon'],
    type: 'object',
)]
final class ConfigurationType implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'Unique type identifier',
            type: 'string',
            example: 'grid_configuration'
        )]
        private readonly string $type,
        #[Property(
            description: 'Translation key for the tab label',
            type: 'string',
            example: 'ownership_management_type_grid_configuration'
        )]
        private readonly string $label,
        #[Property(
            description: 'Icon identifier for the tab',
            type: 'string',
            example: 'table'
        )]
        private readonly string $icon,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
