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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ImageGalleryAdapter implements SetterDataInterface
{
    use ElementProviderTrait;

    public function __construct(private HotspotImageAdapter $hotspotImageAdapter)
    {
    }

    public function getDataForSetter(Concrete $element, Data $fieldDefinition, string $key, array $data): ?ImageGallery
    {
        $galleryData = $data[$key] ?? null;
        if (!is_array($galleryData)) {
            return null;
        }

        $images = [];
        foreach ($galleryData as $item) {
            $images[] = $this->hotspotImageAdapter->getDataForSetter($element, $fieldDefinition, $key, [$key => $item]);
        }

        return new ImageGallery($images);
    }
}
