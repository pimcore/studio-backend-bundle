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
    schema: 'AdminSettingsThumbnailPath',
    title: 'Admin Settings Thumbnail Path',
    required: ['customLogoSmall', 'customLogo', 'loginScreenCustomBackgroundImage'],
    type: 'object'
)]
final readonly class ThumbnailPaths
{
    public function __construct(
        #[Property(
            description: 'Path to custom logo thumbnail',
            type: 'string',
            example: '/Sample%20Content/Background%20Images/321/image_small.png'
        )]
        private ?string $customLogoSmall = null,
        #[Property(
            description: 'Path to custom logo thumbnail',
            type: 'string',
            example: '/Sample%20Content/Background%20Images/321/image-thumb.png'
        )]
        private ?string $customLogo = null,
        #[Property(
            description: 'Path to custom background image',
            type: 'string',
            example: '/Sample%20Content/Background%20Images/317/background.png'
        )]
        private ?string $loginScreenCustomBackgroundImage = null,
    ) {
    }

    public function getCustomLogoSmall(): ?string
    {
        return $this->customLogoSmall;
    }

    public function getCustomLogo(): ?string
    {
        return $this->customLogo;
    }

    public function getLoginScreenCustomBackgroundImage(): ?string
    {
        return $this->loginScreenCustomBackgroundImage;
    }
}
