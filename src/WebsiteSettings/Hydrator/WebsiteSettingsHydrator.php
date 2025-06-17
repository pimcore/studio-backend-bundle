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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Schema\WebsiteSetting;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\WebsiteSetting as WebsiteSettingModel;
use function in_array;

/**
 * @internal
 */
final readonly class WebsiteSettingsHydrator implements WebsiteSettingsHydratorInterface
{
    public function hydrate(WebsiteSettingModel $settings): WebsiteSetting
    {
        $data = null;
        if ($settings->getType() !== null) {
            $data = $this->getSettingData($settings->getType(), $settings->getData());
        }

        return new WebsiteSetting(
            id: $settings->getId(),
            name: $settings->getName(),
            language: $settings->getLanguage(),
            type: $settings->getType(),
            data: $data,
            siteId: $settings->getSiteId(),
            creationDate: $settings->getCreationDate(),
            modificationDate: $settings->getModificationDate()
        );
    }

    private function getSettingData(string $type, mixed $data): string
    {
        if ($data instanceof ElementInterface && in_array($type, ElementTypes::ALLOWED_TYPES, true)) {
            return $data->getRealFullPath();
        }

        return (string)$data;
    }
}
