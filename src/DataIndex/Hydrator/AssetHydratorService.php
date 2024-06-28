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
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Symfony\Contracts\Service\ServiceProviderInterface;
use function get_class;

final readonly class AssetHydratorService implements AssetHydratorServiceInterface
{
    public function __construct(
        private ServiceProviderInterface $assetHydratorLocator,
        private AssetHydratorInterface $assetHydrator
    ) {
    }

    /**
     * @param AssetSearchResultItem $item
     *
     * @return Asset
     */
    public function hydrate(AssetSearchResultItem $item): Asset
    {
        $class = get_class($item);
        if($this->assetHydratorLocator->has($class)) {
            return $this->assetHydratorLocator->get($class)->hydrate($item);
        }

        return $this->assetHydrator->hydrate($item);
    }
}
