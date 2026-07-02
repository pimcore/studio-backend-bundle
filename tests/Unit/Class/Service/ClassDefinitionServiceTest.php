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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ClassDefinitionListHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ClassDefinitionList;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassDefinitionService;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition as CoreClassDefinition;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function in_array;

/**
 * @internal
 */
final class ClassDefinitionServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetCollectionReturnsAll(): void
    {
        $service = $this->createService(
            classDefinitions: $this->createCoreClassDefinitions(['Car', 'News', 'Event']),
        );

        $result = $service->getClassDefinitionCollection();

        $this->assertCount(3, $result);
        $this->assertResultContainsIds(['Car', 'News', 'Event'], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionFiltersByWidgetClasses(): void
    {
        $widget = $this->createElementTreeWidget(['Car', 'Event']);

        $service = $this->createService(
            classDefinitions: $this->createCoreClassDefinitions(['Car', 'News', 'Event']),
            widgetConfig: $widget,
        );

        $result = $service->getClassDefinitionCollection(widgetId: 'test_widget');

        $this->assertCount(2, $result);
        $this->assertResultContainsIds(['Car', 'Event'], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionWithEmptyWidgets(): void
    {
        $widget = $this->createElementTreeWidget([]);

        $service = $this->createService(
            classDefinitions: $this->createCoreClassDefinitions(['Car', 'News']),
            widgetConfig: $widget,
        );

        $result = $service->getClassDefinitionCollection(widgetId: 'test_widget');

        $this->assertCount(2, $result);
        $this->assertResultContainsIds(['Car', 'News'], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionCombine(): void
    {
        $widget = $this->createElementTreeWidget(['Car', 'News']);

        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAllowed' => static function (string $key, string $type = 'permission'): bool {
                if ($type !== ElementTypes::CLASS_TYPE) {
                    return false; // mirrors Pimcore\Model\User::isAllowed fallthrough for unknown types
                }

                return in_array($key, ['Car', 'Event'], true);
            },
        ]);

        $service = $this->createService(
            classDefinitions: $this->createCoreClassDefinitions(['Car', 'News', 'Event']),
            widgetConfig: $widget,
            currentUser: $currentUser,
        );

        $result = $service->getClassDefinitionCollection(creatableOnly: true, widgetId: 'test_widget');

        $this->assertCount(1, $result);
        $this->assertResultContainsIds(['Car'], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionWithNonElementTreeWidgetReturnsAll(): void
    {
        $icon = new ElementIcon(ElementIconTypes::PATH->value, '/icon.svg');
        $baseWidget = new WidgetConfig('test_widget', 'Test', 'some_other_type', $icon);

        $service = $this->createService(
            classDefinitions: $this->createCoreClassDefinitions(['Car', 'News']),
            widgetConfig: $baseWidget,
        );

        $result = $service->getClassDefinitionCollection(widgetId: 'test_widget');

        $this->assertCount(2, $result);
        $this->assertResultContainsIds(['Car', 'News'], $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionCreatableOnlyFiltersWithoutWidget(): void
    {
        $currentUser = $this->makeEmpty(UserInterface::class, [
            'isAllowed' => static function (string $key, string $type = 'permission'): bool {
                if ($type !== ElementTypes::CLASS_TYPE) {
                    return false; // mirrors Pimcore\Model\User::isAllowed fallthrough for unknown types
                }

                return $key === 'News';
            },
        ]);

        $service = $this->createService(
            classDefinitions: $this->createCoreClassDefinitions(['Car', 'News', 'Event']),
            currentUser: $currentUser,
        );

        $result = $service->getClassDefinitionCollection(creatableOnly: true);

        $this->assertCount(1, $result);
        $this->assertResultContainsIds(['News'], $result);
    }

    /**
     * @return CoreClassDefinition[]
     */
    private function createCoreClassDefinitions(array $ids): array
    {
        return array_map(static function (string $id): CoreClassDefinition {
            $cd = new CoreClassDefinition();
            $cd->setId($id);
            $cd->setName($id);

            return $cd;
        }, $ids);
    }

    private function createElementTreeWidget(array $classes): ElementTreeWidgetConfig
    {
        $icon = new ElementIcon(ElementIconTypes::PATH->value, '/icon.svg');

        return new ElementTreeWidgetConfig(
            'test_widget',
            'Test Widget',
            [],
            $icon,
            ElementTypes::TYPE_DATA_OBJECT,
            new RelatedElementData(
                1,
                ElementTypes::TYPE_OBJECT,
                ElementTypes::TYPE_FOLDER,
                '/',
                null
            ),
            false,
            $classes,
        );
    }

    /**
     * @throws Exception
     */
    private function createService(
        array $classDefinitions = [],
        ?WidgetConfig $widgetConfig = null,
        ?UserInterface $currentUser = null,
    ): ClassDefinitionService {
        $icon = new ElementIcon(ElementIconTypes::PATH->value, '/icon.svg');

        $listHydrator = $this->makeEmpty(ClassDefinitionListHydratorInterface::class, [
            'hydrate' => function (CoreClassDefinition $cd) use ($icon): ClassDefinitionList {
                return new ClassDefinitionList($cd->getId(), $cd->getName(), $cd->getName(), $icon);
            },
        ]);

        $widgetService = $this->makeEmpty(WidgetServiceInterface::class, [
            'getWidgetConfigData' => $widgetConfig,
        ]);

        return new ClassDefinitionService(
            $this->makeEmpty(ClassDefinitionRepositoryInterface::class, [
                'getClassDefinitions' => $classDefinitions,
            ]),
            $this->makeEmpty(ClassDefinitionHydratorInterface::class),
            $listHydrator,
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $currentUser ?? $this->makeEmpty(UserInterface::class),
            ]),
            $widgetService,
        );
    }

    /**
     * @param ClassDefinitionList[] $result
     */
    private function assertResultContainsIds(array $expectedIds, array $result): void
    {
        $resultIds = array_map(static fn (ClassDefinitionList $item): string => $item->getId(), $result);
        sort($expectedIds);
        sort($resultIds);
        $this->assertSame($expectedIds, $resultIds);
    }
}
