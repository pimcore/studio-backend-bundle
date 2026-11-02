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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydrator;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
final class KeyGroupRelationHydratorTest extends Unit
{
    private const string SUB_GROUP = '01JBZ8QF6K3M4N5P6R7S8T9V0W';

    public function testSubGroupIsCarriedThrough(): void
    {
        $hydrator = new KeyGroupRelationHydrator();

        $this->assertSame(
            self::SUB_GROUP,
            $hydrator->hydrateKeyGroupRelationDetail($this->createRelation(self::SUB_GROUP))->getSubGroup()
        );
        $this->assertNull(
            $hydrator->hydrateKeyGroupRelationDetail($this->createRelation(null))->getSubGroup()
        );
    }

    private function createRelation(?string $subGroup): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(11);
        $relation->setGroupId(3);
        $relation->setSorter(0);
        $relation->setMandatory(false);
        $relation->setSubGroup($subGroup);

        return $relation;
    }
}
