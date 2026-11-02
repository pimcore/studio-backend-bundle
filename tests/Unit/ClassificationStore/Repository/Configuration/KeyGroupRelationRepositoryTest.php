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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;

/**
 * @internal
 */
final class KeyGroupRelationRepositoryTest extends Unit
{
    use RelationWriteDatabaseStubTrait;

    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

    private const int STORE_ID = 1;

    private const string STORED_SUB_GROUP = 'Dimensions';

    private const string STORED_DEFINITION_OVERRIDE_JSON = '{"defaultUnit":"kg"}';

    private const string STORED_METADATA_JSON = '{"source":"erp"}';

    private const array STORED_DEFINITION_OVERRIDE = ['defaultUnit' => 'kg'];

    private const array STORED_METADATA = ['source' => 'erp'];

    private const string COLUMN_SORTER = 'sorter';

    private const string COLUMN_MANDATORY = 'mandatory';

    private const string COLUMN_DEFINITION_OVERRIDE = 'definitionOverride';

    private const string COLUMN_SUB_GROUP = 'subGroup';

    private const string COLUMN_METADATA = 'metadata';

    public function testUpdateKeepsThePropertiesThePayloadDoesNotCarry(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $relation = $this->createRepository()->update(
            self::KEY_ID,
            self::GROUP_ID,
            new KeyGroupRelationUpdate(sorter: 5)
        );

        $row = $this->getWrittenRow();

        $this->assertSame(5, $row[self::COLUMN_SORTER]);
        $this->assertSame(1, $row[self::COLUMN_MANDATORY]);
        $this->assertSame(self::STORED_DEFINITION_OVERRIDE_JSON, $row[self::COLUMN_DEFINITION_OVERRIDE]);
        $this->assertSame(self::STORED_SUB_GROUP, $row[self::COLUMN_SUB_GROUP]);
        $this->assertSame(self::STORED_METADATA_JSON, $row[self::COLUMN_METADATA]);
        $this->assertSame(self::STORED_DEFINITION_OVERRIDE, $relation->getDefinitionOverride());
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
        $this->assertSame(self::STORED_METADATA, $relation->getMetadata());
    }

    /**
     * @dataProvider nullableColumnProvider
     *
     * @param array<string, mixed> $payload
     */
    public function testUpdateClearsANullableColumnThePayloadSetsToNull(string $column, array $payload): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $this->createRepository()->update(self::KEY_ID, self::GROUP_ID, new KeyGroupRelationUpdate(...$payload));

        $this->assertNull($this->getWrittenRow()[$column]);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function nullableColumnProvider(): array
    {
        return [
            'definition override' => [
                self::COLUMN_DEFINITION_OVERRIDE,
                [self::COLUMN_DEFINITION_OVERRIDE => null],
            ],
            'sub group' => [
                self::COLUMN_SUB_GROUP,
                [self::COLUMN_SUB_GROUP => null],
            ],
            'metadata' => [
                self::COLUMN_METADATA,
                [self::COLUMN_METADATA => null],
            ],
        ];
    }

    public function testUpdateStoresTheSubGroupIdentifierTheStoreResolvedTo(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, self::STORED_SUB_GROUP]);

        $relation = $this->createRepository()->update(
            self::KEY_ID,
            self::GROUP_ID,
            new KeyGroupRelationUpdate(subGroup: 'dimensions')
        );

        $this->assertSame(self::STORED_SUB_GROUP, $this->getWrittenRow()[self::COLUMN_SUB_GROUP]);
        $this->assertSame(self::STORED_SUB_GROUP, $relation->getSubGroup());
    }

    public function testUpdateTurnsAnUnknownSubGroupIntoAnUnprocessableContent(): void
    {
        $this->stubDatabase([$this->createRow()], [self::STORE_ID, false]);

        try {
            $this->createRepository()->update(
                self::KEY_ID,
                self::GROUP_ID,
                new KeyGroupRelationUpdate(subGroup: 'nope')
            );
            $this->fail('Expected the unknown sub group to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'The sub group "nope" does not exist in the store of group 7.',
                $e->getMessage()
            );
        }

        $this->assertNothingWasWritten();
    }

    public function testUpdateTurnsARefusedDefinitionOverrideIntoAnUnprocessableContent(): void
    {
        $this->stubDatabase([$this->createRow()]);

        try {
            $this->createRepository()->update(
                self::KEY_ID,
                self::GROUP_ID,
                new KeyGroupRelationUpdate(definitionOverride: ['fieldtype' => 'select'])
            );
            $this->fail('Expected the refused definition override to be refused.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('"fieldtype" cannot be overridden per key assignment.', $e->getMessage());
        }

        $this->assertNothingWasWritten();
    }

    public function testUpdateReportsAnUnknownPairAsNotFoundWithoutWriting(): void
    {
        $this->stubDatabase([]);

        try {
            $this->createRepository()->update(self::KEY_ID, self::GROUP_ID, new KeyGroupRelationUpdate(sorter: 5));
            $this->fail('Expected the unknown pair to be refused.');
        } catch (NotFoundException) {
            $this->assertNothingWasWritten();
        }
    }

    public function testCreateOrUpdateLeavesThePerAssignmentColumnsUninitialized(): void
    {
        $this->stubDatabase([]);

        $relation = $this->createRepository()->createOrUpdate(self::KEY_ID, self::GROUP_ID, 2, true);

        $this->assertFalse($relation->isDefinitionOverrideInitialized());
        $this->assertFalse($relation->isSubGroupInitialized());
        $this->assertFalse($relation->isMetadataInitialized());

        $row = $this->getWrittenRow();

        $this->assertArrayNotHasKey(self::COLUMN_DEFINITION_OVERRIDE, $row);
        $this->assertArrayNotHasKey(self::COLUMN_SUB_GROUP, $row);
        $this->assertArrayNotHasKey(self::COLUMN_METADATA, $row);
        $this->assertSame(2, $row[self::COLUMN_SORTER]);
        $this->assertSame(1, $row[self::COLUMN_MANDATORY]);
    }

    private function createRepository(): KeyGroupRelationRepository
    {
        return new KeyGroupRelationRepository($this->makeEmpty(ListingFilterInterface::class));
    }

    /**
     * The relation row joined with the key row, the shape the relation listing loads.
     *
     * @return array<string, mixed>
     */
    private function createRow(): array
    {
        return [
            'groupId' => self::GROUP_ID,
            'keyId' => self::KEY_ID,
            self::COLUMN_SORTER => 3,
            self::COLUMN_MANDATORY => 1,
            self::COLUMN_METADATA => self::STORED_METADATA_JSON,
            self::COLUMN_DEFINITION_OVERRIDE => self::STORED_DEFINITION_OVERRIDE_JSON,
            self::COLUMN_SUB_GROUP => self::STORED_SUB_GROUP,
            'name' => 'weight',
            'description' => '',
            'type' => 'input',
            'definition' => '{"fieldtype":"input"}',
            'enabled' => 1,
        ];
    }
}
