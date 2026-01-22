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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Hydrator;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\Settings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\Assets;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\Branding;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\UpdateSettings;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;

/**
 * @internal
 */
final readonly class SettingsHydrator implements SettingsHydratorInterface
{
    use ElementProviderTrait;

    public function __construct(
        private ElementDataServiceInterface $elementDataService,
        private ServiceResolverInterface $serviceResolver
    ) {
    }

    public function hydrate(array $data): Settings
    {
        $branding = new Branding(
            $data['branding']['background_shade'] ?? '',
            $data['branding']['brand_color'] ?? '',
            $this->getRelatedElementData(
                $data['branding']['login_screen_custom_background_image'] ?? null
            ),
            $this->getRelatedElementData(
                $data['branding']['login_screen_custom_image'] ?? null
            )
        );

        $assets = new Assets(
            $data['assets']['hide_edit_image'] ?? false,
            $data['assets']['disable_tree_preview'] ?? false,
        );

        return new Settings(
            $branding,
            $assets,
            $data['isWriteable'] ?? false,
        );
    }

    public function dehydrate(UpdateSettings $adminSettings): array
    {
        $branding = $adminSettings->getBranding();
        $assets = $adminSettings->getAssets();

        return [
            'branding' => [
                'background_shade' => $branding->getBackGroundShade(),
                'brand_color' => $branding->getBrandColor(),
                'login_screen_custom_image' => $this->getRelatedElementDataAsArray(
                    $branding->getLoginScreenCustomImage()
                ),
                'login_screen_custom_background_image' => $this->getRelatedElementDataAsArray(
                    $branding->getLoginScreenCustomBackgroundImage()
                ),
            ],
            'assets' => [
                'hide_edit_image' => $assets->getHideEditImage(),
                'disable_tree_preview' => $assets->getDisableTreePreview(),
            ],
        ];
    }

    private function getRelatedElementDataAsArray(?RelatedElementData $relatedElementData): ?array
    {
        if ($relatedElementData === null) {
            return null;
        }

        return [
            'type' => $relatedElementData->getType(),
            'id' => $relatedElementData->getId(),
        ];
    }

    private function getRelatedElementData(?array $elementData): ?RelatedElementData
    {
        if ($elementData === null || !isset($elementData['type'], $elementData['id'])) {
            return null;
        }

        return $this->elementDataService->getRelatedElementData(
            $this->getElement(
                $this->serviceResolver,
                $elementData['type'],
                $elementData['id']
            )
        );
    }
}
