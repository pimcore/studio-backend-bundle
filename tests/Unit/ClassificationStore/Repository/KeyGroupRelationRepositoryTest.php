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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepository;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchHelperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use ReflectionMethod;

/**
 * @internal
 */
final class KeyGroupRelationRepositoryTest extends Unit
{
    private const array GROUP_ORDER_KEYS = ['sorter', 'id'];

    private const array GROUP_ORDER = ['ASC', 'ASC'];

    private const array STORE_ORDER_KEYS = ['groupId', 'sorter', 'id'];

    private const array STORE_ORDER = ['ASC', 'ASC', 'ASC'];

    public function testGetByGroupIdOrdersBySorterThenId(): void
    {
        $orderKey = null;
        $order = null;
        $listing = $this->createCapturingListing($orderKey, $order);

        $this->applyOrder('applyGroupKeyOrder', $listing);

        $this->assertSame(self::GROUP_ORDER_KEYS, $orderKey);
        $this->assertSame(self::GROUP_ORDER, $order);
    }

    public function testBaseListingOrdersByGroupThenSorterThenId(): void
    {
        $orderKey = null;
        $order = null;
        $listing = $this->createCapturingListing($orderKey, $order);

        $this->applyOrder('applyStoreKeyOrder', $listing);

        $this->assertSame(self::STORE_ORDER_KEYS, $orderKey);
        $this->assertSame(self::STORE_ORDER, $order);
    }

    public function testBaseListingKeepsItsOrderWithASearchTerm(): void
    {
        // A real subclass, not a double: the search filter calls the @deprecated quote(), which fails the suite.
        $listing = new class() extends Listing {
            public array|string|null $capturedOrderKey = null;

            public array|string|null $capturedOrder = null;

            public function setOrderKey(array|string $orderKey, bool $quote = true): static
            {
                $this->capturedOrderKey = $orderKey;

                return $this;
            }

            public function setOrder(array|string $order): static
            {
                $this->capturedOrder = $order;

                return $this;
            }

            public function quote(mixed $value, ?int $type = null): string
            {
                return "'" . $value . "'";
            }
        };

        $this->applyOrder('applyStoreKeyOrder', $listing);
        $this->createSearchHelperService()->applyKeyGroupRelationSearchFilter($listing, 'weight');

        $this->assertSame(self::STORE_ORDER_KEYS, $listing->capturedOrderKey);
        $this->assertSame(self::STORE_ORDER, $listing->capturedOrder);
    }

    private function createCapturingListing(array|string|null &$orderKey, array|string|null &$order): Listing
    {
        $listing = null;
        $listing = $this->makeEmpty(Listing::class, [
            'setOrderKey' => static function (array|string $key) use (&$listing, &$orderKey): Listing {
                $orderKey = $key;

                return $listing;
            },
            'setOrder' => static function (array|string $direction) use (&$listing, &$order): Listing {
                $order = $direction;

                return $listing;
            },
        ]);

        return $listing;
    }

    private function applyOrder(string $method, Listing $listing): void
    {
        $repository = new KeyGroupRelationRepository(
            $this->makeEmpty(GroupConfigRepositoryInterface::class),
            $this->makeEmpty(SearchHelperServiceInterface::class)
        );

        (new ReflectionMethod($repository, $method))->invoke($repository, $listing);
    }

    private function createSearchHelperService(): SearchHelperService
    {
        return new SearchHelperService(
            $this->makeEmpty(TranslationRepositoryInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => static fn (): never => throw new UserNotFoundException(),
            ])
        );
    }
}
