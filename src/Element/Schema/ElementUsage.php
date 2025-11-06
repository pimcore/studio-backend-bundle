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

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ElementUsage',
    title: 'Element Usage',
    required: [
        'data',
        'hasHidden',
    ],
    type: 'object'
)]
final class ElementUsage implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Data', type: 'array', items: new Items(ref: ElementUsageItem::class))]
        private readonly array $data,
        #[Property(description: 'hasHidden', type: 'bool', example: 'false')]
        private readonly bool $hasHidden,
        #[Property(description: 'totalCount', type: 'int', example: '40')]
        private readonly int $totalCount,
    ) {
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isHasHidden(): bool
    {
        return $this->hasHidden;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
}
