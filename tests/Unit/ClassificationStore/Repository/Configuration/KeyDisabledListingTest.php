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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperService;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\PaginationFilter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\SortFilter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SortFilter as SortFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\ListingDatabaseStubTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing;
use function count;
use function str_contains;

/**
 * The key listing answers one of two views, the enabled keys or the disabled ones, and never both.
 * There is no database in the unit suite, so that is asserted on the conditions that reach the
 * connection.
 *
 * @internal
 */
final class KeyDisabledListingTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int STORE_ID = 1;

    private const string ENABLED_CONDITION = 'enabled = 1';

    private const string DISABLED_CONDITION = '(enabled is null or enabled = 0)';

    public function testTheEnabledViewIsTheDefaultAndIsUnchanged(): void
    {
        $absent = $this->load();
        $explicit = $this->load(false);

        foreach ([$absent, $explicit] as $query) {
            $this->assertTrue(str_contains($query, self::ENABLED_CONDITION), $query);
            $this->assertFalse(str_contains($query, self::DISABLED_CONDITION), $query);
            $this->assertTrue(str_contains($query, '`storeId` = '), $query);
        }
    }

    public function testTheDisabledViewEmitsTheDisabledConditionAndNotTheEnabledOne(): void
    {
        $query = $this->load(true);

        $this->assertTrue(str_contains($query, self::DISABLED_CONDITION), $query);
        $this->assertFalse(str_contains($query, self::ENABLED_CONDITION), $query);
    }

    public function testTheTwoViewsAreTwoStatementsAndNeverOne(): void
    {
        $enabled = $this->load(false);
        $this->executedQueries = [];
        $disabled = $this->load(true);

        $this->assertNotSame($enabled, $disabled);
        $this->assertSame(1, (int) str_contains($enabled, self::ENABLED_CONDITION)
            + (int) str_contains($enabled, self::DISABLED_CONDITION));
        $this->assertSame(1, (int) str_contains($disabled, self::ENABLED_CONDITION)
            + (int) str_contains($disabled, self::DISABLED_CONDITION));
    }

    public function testAKeyRowWhoseEnabledColumnIsNullIsInTheDisabledView(): void
    {
        $listing = $this->getListing(true, [$this->createRow()]);

        $keys = $listing->load();

        $this->assertCount(1, $keys);
        $this->assertFalse($keys[0]->getEnabled());
    }

    public function testFiltersSortingAndPagingApplyInsideTheDisabledView(): void
    {
        $filters = new FilterParameter(
            columnFilters: [
                ['key' => 'search', 'type' => 'search', 'filterValue' => 'wei'],
                ['key' => 'page', 'type' => 'page', 'filterValue' => 3],
                ['key' => 'pageSize', 'type' => 'page.size', 'filterValue' => 10],
            ],
            sortFilter: new SortFilterParameter('name', 'DESC'),
        );

        $listing = $this->getListing(true, [], $filters);
        $listing->load();
        $listing->getTotalCount();
        $query = $this->getExecutedQuery();

        $this->assertTrue(str_contains($query, self::DISABLED_CONDITION), $query);
        $this->assertTrue(str_contains($query, '`storeId` = '), $query);
        $this->assertTrue(str_contains($query, 'name LIKE'), $query);
        $this->assertTrue(str_contains($query, ' AND '), $query);
        $this->assertTrue(str_contains($query, 'ORDER BY `name` DESC'), $query);
        $this->assertTrue(str_contains($query, 'LIMIT 20,10'), $query);

        $count = $this->getExecutedCountQuery();

        $this->assertTrue(str_contains($count, self::DISABLED_CONDITION), $count);
        $this->assertFalse(str_contains($count, self::ENABLED_CONDITION), $count);
        $this->assertTrue(str_contains($count, '`storeId` = '), $count);
    }

    private function load(?bool $disabledOnly = null): string
    {
        $this->getListing($disabledOnly)->load();

        return $this->executedQueries[count($this->executedQueries) - 1];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function getListing(?bool $disabledOnly, array $rows = [], ?FilterParameter $filters = null): Listing
    {
        $this->stubDatabaseReturning($rows);

        $listingFilter = $this->makeEmpty(ListingFilterInterface::class, [
            'applyFilters' => static function (FilterParameter $parameters, mixed $listing): mixed {
                $listing = (new SortFilter())->apply($parameters, $listing);

                return (new PaginationFilter())->apply($parameters, $listing);
            },
        ]);
        $searchHelperService = new SearchHelperService(
            $this->makeEmpty(TranslationRepositoryInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class)
        );
        $repository = new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class),
            new InMemoryKeyLocalizationResolver(),
            $listingFilter,
            $searchHelperService,
            $this->makeEmpty(Connection::class)
        );
        $filters ??= new FilterParameter();

        return $disabledOnly === null
            ? $repository->getListing($filters, self::STORE_ID)
            : $repository->getListing($filters, self::STORE_ID, $disabledOnly);
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
