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
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use Pimcore\Model\UserInterface;
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

    private const string USER_LANGUAGE = 'de';

    private const string RESOLVED_LABEL = 'Gehäuse';

    /**
     * The title column of the key configuration, which neither family reads directly any more.
     */
    private const string COLUMN_TITLE = 'Column title';

    /**
     * The enriched field definition of the payload carries a title of its own; neither family reads it.
     */
    public function testBothFamiliesReportTheResolvedLabel(): void
    {
        $labelResolver = $this->createLabelResolver(self::RESOLVED_LABEL);

        $this->assertSame(self::RESOLVED_LABEL, $this->layoutTitle($labelResolver));
        $this->assertSame(self::RESOLVED_LABEL, $this->objectLayoutTitle($labelResolver));
    }

    /**
     * The one sanctioned divergence: the layout endpoints report the resolved value verbatim, so the
     * configuration UI can tell "no title set" apart from "title equals name", while the object layout
     * falls back to the name rather than rendering a blank label.
     */
    public function testOnlyTheFallbackOfAResolvedNothingDiffers(): void
    {
        $labelResolver = $this->createLabelResolver(null);

        $this->assertNull($this->layoutTitle($labelResolver));
        $this->assertSame(self::KEY_NAME, $this->objectLayoutTitle($labelResolver));
    }

    private function layoutTitle(LabelResolverInterface $labelResolver): ?string
    {
        $service = new KeyGroupLayoutService(
            $this->createMock(ServiceResolverInterface::class),
            $this->createMock(KeyRelationResolverInterface::class),
            new ConcreteObjectResolver(),
            $this->createKeyConfigResolver(),
            $labelResolver,
            $this->createSecurityService(),
            $this->createMock(ToolResolverInterface::class),
        );

        return $service->getKeyTitle(self::KEY_ID);
    }

    private function objectLayoutTitle(LabelResolverInterface $labelResolver): mixed
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

        $enricher = new ActiveGroupTitleEnricher(
            $this->createKeyConfigResolver(),
            $labelResolver,
            $this->createSecurityService(),
            $this->createMock(ToolResolverInterface::class),
        );
        $enricher->enrich($fieldDefinition);

        return array_column($fieldDefinition->activeGroupDefinitions[self::GROUP_ID]['keys'], 'title')[0];
    }

    private function createLabelResolver(?string $resolvedLabel): LabelResolverInterface
    {
        $labelResolver = $this->createMock(LabelResolverInterface::class);
        $labelResolver
            ->method('getStoredTitle')
            ->willReturnCallback(
                function (KeyConfig $keyConfig, string $language) use ($resolvedLabel): ?string {
                    $this->assertSame(self::KEY_ID, $keyConfig->getId());
                    $this->assertSame(self::USER_LANGUAGE, $language);

                    return $resolvedLabel;
                }
            );

        return $labelResolver;
    }

    private function createKeyConfigResolver(): KeyConfigResolverInterface
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setId(self::KEY_ID);
        $keyConfig->setTitle(self::COLUMN_TITLE);

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        return $keyConfigResolver;
    }

    private function createSecurityService(): SecurityServiceInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->method('getLanguage')
            ->willReturn(self::USER_LANGUAGE);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService
            ->method('getCurrentUser')
            ->willReturn($user);

        return $securityService;
    }
}
