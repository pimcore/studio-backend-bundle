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
    schema: 'TranslationDeltaValues',
    title: 'Translation delta values',
    description: 'Translation delta values',
    required: ['locale', 'currentTranslation', 'importTranslation'],
    type: 'object'
)]
final readonly class DeltaValues
{
    public function __construct(
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private string $locale,
        #[Property(description: 'Current translation', type: 'string', example: 'some translation')]
        private string $currentTranslation,
        #[Property(description: 'Imported translation', type: 'string', example: 'some translation updated')]
        private string $importTranslation,
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCurrentTranslation(): string
    {
        return $this->currentTranslation;
    }

    public function getImportTranslation(): string
    {
        return $this->importTranslation;
    }
}
