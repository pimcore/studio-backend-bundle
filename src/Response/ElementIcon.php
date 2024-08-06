<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Response;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementIconTypes;

/**
 * @internal
 */
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
            type: 'enum',
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
