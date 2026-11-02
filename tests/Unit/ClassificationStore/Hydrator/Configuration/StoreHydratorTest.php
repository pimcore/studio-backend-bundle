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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Hydrator\Configuration;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\StoreHydrator;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * @internal
 */
final class StoreHydratorTest extends Unit
{
    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    private StoreHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new StoreHydrator();
    }

    public function testStoredIdentifierIsPassedThroughVerbatim(): void
    {
        $storeConfig = $this->createStoreConfig();
        $storeConfig->setIdentifier(self::IDENTIFIER);

        $this->assertSame(self::IDENTIFIER, $this->hydrator->hydrateStoreDetail($storeConfig)->getIdentifier());
        $this->assertSame(self::IDENTIFIER, $this->hydrator->hydrateStoreTreeNode($storeConfig)->getIdentifier());
    }

    public function testUnsetIdentifierStaysNull(): void
    {
        $storeConfig = $this->createStoreConfig();

        $this->assertNull($this->hydrator->hydrateStoreDetail($storeConfig)->getIdentifier());
        $this->assertNull($this->hydrator->hydrateStoreTreeNode($storeConfig)->getIdentifier());
    }

    private function createStoreConfig(): StoreConfig
    {
        $storeConfig = new StoreConfig();
        $storeConfig->setId(1);
        $storeConfig->setName('My Store');

        return $storeConfig;
    }
}
