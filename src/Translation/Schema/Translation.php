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
    schema: 'Translation',
    title: 'Translation',
    description: 'Translation Scheme for API',
    required: ['locale', 'keys'],
    type: 'object'
)]
final readonly class Translation
{
    public function __construct(
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private string $locale = 'en',
        #[Property(description: 'Keys', type: 'array', items: new Items(
            type: 'string', example: 'not_your_typical_key'
        ))]
        private array $keys = PublicTranslations::PUBLIC_KEYS
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }
}
