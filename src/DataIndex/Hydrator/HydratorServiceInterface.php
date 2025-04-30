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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;

interface HydratorServiceInterface
{
    public function hydrateAssets(
        AssetSearchResultItem $item
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video;

    public function hydrateDataObjects(DataObjectSearchResultItem $item): DataObject|DataObjectFolder;
}
