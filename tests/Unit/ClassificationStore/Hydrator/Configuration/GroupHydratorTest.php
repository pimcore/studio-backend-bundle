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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\GroupHydrator;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;

/**
 * @internal
 */
final class GroupHydratorTest extends Unit
{
    private const string IDENTIFIER = '01K9T0ZQ0VX8W4S1R7J3A2B5C6';

    private GroupHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new GroupHydrator();
    }

    public function testStoredIdentifierIsPassedThroughVerbatim(): void
    {
        $groupConfig = $this->createGroupConfig();
        $groupConfig->setIdentifier(self::IDENTIFIER);

        $this->assertSame(self::IDENTIFIER, $this->hydrator->hydrateGroupDetail($groupConfig)->getIdentifier());
    }

    public function testUnsetIdentifierStaysNull(): void
    {
        $this->assertNull($this->hydrator->hydrateGroupDetail($this->createGroupConfig())->getIdentifier());
    }

    private function createGroupConfig(): GroupConfig
    {
        $groupConfig = new GroupConfig();
        $groupConfig->setId(1);
        $groupConfig->setName('My Group');
        $groupConfig->setStoreId(1);

        return $groupConfig;
    }
}
