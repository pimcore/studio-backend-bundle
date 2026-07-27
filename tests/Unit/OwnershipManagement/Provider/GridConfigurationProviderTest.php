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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OwnershipManagement\Provider;

use Codeception\Test\Unit;
use DateTime;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Repository\ConfigurationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\OwnershipConfigurationHydrator;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider\GridConfigurationProvider;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;

/**
 * @internal
 */
final class GridConfigurationProviderTest extends Unit
{
    /**
     * Regression test for #1918: deleting an owner sets the owner to NULL (instead of cascading the
     * deletion), so the ownership management must surface such configurations with owner id 0.
     *
     * @throws Exception
     */
    public function testListMapsDeletedOwnerToZeroAndMarksDeleted(): void
    {
        $configuration = $this->make(GridConfiguration::class, [
            'getId' => 42,
            'getName' => 'Shared grid view',
            'getOwner' => null,
            'getCreationDate' => new DateTime('@1718000000'),
            'getModificationDate' => new DateTime('@1718000500'),
        ]);

        $provider = new GridConfigurationProvider(
            $this->makeEmpty(ConfigurationRepositoryInterface::class, [
                'findAllPaginated' => [$configuration],
                'countAll' => 1,
            ]),
            new OwnershipConfigurationHydrator(),
            $this->makeEmpty(UserServiceInterface::class, [
                'getUserNamesByIds' => [],
            ]),
        );

        $result = $provider->listConfigurations(new OwnershipListQuery(0, 10));

        $this->assertSame(1, $result->getTotalItems());

        $item = $result->getItems()[0];
        $this->assertSame(0, $item->getOwnerId());
        $this->assertNull($item->getOwnerName());
        $this->assertTrue($item->isOwnerDeleted());
    }

    /**
     * @throws Exception
     */
    public function testListKeepsExistingOwner(): void
    {
        $configuration = $this->make(GridConfiguration::class, [
            'getId' => 7,
            'getName' => 'My grid view',
            'getOwner' => 5,
            'getCreationDate' => new DateTime('@1718000000'),
            'getModificationDate' => new DateTime('@1718000500'),
        ]);

        $provider = new GridConfigurationProvider(
            $this->makeEmpty(ConfigurationRepositoryInterface::class, [
                'findAllPaginated' => [$configuration],
                'countAll' => 1,
            ]),
            new OwnershipConfigurationHydrator(),
            $this->makeEmpty(UserServiceInterface::class, [
                'getUserNamesByIds' => [5 => 'john_doe'],
            ]),
        );

        $item = $provider->listConfigurations(new OwnershipListQuery(0, 10))->getItems()[0];

        $this->assertSame(5, $item->getOwnerId());
        $this->assertSame('john_doe', $item->getOwnerName());
        $this->assertFalse($item->isOwnerDeleted());
    }
}
