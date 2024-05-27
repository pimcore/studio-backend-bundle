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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Event\AssetEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Audio;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Document;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Folder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Unknown;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class AssetService implements AssetServiceInterface
{
    public function __construct(
        private AssetSearchServiceInterface $assetSearchService,
        private FilterServiceProviderInterface $filterServiceProvider,
        private EventDispatcherInterface $eventDispatcher
    )
    {
    }

    /**
     * @throws InvalidFilterServiceTypeException|SearchException|InvalidQueryTypeException|InvalidFilterTypeException
     */
    public function getAssets(ElementParameters $parameters): Collection
    {
        /** @var OpenSearchFilterInterface $filterService */
        $filterService = $this->filterServiceProvider->create(OpenSearchFilterInterface::SERVICE_TYPE);

        $assetQuery = $filterService->applyFilters(
            $parameters,
            ElementTypes::TYPE_ASSET
        );

        $result = $this->assetSearchService->searchAssets($assetQuery);

        $items = $result->getItems();

        foreach ($items as $item) {
            $this->eventDispatcher->dispatch(
                new AssetEvent($item),
                AssetEvent::EVENT_NAME
            );

        }

        return new Collection($result->getTotalItems(), $items);
    }

    /**
     * @throws SearchException|ElementNotFoundException
     */
    public function getAsset(int $id):  Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video
    {
        $asset = $this->assetSearchService->getAssetById($id);

        $this->eventDispatcher->dispatch(
            new AssetEvent($asset),
            AssetEvent::EVENT_NAME
        );

        return $asset;
    }
}
