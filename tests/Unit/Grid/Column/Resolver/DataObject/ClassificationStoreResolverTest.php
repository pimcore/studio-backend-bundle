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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Column\Resolver\DataObject;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ClassificationStoreResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ClassificationStoreResolverTest extends Unit
{
    /**
     * A stale saved grid configuration can still contain the bare classification store field column.
     * It must load as an empty cell instead of failing the whole grid request.
     */
    public function testResolveForCoreElementReturnsEmptyDataForBareColumn(): void
    {
        $result = $this->createResolver()->resolveForCoreElement(
            $this->createBareColumn(),
            $this->makeEmpty(Concrete::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('cs', $result->getKey());
        $this->assertSame('dataobject.classificationstore', $result->getFieldType());
    }

    public function testResolveForExportReturnsEmptyDataForBareColumn(): void
    {
        $result = $this->createResolver()->resolveForExport(
            $this->createBareColumn(),
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertNull($result->getValue());
        $this->assertSame('cs', $result->getKey());
        $this->assertSame('dataobject.classificationstore', $result->getFieldType());
    }

    public function testValidateConfigStillThrowsForMissingKeyConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolver()->validateConfig([]);
    }

    /**
     * @dataProvider partialKeyConfigProvider
     */
    public function testResolveForCoreElementThrowsForPartialKeyConfig(array $config): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolver()->resolveForCoreElement(
            new Column(key: 'cs', locale: null, type: 'dataobject.classificationstore', group: null, config: $config),
            $this->makeEmpty(Concrete::class)
        );
    }

    public static function partialKeyConfigProvider(): array
    {
        return [
            'only groupId' => [['groupId' => 1]],
            'only keyId' => [['keyId' => 2]],
        ];
    }

    private function createBareColumn(): Column
    {
        return new Column(
            key: 'cs',
            locale: null,
            type: 'dataobject.classificationstore',
            group: null,
            config: [],
        );
    }

    private function createResolver(): ClassificationStoreResolver
    {
        return new ClassificationStoreResolver(
            $this->makeEmpty(GridServiceInterface::class),
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
        );
    }
}
