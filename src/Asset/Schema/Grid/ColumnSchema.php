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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'Column',
    required: [
        'key',
        'locale',
        'group',
    ],
    type: 'object'
)]
readonly class ColumnSchema
{
    public function __construct(
        #[Property(description: 'Key of the Column', type: 'string', example: 'id')]
        private string $key,
        #[Property(description: 'Locale of the Column', type: 'string', example: 'de')]
        private ?string $locale,
        #[Property(description: 'Group of the Column', type: 'string', example: 'system')]
        private string  $group,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'locale' => $this->locale,
            'group' => $this->group,
        ];
    }
}
