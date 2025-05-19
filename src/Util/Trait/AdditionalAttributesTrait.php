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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use function array_key_exists;

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
            ]
        )
    )]
    private array $additionalAttributes = [];

    public function hasAdditionalAttribute(string $key): bool
    {
        return array_key_exists($key, $this->additionalAttributes);
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
