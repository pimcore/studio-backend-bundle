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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\CollectionHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\GroupHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\StoreHydrator;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * The identifier of a store, collection, group and key is read-only and published on every detail
 * schema that carries one, which is where the listing endpoints hydrate from as well - so the field
 * appears on the listings, on create and on update alike.
 *
 * @internal
 */
final class IdentifierPublicationTest extends Unit
{
    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    private const int ID = 1;

    public function testTheStoredIdentifierIsPassedThroughVerbatim(): void
    {
        $storeConfig = $this->createStoreConfig();
        $storeConfig->setIdentifier(self::IDENTIFIER);

        $collectionConfig = $this->createCollectionConfig();
        $collectionConfig->setIdentifier(self::IDENTIFIER);

        $groupConfig = $this->createGroupConfig();
        $groupConfig->setIdentifier(self::IDENTIFIER);

        $keyConfig = $this->createKeyConfig();
        $keyConfig->setIdentifier(self::IDENTIFIER);

        $storeHydrator = new StoreHydrator();

        $this->assertSame(self::IDENTIFIER, $storeHydrator->hydrateStoreDetail($storeConfig)->getIdentifier());
        $this->assertSame(self::IDENTIFIER, $storeHydrator->hydrateStoreTreeNode($storeConfig)->getIdentifier());
        $this->assertSame(
            self::IDENTIFIER,
            (new CollectionHydrator())->hydrateCollectionDetail($collectionConfig)->getIdentifier()
        );
        $this->assertSame(
            self::IDENTIFIER,
            (new GroupHydrator())->hydrateGroupDetail($groupConfig)->getIdentifier()
        );
        $this->assertSame(
            self::IDENTIFIER,
            (new KeyHydrator())->hydrateKeyDetail($keyConfig)->getIdentifier()
        );
    }

    /**
     * An entity read before its identifier was generated publishes null rather than being left out.
     */
    public function testAnUnsetIdentifierStaysNull(): void
    {
        $storeHydrator = new StoreHydrator();
        $storeConfig = $this->createStoreConfig();

        $this->assertNull($storeHydrator->hydrateStoreDetail($storeConfig)->getIdentifier());
        $this->assertNull($storeHydrator->hydrateStoreTreeNode($storeConfig)->getIdentifier());
        $this->assertNull(
            (new CollectionHydrator())->hydrateCollectionDetail($this->createCollectionConfig())->getIdentifier()
        );
        $this->assertNull((new GroupHydrator())->hydrateGroupDetail($this->createGroupConfig())->getIdentifier());
        $this->assertNull((new KeyHydrator())->hydrateKeyDetail($this->createKeyConfig())->getIdentifier());
    }

    private function createStoreConfig(): StoreConfig
    {
        $storeConfig = new StoreConfig();
        $storeConfig->setId(self::ID);
        $storeConfig->setName('My Store');

        return $storeConfig;
    }

    private function createCollectionConfig(): CollectionConfig
    {
        $collectionConfig = new CollectionConfig();
        $collectionConfig->setId(self::ID);
        $collectionConfig->setName('My Collection');
        $collectionConfig->setStoreId(self::ID);

        return $collectionConfig;
    }

    private function createGroupConfig(): GroupConfig
    {
        $groupConfig = new GroupConfig();
        $groupConfig->setId(self::ID);
        $groupConfig->setName('My Group');
        $groupConfig->setStoreId(self::ID);

        return $groupConfig;
    }

    private function createKeyConfig(): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(self::ID);
        $keyConfig->setName('My Key');
        $keyConfig->setStoreId(self::ID);
        $keyConfig->setType('input');
        $keyConfig->setDefinition('{"fieldtype":"input"}');

        return $keyConfig;
    }
}
