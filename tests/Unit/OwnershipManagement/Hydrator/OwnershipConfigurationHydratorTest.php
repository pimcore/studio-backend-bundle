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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OwnershipManagement\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\OwnershipConfigurationHydrator;

/**
 * @internal
 */
final class OwnershipConfigurationHydratorTest extends Unit
{
    public function testHydrateMapsFieldsWithProvidedOwnerName(): void
    {
        $hydrator = new OwnershipConfigurationHydrator();

        $configuration = $hydrator->hydrate('42', 'grid_configuration', 'My grid view', 7, 'john_doe', 1718000000, 1718000500);

        $this->assertSame('42', $configuration->getId());
        $this->assertSame('grid_configuration', $configuration->getType());
        $this->assertSame('My grid view', $configuration->getName());
        $this->assertSame(7, $configuration->getOwnerId());
        $this->assertSame('john_doe', $configuration->getOwnerName());
        $this->assertFalse($configuration->isOwnerDeleted());
        $this->assertSame(1718000000, $configuration->getCreationDate());
        $this->assertSame(1718000500, $configuration->getModificationDate());
    }

    public function testHydrateMarksOwnerDeletedWhenOwnerNameIsNull(): void
    {
        $hydrator = new OwnershipConfigurationHydrator();

        $configuration = $hydrator->hydrate('uuid-1', 'dashboard', 'My dashboard', 99, null);

        $this->assertNull($configuration->getOwnerName());
        $this->assertTrue($configuration->isOwnerDeleted());
        $this->assertNull($configuration->getCreationDate());
        $this->assertNull($configuration->getModificationDate());
    }
}
