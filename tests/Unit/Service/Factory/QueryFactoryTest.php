<?php

namespace Pimcore\Bundle\StudioApiBundle\Tests\Unit\Service\Factory;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\StudioApiBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioApiBundle\Factory\QueryFactory;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQuery;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQueryProviderInterface;

final class QueryFactoryTest extends Unit
{
    /**
     * @throws InvalidQueryTypeException
     * @throws Exception
     */
    public function testInvalidQueryType(): void
    {
        $queryFactory = new QueryFactory($this->mockAssetAdapterInterface());
        $this->expectExceptionMessage('Unknown query type: invalid');
        $queryFactory->create('invalid');
    }

    /**
     * @throws InvalidQueryTypeException
     * @throws Exception
     */
    public function testAssetQueryType(): void
    {
        $queryFactory = new QueryFactory($this->mockAssetAdapterInterface());
        $query = $queryFactory->create('asset');

        $this->assertInstanceOf(AssetQuery::class, $query);
    }

    /**
     * @throws Exception
     */
    private function mockAssetAdapterInterface(): AssetQueryProviderInterface
    {
        return $this->makeEmpty(AssetQueryProviderInterface::class, [
            'createAssetQuery' => function () {
                return new AssetQuery($this->makeEmpty(SearchInterface::class));
            }
        ]);
    }
}