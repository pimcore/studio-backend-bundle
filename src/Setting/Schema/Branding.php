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
    required: ['login_screen_invert_colors', 'color_login_screen', 'color_admin_interface', 'color_admin_interface_background', 'login_screen_custom_image'],
    type: 'object'
)]
final readonly class Branding
{
    public function __construct(
        #[Property(description: 'Invert colors on login screen', type: 'boolean', example: false)]
        private bool $login_screen_invert_colors,
        #[Property(description: 'Color for login screen', type: 'string', example: '#3C3F41')]
        private string $color_login_screen,
        #[Property(description: 'Color for admin interface', type: 'string', example: '#3C3F41')]
        private string $color_admin_interface,
        #[Property(description: 'Background color for admin interface', type: 'string', example: '#FFFFFF')]
        private string $color_admin_interface_background,
        #[Property(description: 'Custom image for login screen', type: 'string', example: '/bundles/pimcoreadmin/img/login-screen.jpg')]
        private string $login_screen_custom_image,
    ) {
    }

    public function getLoginScreenInvertColors(): bool
    {
        return $this->login_screen_invert_colors;
    }

    public function getColorLoginScreen(): string
    {
        return $this->color_login_screen;
    }

    public function getColorAdminInterface(): string
    {
        return $this->color_admin_interface;
    }

    public function getColorAdminInterfaceBackground(): string
    {
        return $this->color_admin_interface_background;
    }

    public function getLoginScreenCustomImage(): string
    {
        return $this->login_screen_custom_image;
    }
}
