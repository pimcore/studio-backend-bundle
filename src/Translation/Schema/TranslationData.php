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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'TranslationData',
    title: 'Translation Data',
    description: 'Translation Data Scheme for API',
    required: ['locale', 'translation'],
    type: 'object'
)]
final readonly class TranslationData
{
    public function __construct(
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private string $locale,
        #[Property(description: 'Translation', type: 'string', example: 'some_translated_string')]
        private string $translation,
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }
}
