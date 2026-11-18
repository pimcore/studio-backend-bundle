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
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperService;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\EqualsFilter;
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
use function preg_match;
use function str_contains;

/**
 * The key listing can be asked for the keys a group does not already hold, which is what the add-key
 * picker lists. The narrowing is the server's, so the page and the total it reports are both counted
 * over the keys that are actually available; a client-side filter over an unnarrowed page cannot do
 * either.
 *
 * There is no database in the unit suite, so that is asserted on the condition that reaches the
 * connection and on the value bound to it.
 *
 * @internal
 */
final class KeyGroupExclusionListingTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int STORE_ID = 1;

    private const int GROUP_ID = 12;

    private const string EXCLUSION_SUBQUERY = 'NOT EXISTS (SELECT 1 FROM `classificationstore_relations`';

    private const string CORRELATION = '`classificationstore_relations`.`keyId` = `classificationstore_keys`.`id`';

    private const string EXCLUSION_PARAMETER = ':scopeExcludedGroupId';

    private const int HIJACKED_GROUP_ID = 99;

    // the request field's own name, which is what the exclusion was first bound under
    private const string HIJACK_KEY = 'excludeKeysOfGroupId';

    private const string EXCLUSION_PLACEHOLDER_PATTERN = '/`groupId` = :(\w+)\)/';

    public function testTheListingCarriesNoExclusionWhenTheGroupIsNotNamed(): void
    {
        $absent = $this->load();
        $explicitNull = $this->load(null, true);

        foreach ([$absent, $explicitNull] as $query) {
            $this->assertFalse(str_contains($query, self::EXCLUSION_SUBQUERY), $query);
            $this->assertFalse(str_contains($query, self::EXCLUSION_PARAMETER), $query);
            // the endpoint's own scope and view are untouched by the new parameter being absent
            $this->assertTrue(str_contains($query, '`storeId` = '), $query);
            $this->assertTrue(str_contains($query, 'enabled = 1'), $query);
        }
    }

    public function testANamedGroupNarrowsTheListingToTheKeysItDoesNotHold(): void
    {
        $query = $this->load(self::GROUP_ID);

        $this->assertTrue(str_contains($query, self::EXCLUSION_SUBQUERY), $query);
        $this->assertTrue(str_contains($query, self::CORRELATION), $query);
        $this->assertTrue(str_contains($query, '`classificationstore_relations`.`groupId` = '
            . self::EXCLUSION_PARAMETER), $query);
        // the store scope is narrowed further, never replaced
        $this->assertTrue(str_contains($query, '`storeId` = '), $query);
    }

    public function testTheGroupIsBoundAsAParameterAndNotWrittenIntoTheStatement(): void
    {
        $listing = $this->getListing(self::GROUP_ID);
        $listing->load();

        $variables = $listing->getConditionVariables();

        $this->assertArrayHasKey('scopeExcludedGroupId', $variables);
        $this->assertSame(self::GROUP_ID, $variables['scopeExcludedGroupId']);
        $this->assertFalse(str_contains($this->getExecutedQuery(), (string) self::GROUP_ID));
    }

    /**
     * Pimcore merges every binding into one named set and the column filters are applied last, so a
     * filter keyed like the exclusion's parameter would rebind it to the client's group.
     */
    public function testAColumnFilterNamedAfterTheRequestFieldCannotRebindTheExclusion(): void
    {
        $listing = $this->getListingWithColumnFilters([
            ['key' => self::HIJACK_KEY, 'type' => 'equals', 'filterValue' => self::HIJACKED_GROUP_ID],
        ]);

        $condition = $listing->getCondition();
        $variables = $listing->getConditionVariables();

        $this->assertSame(1, preg_match(self::EXCLUSION_PLACEHOLDER_PATTERN, $condition, $matches), $condition);
        $exclusionParameter = $matches[1];

        $this->assertSame(
            self::GROUP_ID,
            $variables[$exclusionParameter] ?? null,
            'The group the route excludes has to survive the filter.'
        );
        $this->assertNotSame(
            self::HIJACK_KEY,
            $exclusionParameter,
            'The exclusion has to bind under an internal name, not the request field\'s.'
        );
        $this->assertSame(self::HIJACKED_GROUP_ID, $variables[self::HIJACK_KEY] ?? null);
    }

    public function testTheTotalIsCountedOverTheNarrowedSet(): void
    {
        $listing = $this->getListing(self::GROUP_ID);
        $listing->getTotalCount();

        $count = $this->getExecutedCountQuery();

        $this->assertTrue(str_contains($count, 'COUNT(*)'), $count);
        $this->assertTrue(str_contains($count, self::EXCLUSION_SUBQUERY), $count);
        $this->assertTrue(str_contains($count, self::CORRELATION), $count);
    }

    public function testTheExclusionAppliesInsideTheDisabledViewWithFiltersSortingAndPaging(): void
    {
        $filters = new FilterParameter(
            columnFilters: [
                ['key' => 'search', 'type' => 'search', 'filterValue' => 'wei'],
                ['key' => 'page', 'type' => 'page', 'filterValue' => 2],
                ['key' => 'pageSize', 'type' => 'page.size', 'filterValue' => 10],
            ],
            sortFilter: new SortFilterParameter('name', 'DESC'),
        );

        $listing = $this->getListing(self::GROUP_ID, true, true, $filters);
        $listing->load();
        $listing->getTotalCount();
        $query = $this->getExecutedQuery();

        $this->assertTrue(str_contains($query, self::EXCLUSION_SUBQUERY), $query);
        $this->assertTrue(str_contains($query, '(enabled is null or enabled = 0)'), $query);
        $this->assertTrue(str_contains($query, 'name LIKE'), $query);
        $this->assertTrue(str_contains($query, 'ORDER BY `name` DESC'), $query);
        $this->assertTrue(str_contains($query, 'LIMIT 10,10'), $query);

        $count = $this->getExecutedCountQuery();

        $this->assertTrue(str_contains($count, self::EXCLUSION_SUBQUERY), $count);
        $this->assertTrue(str_contains($count, '(enabled is null or enabled = 0)'), $count);
    }

    /**
     * The real column filters, only the loader stood in for; no database, the bindings are on the listing.
     *
     * @param list<array<string, mixed>> $columnFilters
     */
    private function getListingWithColumnFilters(array $columnFilters): Listing
    {
        $quotingConnection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
        ]);
        $equalsFilter = new EqualsFilter(
            $this->makeEmpty(DbResolverInterface::class, ['get' => $quotingConnection])
        );
        $listingFilter = $this->makeEmpty(ListingFilterInterface::class, [
            'applyFilters' => static fn (
                FilterParameter $parameters,
                mixed $listing
            ): mixed => $equalsFilter->apply($parameters, $listing),
        ]);
        $repository = new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class),
            new InMemoryKeyLocalizationResolver(),
            $listingFilter,
            new SearchHelperService(
                $this->makeEmpty(TranslationRepositoryInterface::class),
                $this->makeEmpty(SecurityServiceInterface::class)
            ),
            $quotingConnection
        );

        return $repository->getListing(
            new FilterParameter(columnFilters: $columnFilters),
            self::STORE_ID,
            false,
            self::GROUP_ID
        );
    }

    private function load(?int $excludeKeysOfGroupId = null, bool $named = false): string
    {
        $this->getListing($excludeKeysOfGroupId, false, $named)->load();

        return $this->executedQueries[count($this->executedQueries) - 1];
    }

    private function getListing(
        ?int $excludeKeysOfGroupId,
        bool $disabledOnly = false,
        bool $named = true,
        ?FilterParameter $filters = null,
    ): Listing {
        $this->stubDatabaseReturning([]);

        $listingFilter = $this->makeEmpty(ListingFilterInterface::class, [
            'applyFilters' => static function (FilterParameter $parameters, mixed $listing): mixed {
                $listing = (new SortFilter())->apply($parameters, $listing);

                return (new PaginationFilter())->apply($parameters, $listing);
            },
        ]);
        $repository = new KeyRepository(
            $this->makeEmpty(KeyConfigResolverInterface::class),
            new InMemoryKeyLocalizationResolver(),
            $listingFilter,
            new SearchHelperService(
                $this->makeEmpty(TranslationRepositoryInterface::class),
                $this->makeEmpty(SecurityServiceInterface::class)
            ),
            $this->makeEmpty(Connection::class)
        );
        $filters ??= new FilterParameter();

        // the absent case is the call every shipped caller makes: three arguments and no more
        return $named || $excludeKeysOfGroupId !== null
            ? $repository->getListing($filters, self::STORE_ID, $disabledOnly, $excludeKeysOfGroupId)
            : $repository->getListing($filters, self::STORE_ID, $disabledOnly);
    }
}
