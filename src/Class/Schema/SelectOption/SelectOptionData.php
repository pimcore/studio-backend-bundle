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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'SelectOptionData',
    title: 'Select Option Data',
    required: ['value', 'label', 'name'],
    type: 'object'
)]
final readonly class SelectOptionData
{
    public function __construct(
        #[Property(description: 'Value of the select option', type: 'string', example: 'active')]
        private string $value,
        #[Property(description: 'Display label of the select option', type: 'string', example: 'Active')]
        private string $label,
        #[Property(description: 'Enum case name of the select option', type: 'string', example: 'Active')]
        private string $name = '',
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
