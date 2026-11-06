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
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\ListingDatabaseStubTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use function str_contains;

/**
 * A configuration export has to carry every key of a store, enabled and disabled. One pass over the
 * key listing cannot do it: the listing's own `includeDisabled` narrows to the disabled rows rather
 * than adding them to the enabled ones, so the two states are two walks. There is no database in
 * the unit suite, so that is asserted where it is decided - on the conditions that reach the
 * connection.
 *
 * @internal
 */
final class KeyFullCoverageListingTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int STORE_ID = 1;

    private const string ENABLED_CONDITION = 'enabled = 1';

    private const string DISABLED_CONDITION = '(enabled is null or enabled = 0)';

    public function testTheTwoStatesAreTwoWalksWithTheirOwnConditions(): void
    {
        $repository = $this->createRepository([]);

        $repository->getChunkAfterIdentifier(self::STORE_ID, false, null, 0, 200);
        $repository->getChunkAfterIdentifier(self::STORE_ID, true, null, 0, 200);

        $this->assertCount(2, $this->executedQueries, 'Full coverage of a store needs both walks.');
        $this->assertTrue(
            str_contains($this->executedQueries[0], self::ENABLED_CONDITION),
            'The first walk has to narrow to the enabled rows.'
        );
        $this->assertTrue(
            str_contains($this->executedQueries[1], self::DISABLED_CONDITION),
            'The second walk has to narrow to the rows that are disabled or were never enabled.'
        );
    }

    /**
     * The column is nullable, and a row that was never enabled is not enabled - it exports as
     * `false` rather than as nothing.
     */
    public function testAKeyRowWhoseEnabledColumnIsNullReadsAsDisabled(): void
    {
        $repository = $this->createRepository([$this->createRow()]);

        $keys = $repository->getChunkAfterIdentifier(self::STORE_ID, true, null, 0, 200);

        $this->assertCount(1, $keys);
        $this->assertFalse($keys[0]->getEnabled());
    }

    /**
     * An apply writes while it walks, and writing `enabled` moves a key out of the state its walk
     * covers. The paging is therefore a cursor on the row id, which nothing the apply writes moves.
     */
    public function testTheApplyWalkPagesByTheRowIdRatherThanByAnOffset(): void
    {
        $repository = $this->createRepository([]);

        $repository->getChunkAfterId(self::STORE_ID, false, 7, 200);

        $query = $this->getExecutedQuery();

        $this->assertTrue(str_contains($query, '`id` > :afterId'), $query);
        $this->assertTrue(str_contains($query, 'ORDER BY `id` ASC'), $query);
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function createRepository(array $rows): KeyRepository
    {
        $this->stubDatabaseReturning($rows);

        return new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class),
            new InMemoryKeyLocalizationResolver(),
            $this->makeEmpty(ListingFilterInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class),
            $this->makeEmpty(Connection::class)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function createRow(): array
    {
        return [
            'id' => 51,
            'storeId' => self::STORE_ID,
            'name' => 'weight',
            'title' => 'Weight',
            'description' => null,
            'type' => 'input',
            'definition' => '{"fieldtype":"input"}',
            'enabled' => null,
            'identifier' => '01JBQ8X9Z0KEYKEYKEYKEYKEY',
            'creationDate' => 1734567890,
            'modificationDate' => 1734567891,
        ];
    }
}
