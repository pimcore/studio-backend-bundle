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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydrator;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * The key's type on the relations payload, and the case that made it need a guard.
 *
 * `KeyGroupRelation::$type` is a typed property with no default, so a relation that was never
 * hydrated with one throws on the getter rather than answering - and such a relation is ordinary
 * in this bundle rather than an edge case: four of its own unit tests build one that way. Without
 * the guard this hydrator takes the whole listing down with an Error that names no relation.
 *
 * @internal
 */
final class KeyTypePublicationTest extends Unit
{
    public function testTheStoredKeyTypeIsPublishedVerbatim(): void
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(7);
        $relation->setName('diameter');
        $relation->setType('input');

        $hydrated = (new KeyGroupRelationHydrator())->hydrate($relation, $this->groupConfig());

        $this->assertSame(
            'input',
            $hydrated->getKeyType(),
            'the type is reported as the relation stores it, so a picker can decide before Apply'
        );
    }

    public function testARelationThatStatesNoTypePublishesNull(): void
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(7);
        $relation->setName('diameter');

        $hydrated = (new KeyGroupRelationHydrator())->hydrate($relation, $this->groupConfig());

        $this->assertNull(
            $hydrated->getKeyType(),
            'an unhydrated type is no type - it must not raise, and it must not be guessed'
        );
    }

    private function groupConfig(): GroupConfig
    {
        $groupConfig = new GroupConfig();
        $groupConfig->setId(3);
        $groupConfig->setName('dimensions');

        return $groupConfig;
    }
}
