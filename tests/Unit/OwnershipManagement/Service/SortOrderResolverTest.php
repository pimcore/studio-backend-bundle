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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OwnershipManagement\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\SortOrderResolver;

/**
 * @internal
 */
final class SortOrderResolverTest extends Unit
{
    private const array SORTABLE_FIELDS = ['id', 'name', 'owner', 'creationDate', 'modificationDate'];

    private const string DEFAULT_FIELD = 'modificationDate';

    public function testFallsBackToDefaultFieldDescendingWhenNoSortGiven(): void
    {
        $resolver = new SortOrderResolver();

        $this->assertSame(
            [['field' => 'modificationDate', 'direction' => 'DESC']],
            $resolver->resolve([], self::SORTABLE_FIELDS, self::DEFAULT_FIELD)
        );
    }

    public function testFallsBackToDefaultWhenAllFieldsAreUnknown(): void
    {
        $resolver = new SortOrderResolver();

        $this->assertSame(
            [['field' => 'modificationDate', 'direction' => 'DESC']],
            $resolver->resolve(
                [['field' => 'unknown', 'direction' => 'ASC'], ['field' => 'secret']],
                self::SORTABLE_FIELDS,
                self::DEFAULT_FIELD
            )
        );
    }

    public function testKeepsWhitelistedFieldAndNormalizesLowercaseAscending(): void
    {
        $resolver = new SortOrderResolver();

        $this->assertSame(
            [['field' => 'name', 'direction' => 'ASC']],
            $resolver->resolve([['field' => 'name', 'direction' => 'asc']], self::SORTABLE_FIELDS, self::DEFAULT_FIELD)
        );
    }

    public function testDefaultsToDescendingForMissingOrInvalidDirection(): void
    {
        $resolver = new SortOrderResolver();

        $this->assertSame(
            [['field' => 'id', 'direction' => 'DESC']],
            $resolver->resolve([['field' => 'id', 'direction' => 'sideways']], self::SORTABLE_FIELDS, self::DEFAULT_FIELD)
        );

        $this->assertSame(
            [['field' => 'id', 'direction' => 'DESC']],
            $resolver->resolve([['field' => 'id']], self::SORTABLE_FIELDS, self::DEFAULT_FIELD)
        );
    }

    public function testPreservesOrderOfMultipleSortsAsPrimaryAndTieBreakers(): void
    {
        $resolver = new SortOrderResolver();

        $this->assertSame(
            [
                ['field' => 'owner', 'direction' => 'ASC'],
                ['field' => 'id', 'direction' => 'DESC'],
            ],
            $resolver->resolve(
                [
                    ['field' => 'owner', 'direction' => 'ASC'],
                    ['field' => 'id', 'direction' => 'DESC'],
                ],
                self::SORTABLE_FIELDS,
                self::DEFAULT_FIELD
            )
        );
    }

    public function testDropsUnknownFieldsButKeepsValidOnesInOrder(): void
    {
        $resolver = new SortOrderResolver();

        $this->assertSame(
            [['field' => 'name', 'direction' => 'ASC']],
            $resolver->resolve(
                [
                    ['field' => 'bogus', 'direction' => 'DESC'],
                    ['field' => 'name', 'direction' => 'ASC'],
                ],
                self::SORTABLE_FIELDS,
                self::DEFAULT_FIELD
            )
        );
    }
}
