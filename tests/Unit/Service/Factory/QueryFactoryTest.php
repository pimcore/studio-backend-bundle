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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\Factory;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQuery;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactory;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;

final class QueryFactoryTest extends Unit
{
    /**
     * @throws InvalidQueryTypeException
     * @throws Exception
     */
    public function testInvalidQueryType(): void
    {
        $queryFactory = $this->getQueryFactory();
        $this->expectExceptionMessage('Unknown query type: invalid');
        $queryFactory->create('invalid');
    }

    /**
     * @throws InvalidQueryTypeException
     * @throws Exception
     */
    public function testAssetQueryType(): void
    {
        $queryFactory = $this->getQueryFactory();
        $query = $queryFactory->create('asset');

        $this->assertInstanceOf(AssetQuery::class, $query);
    }

    /**
     * @throws InvalidQueryTypeException
     * @throws Exception
     */
    public function testDataObjectQueryType(): void
    {
        $queryFactory = $this->getQueryFactory();
        $query = $queryFactory->create('data-object');

        $this->assertInstanceOf(DataObjectQuery::class, $query);
    }

    /**
     * @throws Exception
     */
    private function getQueryFactory(): QueryFactoryInterface
    {
        return new QueryFactory(
            $this->mockAssetAdapterInterface(),
            $this->mockDataObjectAdapterInterface()
        );
    }

    /**
     * @throws Exception
     */
    private function mockAssetAdapterInterface(): AssetQueryProviderInterface
    {
        return $this->makeEmpty(AssetQueryProviderInterface::class, [
            'createAssetQuery' => function () {
                return new AssetQuery($this->makeEmpty(AssetSearchInterface::class));
            },
        ]);
    }

    /**
     * @throws Exception
     */
    private function mockDataObjectAdapterInterface(): DataObjectQueryProviderInterface
    {
        return $this->makeEmpty(DataObjectQueryProviderInterface::class, [
            'createDataObjectQuery' => function () {
                return new DataObjectQuery(
                    new DataObjectSearch(),
                    $this->makeEmpty(ClassDefinitionResolverInterface::class)
                );
            },
        ]);
    }
}
