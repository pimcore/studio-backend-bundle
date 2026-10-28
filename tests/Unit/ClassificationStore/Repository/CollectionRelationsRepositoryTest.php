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
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\CollectionRelationsRepository;
use Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Listing;
use ReflectionMethod;

/**
 * @internal
 */
final class CollectionRelationsRepositoryTest extends Unit
{
    public function testGetFromCollectionOrdersBySorterThenGroupId(): void
    {
        $orderKey = null;
        $order = null;
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

        $repository = new CollectionRelationsRepository($this->makeEmpty(DbResolverInterface::class));
        (new ReflectionMethod($repository, 'applyGroupOrder'))->invoke($repository, $listing);

        $this->assertSame(['sorter', 'groupId'], $orderKey);
        $this->assertSame(['ASC', 'ASC'], $order);
    }
}
