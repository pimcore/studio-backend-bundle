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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'AdminSettings',
    title: 'Admin Settings',
    required: ['branding', 'assets', 'writeable'],
    type: 'object'
)]
final readonly class Settings
{
    public function __construct(
        #[Property(ref: Branding::class, description: 'Branding configuration')]
        private Branding $branding,
        #[Property(ref: Assets::class, description: 'Assets configuration')]
        private Assets $assets,
        #[Property(description: 'Whether the settings are writeable', type: 'boolean', example: true)]
        private bool $writeable,
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

    public function getWriteable(): bool
    {
        return $this->writeable;
    }
}
