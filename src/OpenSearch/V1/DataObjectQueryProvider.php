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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;

final readonly class DataObjectQueryProvider implements DataObjectQueryProviderInterface
{
    public function __construct(
        private SearchProviderInterface $searchProvider,
        private ClassDefinitionResolverInterface $classDefinitionResolver
    ) {
    }

    public function createDataObjectQuery(): QueryInterface
    {
        /** @var DataObjectSearch $dataObjectSearch */
        $dataObjectSearch = $this->searchProvider->createDataObjectSearch();

        return new DataObjectQuery($dataObjectSearch, $this->classDefinitionResolver);
    }
}
