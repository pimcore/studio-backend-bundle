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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageThumbnailConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoThumbnailConfig;

/**
 * @internal
 */
final readonly class ThumbnailConfigHydrator implements ThumbnailConfigHydratorInterface
{
    public function hydrate(ImageThumbnailConfig|VideoThumbnailConfig $configuration, string $icon): ThumbnailConfig
    {
        return new ThumbnailConfig(
            id: $configuration->getName(),
            name: $configuration->getName(),
            icon: new ElementIcon(ElementIconTypes::NAME->value, $icon),
            writeable: $configuration->isWriteable()
        );
    }
}
