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

namespace Pimcore\Bundle\StudioBackendBundle\Translation\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PublicTranslations;

/**
 * @internal
 */
#[Schema(
    schema: 'DeltaItem',
    title: 'Delta Item',
    description: 'Translation delta item after merge',
    required: ['key', 'deltaValues'],
    type: 'object'
)]
final readonly class DeltaItem
{
    public function __construct(
        #[Property(description: 'Key of the translation', type: 'string', example: 'car')]
        private string $key,
        #[Property(
            description: 'List of translation deltas for the given key',
            type: 'array',
            items: new Items(ref: DeltaValues::class)
        )]
        private array $deltaValues = [],
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return DeltaValues[]
     */
    public function getDeltaValues(): array
    {
        return $this->deltaValues;
    }
}
