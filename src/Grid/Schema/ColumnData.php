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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'GridColumnData',
    type: 'object'
)]
final readonly class ColumnData
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'id')]
        private string $key,
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private ?string $locale,
        #[Property(description: 'value', type: 'mixed', example: 73)]
        private mixed $value
    )
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}