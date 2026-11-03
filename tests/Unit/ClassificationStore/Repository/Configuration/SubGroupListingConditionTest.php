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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperService;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\EqualsFilter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing;
use Pimcore\Model\UserInterface;
use function is_int;
use function str_contains;
use function substr_count;

/**
 * The listing the repository hands back carries the route's store restriction next to whatever the
 * client sent. Pimcore keys its conditions by the condition string and merges their bindings into
 * one parameter set, so both are observable on the listing itself - no database needed - through
 * `getCondition()` and the `getConditionVariables()` it fills.
 *
 * @internal
 */
final class SubGroupListingConditionTest extends Unit
{
    private const int STORE_ID = 1;

    private const int OTHER_STORE_ID = 2;

    /**
     * The route binds the store. A client supplied equals filter on the same column must be able to
     * narrow that, never to replace it - which is what an identically keyed condition would do.
     */
    public function testAnEqualsFilterOnStoreIdCannotReplaceTheRouteStore(): void
    {
        $listing = $this->getListing([
            ['key' => 'storeId', 'type' => 'equals', 'filterValue' => self::OTHER_STORE_ID],
        ]);

        $condition = $listing->getCondition();
        $variables = $listing->getConditionVariables();

        // two conditions, not one: the filter was added next to the restriction
        $this->assertSame(2, substr_count($condition, '`storeId` ='));
        $this->assertContains(self::STORE_ID, $variables, 'The route store has to survive the filter.');
        $this->assertContains(self::OTHER_STORE_ID, $variables);

        // and they are ANDed, so asking store 1 for store 2 matches nothing rather than crossing over
        $this->assertStringContainsString('AND', $condition);
    }

    /**
     * A search and the store restriction land in the same statement. DBAL binds a statement either
     * by name or by position, so every placeholder in it has to be named.
     */
    public function testTheStoreRestrictionAndASearchBindByNameOnly(): void
    {
        $listing = $this->getListing([
            ['key' => 'search', 'type' => 'search', 'filterValue' => 'dim'],
        ]);

        $condition = $listing->getCondition();
        $variables = $listing->getConditionVariables();

        $this->assertStringNotContainsString('?', $condition, 'A positional placeholder cannot be bound here.');
        $this->assertStringContainsString(':scopeStoreId', $condition);
        $this->assertStringContainsString(':searchTerm0', $condition);
        // the translated term is a second search parameter, under its own name
        $this->assertStringContainsString(':searchTerm1', $condition);

        foreach ($variables as $name => $value) {
            $this->assertFalse(is_int($name), 'Every binding has to be named, not positional.');
        }

        $this->assertSame(self::STORE_ID, $variables['scopeStoreId']);
        $this->assertSame('%dim%', $variables['searchTerm0']);
        $this->assertSame('%dimension_label%', $variables['searchTerm1']);
    }

    /**
     * All three at once: the restriction, the search and a column filter, still named throughout and
     * with the route's store intact.
     */
    public function testASearchAndAColumnFilterKeepTheStoreRestrictionIntact(): void
    {
        $listing = $this->getListing([
            ['key' => 'search', 'type' => 'search', 'filterValue' => 'dim'],
            ['key' => 'storeId', 'type' => 'equals', 'filterValue' => self::OTHER_STORE_ID],
        ]);

        $condition = $listing->getCondition();
        $variables = $listing->getConditionVariables();

        $this->assertStringNotContainsString('?', $condition);
        $this->assertSame(self::STORE_ID, $variables['scopeStoreId']);
        $this->assertSame(self::OTHER_STORE_ID, $variables['storeId']);
        $this->assertSame('%dim%', $variables['searchTerm0']);
    }

    /**
     * @param list<array<string, mixed>> $columnFilters
     */
    private function getListing(array $columnFilters): Listing
    {
        $quotingConnection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static fn (string $identifier): string => '`' . $identifier . '`',
        ]);

        $equalsFilter = new EqualsFilter(
            $this->makeEmpty(DbResolverInterface::class, ['get' => $quotingConnection])
        );

        // the generic column filters as the listing really sees them; only the loader is stood in for
        $listingFilter = $this->makeEmpty(ListingFilterInterface::class, [
            'applyFilters' => static fn (
                FilterParameter $parameters,
                mixed $listing
            ): mixed => $equalsFilter->apply($parameters, $listing),
        ]);

        $searchHelperService = new SearchHelperService(
            $this->makeEmpty(TranslationRepositoryInterface::class, [
                'getTranslationKeysWithTextFilter' => static fn (string $term): array => str_contains($term, 'dim')
                    ? ['dimension_label']
                    : [],
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['getLanguage' => 'en']),
            ])
        );

        $repository = new SubGroupRepository(
            $listingFilter,
            $searchHelperService,
            $quotingConnection
        );

        return $repository->getListing(new FilterParameter(columnFilters: $columnFilters), self::STORE_ID);
    }
}
