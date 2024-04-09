<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolver;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;

final readonly class DataObjectQueryProvider implements DataObjectQueryProviderInterface
{
    public function __construct(
        private SearchProviderInterface $searchProvider,
        private ClassDefinitionResolverInterface $classDefinitionResolver
    )
    {
    }
    public function createDataObjectQuery(): DataObjectQuery
    {
        return new DataObjectQuery($this->searchProvider->createDataObjectSearch(), $this->classDefinitionResolver);
    }
}