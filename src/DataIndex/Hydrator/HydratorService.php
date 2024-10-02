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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResultItem;
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
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document as IndexDocument;
use Symfony\Contracts\Service\ServiceProviderInterface;
use function get_class;

final readonly class HydratorService implements HydratorServiceInterface
{
    public function __construct(
        private AssetHydratorInterface $assetHydrator,
        private DataObjectHydratorInterface $dataObjectHydrator,
        private DocumentHydratorInterface $documentHydrator,
        private ServiceProviderInterface $assetHydratorLocator,
        private ServiceProviderInterface $dataObjectHydratorLocator,
    ) {
    }

    public function hydrateAssets(
        AssetSearchResultItem $item
    ): Asset|Archive|Audio|Document|AssetFolder|Image|Text|Unknown|Video {
        $class = get_class($item);
        if ($this->assetHydratorLocator->has($class)) {
            return $this->assetHydratorLocator->get($class)->hydrate($item);
        }

        return $this->assetHydrator->hydrate($item);
    }

    public function hydrateDataObjects(DataObjectSearchResultItem $item): DataObject|DataObjectFolder
    {
        $class = get_class($item);
        if ($this->dataObjectHydratorLocator->has($class)) {
            return $this->dataObjectHydratorLocator->get($class)->hydrate($item);
        }

        return $this->dataObjectHydrator->hydrate($item);
    }

    public function hydradeDocuments(DocumentSearchResultItem $item): IndexDocument
    {
        // TODO: Add Service Locator for different document types

        return $this->documentHydrator->hydrate($item);
    }
}
