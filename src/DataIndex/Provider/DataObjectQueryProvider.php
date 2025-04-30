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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;

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
