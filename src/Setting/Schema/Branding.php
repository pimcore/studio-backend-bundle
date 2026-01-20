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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'Branding',
    title: 'Branding',
    required: [
        'loginScreenInvertColors',
        'colorLoginScreen',
        'colorAdminInterface',
        'colorAdminInterfaceBackground',
        'loginScreenCustomImage',
        'loginScreenCustomBackgroundImage',
    ],
    type: 'object'
)]
final readonly class Branding
{
    public function __construct(
        #[Property(description: 'Background shade', type: 'string', example: '#CCCCCC')]
        private string $backgroundShade,
        #[Property(description: 'Brand color', type: 'string', example: '#FFCC00')]
        private string $brandColor,
        #[Property(description: 'Background color for admin interface', type: 'string', example: '#FFFFFF')]
        private string $colorAdminInterfaceBackground,
        #[Property(
            description: 'Custom image for login screen',
            type: 'string',
            example: '/Sample Content/Logo/login_background.png')
        ]
        private string $loginScreenCustomBackgroundImage,
        #[Property(
            description: 'Custom image for login screen',
            type: 'string',
            example: '/Sample Content/Logo/login_logo.png')
        ]
        private string $loginScreenCustomImage,
    ) {

    }

    public function getColorAdminInterfaceBackground(): string
    {
        return $this->colorAdminInterfaceBackground;
    }

    public function getLoginScreenCustomBackgroundImage(): string
    {
        return $this->loginScreenCustomBackgroundImage;
    }

    public function getLoginScreenCustomImage(): string
    {
        return $this->loginScreenCustomImage;
    }

    public function getBackGroundShade(): string
    {
        return $this->backgroundShade;
    }

    public function getBrandColor(): string
    {
        return $this->brandColor;
    }
}
