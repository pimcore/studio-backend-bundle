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
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;

/**
 * @internal
 */
final class CollectionHydratorTest extends Unit
{
    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    private CollectionHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new CollectionHydrator();
    }

    public function testStoredIdentifierIsPassedThroughVerbatim(): void
    {
        $collectionConfig = $this->createCollectionConfig();
        $collectionConfig->setIdentifier(self::IDENTIFIER);

        $this->assertSame(
            self::IDENTIFIER,
            $this->hydrator->hydrateCollectionDetail($collectionConfig)->getIdentifier()
        );
    }

    public function testUnsetIdentifierStaysNull(): void
    {
        $this->assertNull(
            $this->hydrator->hydrateCollectionDetail($this->createCollectionConfig())->getIdentifier()
        );
    }

    private function createCollectionConfig(): CollectionConfig
    {
        $collectionConfig = new CollectionConfig();
        $collectionConfig->setId(1);
        $collectionConfig->setName('My Collection');
        $collectionConfig->setStoreId(1);

        return $collectionConfig;
    }
}
