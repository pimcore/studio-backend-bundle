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

namespace Pimcore\Bundle\StudioApiBundle\OpenSearch\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;

final readonly class AssetQueryProvider implements AssetQueryProviderInterface
{
    public function __construct(private SearchProviderInterface $searchProvider)
    {
    }

    public function createAssetQuery(): QueryInterface
    {
        return new AssetQuery($this->searchProvider->createAssetSearch());
    }
}
