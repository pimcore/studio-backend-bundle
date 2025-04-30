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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use function in_array;

#[Schema(
    title: 'ElementIcon',
    required: [
        'type',
        'value',
    ],
    type: 'object'
)]
final readonly class ElementIcon
{
    public function __construct(
        #[Property(
            description: 'Icon type',
            type: 'string',
            enum: ElementIconTypes::class,
            example: ElementIconTypes::PATH->value
        )]
        private string $type,
        #[Property(description: 'Icon value', type: 'string', example: '/path/to/icon')]
        private string $value,
    ) {
        if (!in_array($this->type, ElementIconTypes::values(), true)) {
            throw new EnvironmentException('Invalid icon type');
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
