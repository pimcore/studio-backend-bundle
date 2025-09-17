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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Service\Factory;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\DataObjectSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\DocumentSearch;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DocumentQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQuery;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactory;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;

#[CoversClass(QueryFactory::class)]
#[UsesClass(AbstractApiException::class)]
#[UsesClass(DataObjectQuery::class)]
#[UsesClass(DocumentQuery::class)]
#[UsesClass(AssetQuery::class)]
/**
 * @internal
 */
final class QueryFactoryTest extends TestCase
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
     * @throws InvalidQueryTypeException
     * @throws Exception
     */
    public function testDocumentQueryType(): void
    {
        $queryFactory = $this->getQueryFactory();
        $query = $queryFactory->create('document');

        $this->assertInstanceOf(DocumentQuery::class, $query);
    }

    /**
     * @throws Exception
     */
    private function getQueryFactory(): QueryFactoryInterface
    {
        return new QueryFactory(
            $this->mockAssetAdapterInterface(),
            $this->mockDataObjectAdapterInterface(),
            $this->mockDocumentAdapterInterface(),
        );
    }

    /**
     * @throws Exception
     */
    private function mockAssetAdapterInterface(): AssetQueryProviderInterface
    {
        $mock = $this->createMock(AssetQueryProviderInterface::class);
        $mock->method('createAssetQuery')->willReturnCallback(function () {
            return new AssetQuery($this->createMock(AssetSearchInterface::class));
        });

        return $mock;
    }

    /**
     * @throws Exception
     */
    private function mockDataObjectAdapterInterface(): DataObjectQueryProviderInterface
    {
        $mock = $this->createMock(DataObjectQueryProviderInterface::class);
        $mock->method('createDataObjectQuery')->willReturnCallback(function () {
            return new DataObjectQuery(
                new DataObjectSearch(),
                $this->createMock(ClassDefinitionResolverInterface::class)
            );
        });

        return $mock;
    }

    /**
     * @throws Exception
     */
    private function mockDocumentAdapterInterface(): DocumentQueryProviderInterface
    {
        $mock = $this->createMock(DocumentQueryProviderInterface::class);
        $mock->method('createDocumentQuery')->willReturnCallback(function () {
            return new DocumentQuery(new DocumentSearch());
        });

        return $mock;
    }
}
