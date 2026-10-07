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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Translation\ListingFilter;

use Codeception\Test\Unit;
use Doctrine\DBAL\Connection;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Translation\ListingFilter\TranslationLikeFilter;
use Pimcore\Model\Element\Note\Listing as NoteListing;
use Pimcore\Model\Translation\Listing as TranslationListing;

/**
 * @internal
 */
final class TranslationLikeFilterTest extends Unit
{
    public function testFilterIsAppliedForListingLanguage(): void
    {
        $listing = $this->createListing(['de', 'fr_BE']);

        $this->createFilter()->apply($this->createParameters('fr_BE', 'car'), $listing);

        $this->assertStringContainsString('`fr_BE`.text LIKE :translationLikeValue', $listing->getCondition());
        $this->assertSame(['translationLikeValue' => '%car%'], $listing->getConditionVariables());
    }

    public function testFilterIsRejectedForUnknownLanguage(): void
    {
        $listing = $this->createListing(['de', 'fr_BE']);

        $this->expectException(InvalidArgumentException::class);

        $this->createFilter()->apply($this->createParameters('xx_unknown', 'car'), $listing);
    }

    public function testListingIsUnchangedWithoutTranslationFilter(): void
    {
        $listing = $this->createListing(['de']);

        $this->createFilter()->apply(new FilterParameter(), $listing);

        $this->assertSame('', $listing->getCondition());
    }

    public function testOnlyTranslationListingsAreSupported(): void
    {
        $filter = $this->createFilter();

        $this->assertTrue($filter->supports($this->createListing(['de'])));
        $this->assertFalse($filter->supports(new NoteListing()));
    }

    /**
     * @param array<int, string> $languages
     */
    private function createListing(array $languages): TranslationListing
    {
        $listing = new TranslationListing();
        $listing->setLanguages($languages);

        return $listing;
    }

    private function createParameters(string $key, string $value): FilterParameter
    {
        return new FilterParameter(columnFilters: [
            ['key' => $key, 'type' => 'translationLike', 'filterValue' => $value],
        ]);
    }

    private function createFilter(): TranslationLikeFilter
    {
        $connection = $this->makeEmpty(Connection::class, [
            'quoteIdentifier' => static function (string $identifier): string {
                return '`' . str_replace('`', '``', $identifier) . '`';
            },
        ]);

        return new TranslationLikeFilter(
            $this->makeEmpty(DbResolverInterface::class, ['get' => $connection])
        );
    }
}
