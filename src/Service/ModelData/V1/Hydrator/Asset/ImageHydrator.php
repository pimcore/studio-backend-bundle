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

namespace Pimcore\Bundle\StudioApiBundle\Service\ModelData\V1\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Image as ImageItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class ImageHydrator implements ImageHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        //private MetaDataHydratorInterface $metaDataHydrator,
        //private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(\Pimcore\Model\Asset\Image $item): Image
    {
        $image = new Image($item->getId());
        // parent element stuff
        $image->setParentId($item->getParentId());
        $image->setPath($item->getPath());
        $image->setUserOwner($item->getUserOwner());
        $image->setUserModification($item->getUserModification());
        $image->setLocked($item->getLocked());
        $image->setIsLocked($item->isLocked());
        $image->setCreationDate($item->getCreationDate());
        $image->setModificationDate($item->getModificationDate());

        $image->setUserModification($item->getUserModification());

        // asset specific stuff
        $image->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));

        $image->setType($item->getType());
        $image->setFilename($item->getKey());
        $image->setMimeType($item->getMimeType());
       // $image->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));

        $image->setFullPath($item->getFullPath());

        $image->setWidth($item->getWidth());
        $image->setHeight($item->getHeight());


        return $image;
    }
}
