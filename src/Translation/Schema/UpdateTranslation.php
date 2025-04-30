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
    required: ['locale', 'translationData'],
    type: 'object'
)]
final readonly class UpdateTranslation
{
    public function __construct(
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private string $locale = 'en',
        #[Property(description: 'Translation Data', type: 'array', items: new Items(ref: TranslationData::class))]
        private array $translationData = []
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<TranslationData>
     */
    public function getTranslationData(): array
    {
        return $this->translationData;
    }
}
