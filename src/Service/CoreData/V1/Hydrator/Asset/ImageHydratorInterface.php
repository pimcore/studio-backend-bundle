<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator\Asset;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Model\Asset\Image as CoreImage;

interface ImageHydratorInterface
{
    public function hydrate(CoreImage $item): Image;
}