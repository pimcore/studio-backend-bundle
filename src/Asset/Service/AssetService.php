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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Note\Event\NoteEvent;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AssetService implements AssetServiceInterface
{
    public function __construct(
        private readonly AssetSearchServiceInterface $assetSearchService,
        private readonly FilterServiceProviderInterface $filterServiceProvider,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {

    }


    public function getAssets(ElementParameters $parameters): Collection
    {
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
}
