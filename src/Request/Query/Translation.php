<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Request\Query;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioApiBundle\Util\Constants\PublicTranslations;

/**
 * @internal
 */
#[Schema(
    schema: 'Translation',
    title: 'Translation',
    description: 'Translation Scheme for API',
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
        private array $keys = [PublicTranslations::PUBLIC_KEYS]
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
