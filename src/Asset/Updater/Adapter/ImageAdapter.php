<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Updater\Adapter;

use function array_key_exists;
use Pimcore\Bundle\StudioBackendBundle\Updater\Adapter\UpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class ImageAdapter implements UpdateAdapterInterface
{
    private const INDEX_KEY = 'image';

    public function update(ElementInterface $element, array $data): void
    {
        if (!$element instanceof Image) {
            return;
        }

        $this->checkFocalPoint($element, $data);

    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }

    private function checkFocalPoint(Image $image, array $data): void
    {
        if (!array_key_exists(self::INDEX_KEY, $data)) {
            return;
        }

        if (!array_key_exists('focalPoint', $data[self::INDEX_KEY])) {
            $image->removeCustomSetting('focalPointX');
            $image->removeCustomSetting('focalPointY');

            return;
        }

        $image->setCustomSetting('focalPointX', $data[self::INDEX_KEY]['focalPoint']['x']);
        $image->setCustomSetting('focalPointY', $data[self::INDEX_KEY]['focalPoint']['y']);
    }
}
