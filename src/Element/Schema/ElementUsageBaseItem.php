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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

#[Schema(
    schema: 'ElementUsageBaseItem',
    title: 'Element Usage Base Item',
    required: [
        'id',
        'type'
    ],
    type: 'object'
)]
class ElementUsageBaseItem
{
    public function __construct(
        #[Property(
            description: 'ID',
            type: 'integer',
            example: 83
        )]
        private readonly int $id,
        #[Property(
            description: 'type',
            type: 'string',
            enum: ElementTypes::ALLOWED_TYPES,
            example: ElementTypes::TYPE_DATA_OBJECT
        )]
        private readonly string $type
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
