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

namespace Pimcore\Bundle\StudioApiBundle\Service\ModelData\V1\Hydrator;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Video;
use Pimcore\Model\Asset as ModelAsset;
use Symfony\Contracts\Service\ServiceProviderInterface;


final readonly class AssetHydratorService implements AssetHydratorServiceInterface
{
    public function __construct(
        private ServiceProviderInterface $assetHydratorLocator,
    ) {
    }

    /**
     * @param ModelAsset $item
     *
     * @return Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video
     */
    public function hydrate(ModelAsset $item): Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video
    {
        $class = get_class($item);
        if($this->assetHydratorLocator->has($class)) {
            return $this->assetHydratorLocator->get($class)->hydrate($item);
        }
        return new Asset($item->getId());
    }
}
