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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Asset\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class GridSearch implements GridSearchInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private AssetSearchServiceInterface $assetSearchService,
    ) {
    }

    public function searchAssets(GridParameter $gridParameter): AssetSearchResult
    {
        $search = new AssetSearch();
        $search->setUser($this->securityService->getCurrentUser());

        return $this->assetSearchService->search($search);
    }
}
