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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ClassDefinitionIdentifierData',
    title: 'Class Definition Identifier Data',
    required: [
        'suggestedId',
        'existingIds',
    ],
    type: 'object'
)]
class ClassDefinitionIdentifierData implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Suggested unique ID for the new class definition', type: 'string', example: 'AP')]
        private readonly string $suggestedId,
        #[Property(
            description: 'Array of existing class definition IDs',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['AP', 'CP', 'MP']
        )]
        private readonly array $existingIds
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
}
