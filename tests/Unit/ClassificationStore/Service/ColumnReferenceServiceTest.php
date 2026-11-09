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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceLocator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use function array_map;
use function array_search;
use function range;

/**
 * @internal
 */
final class ColumnReferenceServiceTest extends Unit
{
    private const string CLASS_ID = 'EV';

    private const string GROUP_IDENTIFIER = 'product-data';

    private const string KEY_IDENTIFIER = 'weight';

    private const int STORE_ID = 1;

    private const int OTHER_STORE_ID = 2;

    /** @var array<int, array<string, int>> group id by identifier, by store */
    private array $groups = [];

    /** @var array<int, array<string, int>> key id by identifier, by store */
    private array $keys = [];

    private int $statements = 0;

    protected function _before(): void
    {
        $this->groups = [self::STORE_ID => [self::GROUP_IDENTIFIER => 5], self::OTHER_STORE_ID => [self::GROUP_IDENTIFIER => 50]];
        $this->keys = [self::STORE_ID => [self::KEY_IDENTIFIER => 6], self::OTHER_STORE_ID => [self::KEY_IDENTIFIER => 60]];
        $this->statements = 0;
    }

    public function testResolveReturnsNullWithoutBothIdentifiers(): void
    {
        $service = $this->createService();

        $this->assertNull($service->resolve(self::CLASS_ID, 'cs', ['groupId' => 5, 'keyId' => 6]));
        $this->assertNull($service->resolve(self::CLASS_ID, 'cs', ['groupIdentifier' => self::GROUP_IDENTIFIER]));
        $this->assertNull($service->resolve(self::CLASS_ID, 'cs', ['groupIdentifier' => '', 'keyIdentifier' => 'a']));
    }

    /**
     * Two stores hold the same identifier strings; the field's own store decides.
     */
    public function testResolveUsesTheStoreOfTheNamedFieldOnly(): void
    {
        $service = $this->createService();
        $config = ['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER];

        $first = $service->resolve(self::CLASS_ID, 'cs', $config);
        $second = $service->resolve(self::CLASS_ID, 'otherCs', $config);

        $this->assertSame([5, 6], [$first->getGroupId(), $first->getKeyId()]);
        $this->assertSame([50, 60], [$second->getGroupId(), $second->getKeyId()]);
    }

    public function testResolveIgnoresTheIntegerPairOfTheSameConfig(): void
    {
        $resolved = $this->createService()->resolve(
            self::CLASS_ID,
            'cs',
            ['groupId' => 99, 'keyId' => 98, 'groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER]
        );

        $this->assertSame([5, 6], [$resolved->getGroupId(), $resolved->getKeyId()]);
    }

    public function testResolveThrowsForAnUnresolvableIdentifierInsteadOfFallingBack(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createService()->resolve(
            self::CLASS_ID,
            'cs',
            ['groupId' => 5, 'keyId' => 6, 'groupIdentifier' => 'gone', 'keyIdentifier' => self::KEY_IDENTIFIER]
        );
    }

    public function testResolveThrowsForAFieldThatIsNoClassificationStore(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createService()->resolve(
            self::CLASS_ID,
            'name',
            ['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER]
        );
    }

    public function testAddIdentifiersWritesThemNextToThePair(): void
    {
        $columns = $this->createService()->addIdentifiers(self::CLASS_ID, [$this->csColumn('cs', 5, 6)]);

        $this->assertSame(
            ['groupId' => 5, 'keyId' => 6, 'groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER],
            $columns[0]['config']
        );
    }

    /**
     * An entity without an identifier is not refused: the pair stays and the member is absent.
     */
    public function testAddIdentifiersLeavesTheMemberOutForAnEntityWithoutIdentifier(): void
    {
        $this->groups[self::STORE_ID] = [];

        $columns = $this->createService()->addIdentifiers(self::CLASS_ID, [$this->csColumn('cs', 5, 6)]);

        $this->assertSame(
            ['groupId' => 5, 'keyId' => 6, 'keyIdentifier' => self::KEY_IDENTIFIER],
            $columns[0]['config']
        );
    }

    /**
     * A copied pair that is wrong here must not overwrite identifiers that resolve.
     */
    public function testAddIdentifiersKeepsResolvingIdentifiersAndCorrectsThePair(): void
    {
        $column = $this->csColumn('cs', 99, 98);
        $column['config']['groupIdentifier'] = self::GROUP_IDENTIFIER;
        $column['config']['keyIdentifier'] = self::KEY_IDENTIFIER;

        $columns = $this->createService()->addIdentifiers(self::CLASS_ID, [$column]);

        $this->assertSame(5, $columns[0]['config']['groupId']);
        $this->assertSame(self::GROUP_IDENTIFIER, $columns[0]['config']['groupIdentifier']);
    }

    public function testAddIdentifiersCoversTheSourceFieldOfAnAdvancedColumn(): void
    {
        $advanced = [
            'key' => 'adv',
            'type' => 'dataobject.advanced',
            'config' => ['advancedColumns' => [
                ['key' => 'simpleField', 'config' => ['field' => 'cs', 'groupId' => 5, 'keyId' => 6]],
                ['key' => 'staticText', 'config' => ['text' => 'x']],
            ]],
        ];

        $columns = $this->createService()->addIdentifiers(self::CLASS_ID, [$advanced]);

        $source = $columns[0]['config']['advancedColumns'][0]['config'];
        $this->assertSame(self::GROUP_IDENTIFIER, $source['groupIdentifier']);
        $this->assertSame(self::KEY_IDENTIFIER, $source['keyIdentifier']);
        $this->assertSame(['text' => 'x'], $columns[0]['config']['advancedColumns'][1]['config']);
    }

    /**
     * The related class (Maker) keeps its field in the other store, so the owner's store would be the wrong one.
     */
    public function testAddIdentifiersCoversTheSourceFieldOfARelationField(): void
    {
        $advanced = [
            'key' => 'adv',
            'type' => 'dataobject.advanced',
            'config' => ['advancedColumns' => [
                ['key' => 'relationField', 'config' => [
                    'relation' => 'manufacturer',
                    'field' => 'cs',
                    'groupId' => 50,
                    'keyId' => 60,
                ]],
            ]],
        ];

        $columns = $this->createService()->addIdentifiers(self::CLASS_ID, [$advanced]);

        $source = $columns[0]['config']['advancedColumns'][0]['config'];
        $this->assertSame(self::GROUP_IDENTIFIER, $source['groupIdentifier']);
        $this->assertSame(self::KEY_IDENTIFIER, $source['keyIdentifier']);
    }

    public function testAddFilterIdentifiersStampsOnlyClassificationStoreColumnFilters(): void
    {
        $filter = ['page' => 1, 'columnFilters' => [
            ['key' => 'name', 'type' => 'input', 'filterValue' => 'x'],
            ['key' => 'cs', 'type' => 'classificationstore.string', 'filterValue' => [
                'groupId' => 5,
                'keyId' => 6,
                'value' => 'needle',
            ]],
        ]];

        $stamped = $this->createService()->addFilterIdentifiers(self::CLASS_ID, $filter);

        $this->assertSame($filter['columnFilters'][0], $stamped['columnFilters'][0]);
        $this->assertSame([
            'groupId' => 5,
            'keyId' => 6,
            'value' => 'needle',
            'groupIdentifier' => self::GROUP_IDENTIFIER,
            'keyIdentifier' => self::KEY_IDENTIFIER,
        ], $stamped['columnFilters'][1]['filterValue']);
    }

    public function testDeriveFilterIdsReplacesAStalePairAndLeavesAFilterWithoutColumnFiltersAsStored(): void
    {
        $filter = ['columnFilters' => [['key' => 'cs', 'type' => 'classificationstore.string', 'filterValue' => [
            'groupId' => 99,
            'keyId' => 98,
            'groupIdentifier' => self::GROUP_IDENTIFIER,
            'keyIdentifier' => self::KEY_IDENTIFIER,
            'value' => 'needle',
        ]]]];

        $derived = $this->createService()->deriveFilterIds(self::CLASS_ID, $filter);

        $this->assertSame([5, 6], [
            $derived['columnFilters'][0]['filterValue']['groupId'],
            $derived['columnFilters'][0]['filterValue']['keyId'],
        ]);
        $this->assertSame(['page' => 1], $this->createService()->deriveFilterIds(self::CLASS_ID, ['page' => 1]));
    }

    public function testDeriveIdsReplacesAStalePairWithTheLocalIds(): void
    {
        $column = $this->csColumn('cs', 99, 98);
        $column['config']['groupIdentifier'] = self::GROUP_IDENTIFIER;
        $column['config']['keyIdentifier'] = self::KEY_IDENTIFIER;

        $columns = $this->createService()->deriveIds(self::CLASS_ID, [$column]);

        $this->assertSame([5, 6], [$columns[0]['config']['groupId'], $columns[0]['config']['keyId']]);
    }

    /**
     * A configuration saved before identifiers existed is served exactly as stored.
     */
    public function testDeriveIdsLeavesAnIdentifierlessColumnAndAnUnresolvableOneAsStored(): void
    {
        $stale = $this->csColumn('cs', 99, 98);
        $stale['config']['groupIdentifier'] = 'gone';
        $stale['config']['keyIdentifier'] = self::KEY_IDENTIFIER;
        $legacy = $this->csColumn('cs', 7, 8);

        $columns = $this->createService()->deriveIds(self::CLASS_ID, [$stale, $legacy]);

        $this->assertSame([$stale, $legacy], $columns);
    }

    /**
     * The statements of identifier resolution do not grow with the number of columns.
     */
    public function testStatementCountDoesNotGrowWithTheNumberOfColumns(): void
    {
        $counts = [];
        foreach ([1, 50] as $number) {
            $this->statements = 0;
            $columns = array_map(fn () => $this->csColumn('cs', 5, 6), range(1, $number));
            $columns = array_map(static function (array $column): array {
                $column['config']['groupIdentifier'] = self::GROUP_IDENTIFIER;
                $column['config']['keyIdentifier'] = self::KEY_IDENTIFIER;

                return $column;
            }, $columns);

            $service = $this->createService();
            $service->deriveIds(self::CLASS_ID, $columns);
            $service->addIdentifiers(self::CLASS_ID, array_map($this->withoutIdentifiers(...), $columns));
            $counts[$number] = $this->statements;
        }

        $this->assertSame($counts[1], $counts[50]);
    }

    public function testPrimeSpendsOneStatementPerEntityTypeAndTheResolvesAfterItNone(): void
    {
        $columns = array_map(fn () => new Column(
            key: 'cs',
            locale: null,
            type: 'dataobject.classificationstore',
            group: null,
            config: ['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER],
        ), range(1, 50));
        $collection = new ColumnCollection($columns);
        $service = $this->createService();

        $service->prime(self::CLASS_ID, $collection);
        $primed = $this->statements;
        foreach ($columns as $column) {
            $service->resolve(self::CLASS_ID, 'cs', $column->getConfig());
        }
        $service->prime(self::CLASS_ID, $collection);

        $this->assertSame(2, $primed);
        $this->assertSame(2, $this->statements);
    }

    /**
     * The filters of a class-scoped search are primed as one set, so a reference two filters name costs one
     * statement per entity type and no filter spends a lookup of its own.
     */
    public function testPrimeFiltersSpendsOneStatementPerEntityTypeAndTheResolvesAfterItNone(): void
    {
        $columnFilters = array_map(static fn () => [
            'key' => 'cs',
            'type' => 'classificationstore.string',
            'filterValue' => [
                'groupIdentifier' => self::GROUP_IDENTIFIER,
                'keyIdentifier' => self::KEY_IDENTIFIER,
                'value' => 'needle',
            ],
        ], range(1, 20));
        $service = $this->createService();

        $service->primeFilters(self::CLASS_ID, $columnFilters);
        $primed = $this->statements;
        foreach ($columnFilters as $columnFilter) {
            $service->resolve(self::CLASS_ID, 'cs', $columnFilter['filterValue']);
        }

        $this->assertSame(2, $primed);
        $this->assertSame(2, $this->statements);
    }

    /**
     * Nothing primes the filters of the advanced column preview or of a saved search served without a class,
     * so a resolve that has to look a reference up itself memoises what it read.
     */
    public function testAnUnprimedResolveMemoisesWhatItLooksUp(): void
    {
        $config = ['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER];
        $service = $this->createService();

        $service->resolve(self::CLASS_ID, 'cs', $config);
        $afterFirst = $this->statements;
        $service->resolve(self::CLASS_ID, 'cs', $config);
        $service->resolve(self::CLASS_ID, 'cs', $config);

        $this->assertSame(2, $afterFirst);
        $this->assertSame(2, $this->statements);
    }

    /**
     * The memo is per store: the same identifier strings in another store are read again.
     */
    public function testTheMemoOfOneStoreDoesNotAnswerForAnother(): void
    {
        $config = ['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER];
        $service = $this->createService();

        $service->resolve(self::CLASS_ID, 'cs', $config);
        $other = $service->resolve(self::CLASS_ID, 'otherCs', $config);

        $this->assertSame([50, 60], [$other->getGroupId(), $other->getKeyId()]);
        $this->assertSame(4, $this->statements);
    }

    public function testResetDropsTheMemoOfAnUnprimedResolve(): void
    {
        $config = ['groupIdentifier' => self::GROUP_IDENTIFIER, 'keyIdentifier' => self::KEY_IDENTIFIER];
        $service = $this->createService();

        $service->resolve(self::CLASS_ID, 'cs', $config);
        $service->reset();
        $service->resolve(self::CLASS_ID, 'cs', $config);

        $this->assertSame(4, $this->statements);
    }

    private function withoutIdentifiers(array $column): array
    {
        unset($column['config']['groupIdentifier'], $column['config']['keyIdentifier']);

        return $column;
    }

    private function csColumn(string $key, int $groupId, int $keyId): array
    {
        return [
            'key' => $key,
            'type' => 'dataobject.classificationstore',
            'config' => ['groupId' => $groupId, 'keyId' => $keyId],
        ];
    }

    private function createService(): ColumnReferenceService
    {
        $input = new Input();
        $input->setName('name');
        $classDefinition = new ClassDefinition();
        $classDefinition->setFieldDefinitions([
            'cs' => $this->createField('cs', self::STORE_ID),
            'otherCs' => $this->createField('otherCs', self::OTHER_STORE_ID),
            'name' => $input,
        ]);

        $relation = new ManyToOneRelation();
        $relation->setName('manufacturer');
        $relation->setClasses([['classes' => 'Maker']]);
        $classDefinition->setFieldDefinitions([...$classDefinition->getFieldDefinitions(), 'manufacturer' => $relation]);
        $maker = new ClassDefinition();
        $maker->setFieldDefinitions(['cs' => $this->createField('cs', self::OTHER_STORE_ID)]);

        return new ColumnReferenceService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class, [
                'getById' => $classDefinition,
                'getByName' => $maker,
            ]),
            $this->makeEmpty(GroupRepositoryInterface::class, [
                'getIdsByIdentifiers' => fn (int $storeId, array $identifiers) => $this->idsOf($this->groups, $storeId, $identifiers),
                'getIdentifiersByIds' => fn (int $storeId, array $ids) => $this->identifiersOf($this->groups, $storeId, $ids),
            ]),
            $this->makeEmpty(KeyRepositoryInterface::class, [
                'getIdsByIdentifiers' => fn (int $storeId, array $identifiers) => $this->idsOf($this->keys, $storeId, $identifiers),
                'getIdentifiersByIds' => fn (int $storeId, array $ids) => $this->identifiersOf($this->keys, $storeId, $ids),
            ]),
            new ColumnReferenceLocator(),
        );
    }

    private function createField(string $name, int $storeId): Classificationstore
    {
        $field = new Classificationstore();
        $field->setName($name);
        $field->setStoreId($storeId);

        return $field;
    }

    private function idsOf(array $rows, int $storeId, array $identifiers): array
    {
        $this->statements++;
        $found = [];
        foreach ($identifiers as $identifier) {
            if (isset($rows[$storeId][$identifier])) {
                $found[$identifier] = $rows[$storeId][$identifier];
            }
        }

        return $found;
    }

    private function identifiersOf(array $rows, int $storeId, array $ids): array
    {
        $this->statements++;
        $found = [];
        foreach ($ids as $id) {
            $identifier = array_search($id, $rows[$storeId] ?? [], true);
            if ($identifier !== false) {
                $found[$id] = (string) $identifier;
            }
        }

        return $found;
    }
}
