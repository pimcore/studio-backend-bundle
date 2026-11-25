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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyFilterResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\LabelResolverMockTrait;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation as CoreKeyGroupRelation;

/**
 * The label a relations row is shown under. The row carries the technical key name only - core's
 * relation listing selects the key's title column and then drops it, because the relation model has
 * no property for it - so a caller rendering a row had nothing but `height` to show.
 *
 * @internal
 */
final class KeyTitlePublicationTest extends Unit
{
    use LabelResolverMockTrait;

    private const int KEY_ID = 7;

    public function testTheKeysOwnTitleIsPublishedBesideItsName(): void
    {
        $hydrated = $this
            ->hydrator($this->createKeyConfig(self::KEY_ID, 'height', 'Height'))
            ->hydrate($this->relation(), $this->groupConfig());

        $this->assertSame('height', $hydrated->getKeyName());
        $this->assertSame('Height', $hydrated->getKeyTitle());
    }

    public function testAStoredLabelOfTheLabelLanguageWinsOverTheOwnTitle(): void
    {
        $hydrated = $this
            ->hydrator(
                $this->createKeyConfig(self::KEY_ID, 'height', 'Height'),
                [self::KEY_ID => ['de' => 'Höhe', 'en' => 'Height']],
                'de'
            )
            ->hydrate($this->relation(), $this->groupConfig());

        $this->assertSame('Höhe', $hydrated->getKeyTitle());
    }

    public function testAKeyWithoutATitleFallsBackToItsNameRatherThanToNothing(): void
    {
        $hydrated = $this
            ->hydrator($this->createKeyConfig(self::KEY_ID, 'height'))
            ->hydrate($this->relation(), $this->groupConfig());

        $this->assertSame(
            'height',
            $hydrated->getKeyTitle(),
            'the resolver ends on the technical name, so a caller never has to repeat that fallback'
        );
    }

    public function testARelationWhoseKeyCannotBeResolvedPublishesNoTitle(): void
    {
        $hydrated = $this->hydrator(null)->hydrate($this->relation(), $this->groupConfig());

        $this->assertNull(
            $hydrated->getKeyTitle(),
            'a key the configuration no longer carries has no title to report, and must not guess one'
        );
    }

    /**
     * @param array<int, array<string, string>> $labels
     */
    private function hydrator(
        ?KeyConfig $keyConfig,
        array $labels = [],
        string $userLanguage = 'en'
    ): KeyGroupRelationHydrator {
        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturn($keyConfig);

        return new KeyGroupRelationHydrator(
            $this->createMock(KeyFilterResolverInterface::class),
            $keyConfigResolver,
            $this->createLabelResolver($labels),
            $this->createLabelLanguageResolver($userLanguage, 'en')
        );
    }

    private function relation(): CoreKeyGroupRelation
    {
        $relation = new CoreKeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setName('height');

        return $relation;
    }

    private function groupConfig(): GroupConfig
    {
        $groupConfig = new GroupConfig();
        $groupConfig->setId(3);
        $groupConfig->setName('Dimensions');

        return $groupConfig;
    }
}
