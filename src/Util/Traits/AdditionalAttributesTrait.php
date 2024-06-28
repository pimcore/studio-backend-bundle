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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
trait AdditionalAttributesTrait
{
    #[Property(
        description: 'AdditionalAttributes',
        type: 'object',
        additionalProperties: new AdditionalProperties(
            anyOf: [
                new Schema(type: 'string'),
                new Schema(type: 'number'),
                new Schema(type: 'boolean'),
                new Schema(type: 'object'),
                new Schema(type: 'array', items: new Items()),
            ]
        )
    )]
    private array $additionalAttributes = [];

    public function hasAdditionalAttribute(string $key): bool
    {
        return \array_key_exists($key, $this->additionalAttributes);
    }

    public function getAdditionalAttributes(): array
    {
        return $this->additionalAttributes;
    }

    public function getAdditionalAttribute(string $key): mixed
    {
        return $this->additionalAttributes[$key] ?? null;
    }

    public function addAdditionalAttribute(string $key, mixed $value): void
    {
        $this->additionalAttributes[$key] = $value;
    }

    public function removeAdditionalAttribute(string $key): void
    {
        unset($this->additionalAttributes[$key]);
    }
}
