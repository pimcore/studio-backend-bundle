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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema;

use OpenApi\Attributes\AdditionalProperties;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use function array_key_exists;

#[Schema(
    schema: 'UpdateAdminSettings',
    title: 'Update Admin Settings',
    required: ['branding', 'assets'],
    type: 'object'
)]
final readonly class UpdateSettings
{
    public function __construct(
        #[Property(ref: Branding::class, description: 'Branding configuration')]
        private Branding $branding,
        #[Property(ref: Assets::class, description: 'Assets configuration')]
        private Assets $assets,
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
        private array $additionalAttributes = [],
    ) {
    }

    public function getBranding(): Branding
    {
        return $this->branding;
    }

    public function getAssets(): Assets
    {
        return $this->assets;
    }

    public function getAdditionalAttributes(): array
    {
        return $this->additionalAttributes;
    }

    public function hasAdditionalAttribute(string $key): bool
    {
        return array_key_exists($key, $this->additionalAttributes);
    }

    public function getAdditionalAttribute(string $key): mixed
    {
        return $this->additionalAttributes[$key] ?? null;
    }
}
