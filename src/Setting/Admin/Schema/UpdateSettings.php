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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'UpdateAdminSettings',
    title: 'Update Admin Settings',
    required: ['branding', 'assets'],
    type: 'object'
)]
final class UpdateSettings implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(ref: Branding::class, description: 'Branding configuration')]
        private readonly Branding $branding,
        #[Property(ref: Assets::class, description: 'Assets configuration')]
        private readonly Assets $assets,
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
}
