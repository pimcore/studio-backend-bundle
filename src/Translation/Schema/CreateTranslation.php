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

/**
 * @internal
 */
#[Schema(
    schema: 'CreateTranslation',
    title: 'Translation Create',
    description: 'Translation Crete Scheme for API',
    required: ['translationData'],
    type: 'object'
)]
final readonly class CreateTranslation
{
    public function __construct(
        #[Property(description: 'Translation Data', type: 'array', items: new Items(ref: CreateTranslationData::class))]
        private array $translationData = []
    ) {
    }

    /**
     * @return array<CreateTranslationData>
     */
    public function getTranslationData(): array
    {
        return $this->translationData;
    }
}
