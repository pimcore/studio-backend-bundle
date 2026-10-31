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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_column;

/**
 * The two layout path families label a key through different services - the layout endpoints through
 * KeyGroupLayoutService, the already-active groups of an object layout through ActiveGroupTitleEnricher.
 * Both are run over one fixture here, so a key cannot carry one label before a reload and another after.
 *
 * @internal
 */
final class KeyTitleAgreementTest extends Unit
{
    private const int KEY_ID = 11;

    private const int GROUP_ID = 3;

    private const string KEY_NAME = 'weight';

    private const string STORED_TITLE = 'Column title';

    /**
     * The enriched field definition of the payload carries a title of its own; neither family reads it.
     */
    public function testBothFamiliesReportTheStoredTitle(): void
    {
        $keyConfigResolver = $this->createKeyConfigResolver(self::STORED_TITLE);

        $this->assertSame(self::STORED_TITLE, $this->layoutTitle($keyConfigResolver));
        $this->assertSame(self::STORED_TITLE, $this->objectLayoutTitle($keyConfigResolver));
    }

    /**
     * The one sanctioned divergence: the layout endpoints report the stored value verbatim, so the
     * configuration UI can tell "no title set" apart from "title equals name", while the object layout
     * falls back to the name rather than rendering a blank label.
     */
    public function testOnlyTheFallbackOfAStoredNothingDiffers(): void
    {
        $keyConfigResolver = $this->createKeyConfigResolver(null);

        $this->assertNull($this->layoutTitle($keyConfigResolver));
        $this->assertSame(self::KEY_NAME, $this->objectLayoutTitle($keyConfigResolver));
    }

    private function layoutTitle(KeyConfigResolverInterface $keyConfigResolver): ?string
    {
        $service = new KeyGroupLayoutService(
            $this->createMock(ServiceResolverInterface::class),
            $this->createMock(KeyRelationResolverInterface::class),
            new ConcreteObjectResolver(),
            $keyConfigResolver,
        );

        return $service->getKeyTitle(self::KEY_ID);
    }

    private function objectLayoutTitle(KeyConfigResolverInterface $keyConfigResolver): mixed
    {
        $definition = new Input();
        $definition->setName(self::KEY_NAME);
        $definition->setTitle('Definition title');

        $fieldDefinition = new Classificationstore();
        $fieldDefinition->setName('classificationStore');
        $fieldDefinition->activeGroupDefinitions = [
            self::GROUP_ID => [
                'name' => 'Technical',
                'id' => self::GROUP_ID,
                'description' => '',
                'keys' => [
                    [
                        'name' => self::KEY_NAME,
                        'id' => self::KEY_ID,
                        'description' => '',
                        'definition' => $definition,
                    ],
                ],
            ],
        ];

        (new ActiveGroupTitleEnricher($keyConfigResolver))->enrich($fieldDefinition);

        return array_column($fieldDefinition->activeGroupDefinitions[self::GROUP_ID]['keys'], 'title')[0];
    }

    private function createKeyConfigResolver(?string $storedTitle): KeyConfigResolverInterface
    {
        $keyConfig = new KeyConfig();
        if ($storedTitle !== null) {
            $keyConfig->setTitle($storedTitle);
        }

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        return $keyConfigResolver;
    }
}
