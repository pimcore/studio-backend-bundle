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
    schema: 'UpdateTranslation',
    title: 'Translation Update',
    description: 'Translation Update Scheme for API',
    required: ['key', 'type', 'translationData'],
    type: 'object'
)]
final readonly class UpdateTranslation
{
    public function __construct(
        #[Property(description: 'Key of the translation', type: 'string', example: 'car')]
        private string $key,
        #[Property(description: 'Type of the translation', type: 'string', example: 'simple')]
        private ?string $type = null,
        #[Property(description: 'Translation Data', type: 'array', items: new Items(ref: TranslationData::class))]
        private array $translationData = []
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return array<TranslationData>
     */
    public function getTranslationData(): array
    {
        return $this->translationData;
    }
}
