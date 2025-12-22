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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'CustomLayoutIdentifierData',
    title: 'Custom Layout Identifier Data',
    required: [
        'suggestedId',
        'existingIds',
        'existingNames',
    ],
    type: 'object'
)]
final class CustomLayoutIdentifierData implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Suggested unique ID for custom layout', type: 'string', example: 'custom_layout_1')]
        private readonly string $suggestedId,
        #[Property(
            description: 'Array of existing custom layout IDs',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['custom_layout_1', 'custom_layout_2', 'custom_layout_3']
        )]
        private readonly array $existingIds,
        #[Property(
            description: 'Array of existing custom layout names',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['Custom Layout 1', 'Custom Layout 2', 'Custom Layout 3']
        )]
        private readonly array $existingNames
    ) {
    }

    public function getSuggestedId(): string
    {
        return $this->suggestedId;
    }

    /**
     * @return string[]
     */
    public function getExistingIds(): array
    {
        return $this->existingIds;
    }

    /**
     * @return string[]
     */
    public function getExistingNames(): array
    {
        return $this->existingNames;
    }
}

