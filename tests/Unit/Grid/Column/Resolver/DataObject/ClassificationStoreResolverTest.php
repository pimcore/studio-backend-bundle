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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject\ClassificationStoreResolver;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ClassificationStoreResolverTest extends Unit
{
    private const string GROUP_IDENTIFIER = 'product-data';

    private const string KEY_IDENTIFIER = 'weight';

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

    /**
     * Identifiers win over a deliberately wrong integer pair in the same config.
     */
    public function testResolveForCoreElementResolvesFromIdentifiersIgnoringTheIntegerPair(): void
    {
        $result = $this->createResolverWithReference(new ClassificationStoreConfig(5, 6))->resolveForCoreElement(
            $this->createColumn([
                'groupId' => 99,
                'keyId' => 98,
                'groupIdentifier' => self::GROUP_IDENTIFIER,
                'keyIdentifier' => self::KEY_IDENTIFIER,
            ]),
            $this->createObject()
        );

        $this->assertSame('cs.weight', $result->getKey());
        $this->assertSame('42 kg', $result->getValue());
        $this->assertSame(5, $result->getAdditionalAttributes()['groupId']);
        $this->assertSame(6, $result->getAdditionalAttributes()['keyId']);
    }

    /**
     * A config without identifiers resolves from the pair exactly as before.
     */
    public function testResolveForCoreElementResolvesFromTheIntegerPairWithoutIdentifiers(): void
    {
        $result = $this->createResolverWithReference(null)->resolveForCoreElement(
            $this->createColumn(['groupId' => 5, 'keyId' => 6]),
            $this->createObject()
        );

        $this->assertSame('cs.weight', $result->getKey());
        $this->assertSame('42 kg', $result->getValue());
        $this->assertSame('input', $result->getFieldType());
        $this->assertSame(5, $result->getAdditionalAttributes()['groupId']);
        $this->assertSame(6, $result->getAdditionalAttributes()['keyId']);
    }

    /**
     * An identifier-only column is a configured reference: it fails when unresolvable, never renders empty.
     */
    public function testResolveForCoreElementThrowsForAnUnresolvableIdentifierPair(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolverWithUnresolvableReference()->resolveForCoreElement(
            $this->createColumn(['groupId' => 5, 'keyId' => 6, 'groupIdentifier' => 'gone', 'keyIdentifier' => 'gone']),
            $this->createObject()
        );
    }

    public function testResolveForExportThrowsForAnUnresolvableIdentifierPair(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolverWithUnresolvableReference()->resolveForExport(
            $this->createColumn(['groupIdentifier' => 'gone', 'keyIdentifier' => 'gone']),
            $this->createObject(),
            $this->makeEmpty(UserInterface::class)
        );
    }

    public function testIdentifierOnlyColumnIsNotTheEmptyBareColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createResolver()->resolveForCoreElement(
            $this->createColumn(['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER]),
            $this->makeEmpty(Concrete::class)
        );
    }

    public function testNullIdentifiersAloneStayTheEmptyBareColumn(): void
    {
        $result = $this->createResolver()->resolveForCoreElement(
            $this->createColumn(['groupIdentifier' => null, 'keyIdentifier' => null]),
            $this->makeEmpty(Concrete::class)
        );

        $this->assertNull($result->getValue());
    }

    private function createColumn(array $config): Column
    {
        return new Column(key: 'cs', locale: null, type: 'dataobject.classificationstore', group: null, config: $config);
    }

    private function createObject(): Concrete
    {
        return $this->makeEmpty(Concrete::class, ['getClassId' => 'EV']);
    }

    private function createResolverWithUnresolvableReference(): ClassificationStoreResolver
    {
        $reference = $this->makeEmpty(ColumnReferenceServiceInterface::class, [
            'resolve' => static function (): never {
                throw new InvalidArgumentException('does not exist');
            },
        ]);

        return $this->createResolver($reference);
    }

    private function createResolverWithReference(?ClassificationStoreConfig $resolved): ClassificationStoreResolver
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(6);
        $relation->setName('weight');
        $relation->setType('input');

        $base = $this->makeEmpty(CoreElementColumnResolverInterface::class, [
            'resolveForCoreElement' => new ColumnData(
                'cs',
                null,
                [5 => ['default' => [6 => '42 kg']]],
                'x',
                [5 => ['default' => [6 => new InheritanceData(1, false)]]]
            ),
        ]);

        return new ClassificationStoreResolver(
            $this->makeEmpty(GridServiceInterface::class, [
                'getColumnResolvers' => ['dataobject.adapter' => $base],
            ]),
            $this->makeEmpty(KeyRelationResolverInterface::class, ['getByGroup' => [6 => $relation]]),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $this->makeEmpty(ColumnReferenceServiceInterface::class, ['resolve' => $resolved]),
        );
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

    private function createResolver(?ColumnReferenceServiceInterface $reference = null): ClassificationStoreResolver
    {
        return new ClassificationStoreResolver(
            $this->makeEmpty(GridServiceInterface::class),
            $this->makeEmpty(KeyRelationResolverInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(ToolResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $reference ?? $this->makeEmpty(ColumnReferenceServiceInterface::class),
        );
    }
}
