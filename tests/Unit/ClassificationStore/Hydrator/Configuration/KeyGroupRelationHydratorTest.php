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
    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

    private KeyGroupRelationHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new KeyGroupRelationHydrator();
    }

    public function testThePerAssignmentColumnsArePassedThroughVerbatim(): void
    {
        $relation = $this->createRelation();
        $relation->setDefinitionOverride(['defaultUnit' => 'kg']);
        $relation->setSubGroup('Dimensions');
        $relation->setMetadata(['source' => 'erp']);

        $detail = $this->hydrator->hydrateKeyGroupRelationDetail($relation);

        $this->assertSame(['defaultUnit' => 'kg'], $detail->getDefinitionOverride());
        $this->assertSame('Dimensions', $detail->getSubGroup());
        $this->assertSame(['source' => 'erp'], $detail->getMetadata());
    }

    public function testUnsetPerAssignmentColumnsStayNull(): void
    {
        $detail = $this->hydrator->hydrateKeyGroupRelationDetail($this->createRelation());

        $this->assertNull($detail->getDefinitionOverride());
        $this->assertNull($detail->getSubGroup());
        $this->assertNull($detail->getMetadata());
    }

    private function createRelation(): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);

        return $relation;
    }
}
