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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyFilterResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SearchIndexFlagResolver;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
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

        $hydrated = $this->hydrator()->hydrate($relation, $this->groupConfig());

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

        $hydrated = $this->hydrator()->hydrate($relation, $this->groupConfig());

        $this->assertNull(
            $hydrated->getKeyType(),
            'an unhydrated type is no type - it must not raise, and it must not be guessed'
        );
    }

    public function testTheFilterVerdictIsPublishedBesideTheKeyType(): void
    {
        $indexed = $this->hydrator()->hydrate($this->statedRelation('{"fieldtype":"input"}'), $this->groupConfig());
        $excluded = $this->hydrator()->hydrate(
            $this->statedRelation('{"fieldtype":"input","indexed":false}'),
            $this->groupConfig()
        );

        $this->assertTrue($indexed->getFilterable());
        $this->assertNull($indexed->getFilterUnavailableReason());
        $this->assertFalse($excluded->getFilterable());
        $this->assertSame('indexExcluded', $excluded->getFilterUnavailableReason());
    }

    public function testARelationThatStatesNoDefinitionPublishesNoVerdict(): void
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(7);
        $relation->setName('diameter');
        $relation->setType('input');

        $hydrated = $this->hydrator()->hydrate($relation, $this->groupConfig());

        $this->assertNull($hydrated->getFilterable());
        $this->assertNull($hydrated->getFilterUnavailableReason());
    }

    private function statedRelation(string $definition): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(7);
        $relation->setName('diameter');
        $relation->setType('input');
        $relation->setDefinition($definition);

        return $relation;
    }

    private function hydrator(): KeyGroupRelationHydrator
    {
        return new KeyGroupRelationHydrator(new KeyFilterResolver(
            $this->makeEmpty(ServiceResolverInterface::class, ['getFieldDefinitionFromJson' => new Input()]),
            new SearchIndexFlagResolver()
        ));
    }

    private function groupConfig(): GroupConfig
    {
        $groupConfig = new GroupConfig();
        $groupConfig->setId(3);
        $groupConfig->setName('dimensions');

        return $groupConfig;
    }
}
