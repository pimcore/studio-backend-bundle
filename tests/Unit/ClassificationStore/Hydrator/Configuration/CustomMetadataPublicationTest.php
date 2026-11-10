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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\SubGroupHydrator;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\NullKeyOptionsResolver;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;

/**
 * The pairs of a collection, group, sub group and key are published on every detail schema that
 * carries them, which is where the listing endpoints hydrate from as well - so they are readable
 * for an entity that exists and not only in the response to a write.
 *
 * @internal
 */
final class CustomMetadataPublicationTest extends Unit
{
    private const int ID = 1;

    private const array PAIRS = ['indexed' => 'yes', 'title.de' => 'Gewicht', 'nested' => ['a' => 1]];

    public function testTheStoredPairsArePassedThroughVerbatim(): void
    {
        $collection = $this->createCollectionConfig();
        $collection->setCustomMetadata(self::PAIRS);
        $group = $this->createGroupConfig();
        $group->setCustomMetadata(self::PAIRS);
        $subGroup = $this->createSubGroupConfig();
        $subGroup->setCustomMetadata(self::PAIRS);
        $key = $this->createKeyConfig();
        $key->setCustomMetadata(self::PAIRS);

        $this->assertSame(
            self::PAIRS,
            (new CollectionHydrator())->hydrateCollectionDetail($collection)->getCustomMetadata()
        );
        $this->assertSame(self::PAIRS, (new GroupHydrator())->hydrateGroupDetail($group)->getCustomMetadata());
        $this->assertSame(
            self::PAIRS,
            (new SubGroupHydrator())->hydrateSubGroupDetail($subGroup)->getCustomMetadata()
        );
        $this->assertSame(
            self::PAIRS,
            (new KeyHydrator(new InMemoryKeyLocalizationResolver(), new NullKeyOptionsResolver()))->hydrateKeyDetail($key)->getCustomMetadata()
        );
    }

    /**
     * An entity holding no pairs publishes null rather than being left out, as its identifier does.
     */
    public function testAnEntityWithoutPairsPublishesNull(): void
    {
        $this->assertNull(
            (new CollectionHydrator())->hydrateCollectionDetail($this->createCollectionConfig())->getCustomMetadata()
        );
        $this->assertNull(
            (new GroupHydrator())->hydrateGroupDetail($this->createGroupConfig())->getCustomMetadata()
        );
        $this->assertNull(
            (new SubGroupHydrator())->hydrateSubGroupDetail($this->createSubGroupConfig())->getCustomMetadata()
        );
        $keyHydrator = new KeyHydrator(new InMemoryKeyLocalizationResolver(), new NullKeyOptionsResolver());
        $this->assertNull($keyHydrator->hydrateKeyDetail($this->createKeyConfig())->getCustomMetadata());
    }

    private function createCollectionConfig(): CollectionConfig
    {
        $config = new CollectionConfig();
        $config->setId(self::ID);
        $config->setName('My Collection');
        $config->setStoreId(self::ID);

        return $config;
    }

    private function createGroupConfig(): GroupConfig
    {
        $config = new GroupConfig();
        $config->setId(self::ID);
        $config->setName('My Group');
        $config->setStoreId(self::ID);

        return $config;
    }

    private function createSubGroupConfig(): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setId(self::ID);
        $config->setName('My Sub Group');
        $config->setStoreId(self::ID);

        return $config;
    }

    private function createKeyConfig(): KeyConfig
    {
        $config = new KeyConfig();
        $config->setId(self::ID);
        $config->setName('My Key');
        $config->setStoreId(self::ID);
        $config->setType('input');
        $config->setDefinition('{"fieldtype":"input"}');

        return $config;
    }
}
