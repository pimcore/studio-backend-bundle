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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\AdminSettings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\Assets;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\Branding;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\UpdateAdminSettings;

/**
 * @internal
 */
final readonly class AdminSettingsHydrator implements AdminSettingsHydratorInterface
{
    public function hydrate(array $data): AdminSettings
    {
        $branding = new Branding(
            $data['branding']['background_shade'] ?? '',
            $data['branding']['brand_color'] ?? '',
            $data['branding']['color_admin_interface_background'] ?? '',
            $data['branding']['login_screen_custom_background_image'] ?? '',
            $data['branding']['login_screen_custom_image'] ?? '',
        );

        $assets = new Assets(
            $data['assets']['hide_edit_image'] ?? false,
            $data['assets']['disable_tree_preview'] ?? false,
        );

        return new AdminSettings(
            $branding,
            $assets,
            $data['writeable'] ?? false,
        );
    }

    public function dehydrate(UpdateAdminSettings $adminSettings): array
    {
        $branding = $adminSettings->getBranding();
        $assets = $adminSettings->getAssets();

        return [
            'branding' => [
                'background_shade' => $branding->getBackGroundShade(),
                'brand_color' => $branding->getBrandColor(),
                'color_admin_interface_background' => $branding->getColorAdminInterfaceBackground(),
                'login_screen_custom_image' =>
                    str_replace(
                        '%',
                        '%%',
                        $branding->getLoginScreenCustomImage()
                    ),
                'login_screen_custom_background_image' =>
                    str_replace(
                        '%',
                        '%%',
                        $branding->getLoginScreenCustomBackgroundImage()
                    ),
            ],
            'assets' => [
                'hide_edit_image' => $assets->getHideEditImage(),
                'disable_tree_preview' => $assets->getDisableTreePreview(),
            ],
        ];
    }
}
