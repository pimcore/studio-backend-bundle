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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Perspective\Repository;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\Search\LocateInTreeServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\PathServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\TreeQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\TreeLevelData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Model\WidgetElementData;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\ElementTreeWidgetRepository;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\ElementTreeWidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_keys;
use function array_map;

/**
 * @internal
 */
final class ElementTreeWidgetRepositoryTest extends Unit
{
    private const ELEMENT_ID = 7221;

    private const IDS_BY_PATH = [
        '/' => 1,
        '/some-folder' => 4758,
        '/some-folder/child' => 7219,
        '/some-folder/child/leaf' => 7220,
    ];

    /**
     * The tree levels between the widget root and the requested element are only navigated through,
     * so they must be resolved without a view permission check - a user with a workspace on a
     * descendant has no permissions on its ancestors, not even on the root.
     *
     * @throws Exception
     */
    public function testTreeLevelsAreResolvedWithoutPermissionCheck(): void
    {
        $repository = $this->createRepository($navigatedPaths);

        $treeLevelData = $repository->getTreeLevelData(
            $this->createWidgetElementData(),
            $this->makeEmpty(SearchIndexFilterInterface::class),
            $this->makeUser()
        );

        $this->assertSame(
            ['/', '/some-folder', '/some-folder/child', '/some-folder/child/leaf'],
            $navigatedPaths
        );
        $this->assertSame(
            [[1, 4758], [4758, 7219], [7219, 7220], [7220, self::ELEMENT_ID]],
            $this->flatten($treeLevelData)
        );
    }

    /**
     * @throws Exception
     */
    public function testTreeLevelsStartAtTheWidgetRootFolder(): void
    {
        $repository = $this->createRepository($navigatedPaths);

        $treeLevelData = $repository->getTreeLevelData(
            $this->createWidgetElementData('/some-folder/child'),
            $this->makeEmpty(SearchIndexFilterInterface::class),
            $this->makeUser()
        );

        $this->assertSame(['/some-folder/child', '/some-folder/child/leaf'], $navigatedPaths);
        $this->assertSame([[7219, 7220], [7220, self::ELEMENT_ID]], $this->flatten($treeLevelData));
    }

    /**
     * @param array<int, string>|null $navigatedPaths paths resolved as a tree level
     *
     * @throws Exception
     */
    private function createRepository(?array &$navigatedPaths = null): ElementTreeWidgetRepository
    {
        $navigatedPaths = [];

        $elementService = $this->makeEmpty(ElementServiceInterface::class, [
            'getNavigableElementByPath' => function (
                string $elementType,
                string $elementPath
            ) use (&$navigatedPaths): ElementInterface {
                $navigatedPaths[] = $elementPath;

                return $this->makeEmpty(ElementInterface::class, [
                    'getId' => self::IDS_BY_PATH[$elementPath],
                ]);
            },
            'getAllowedElementByPath' => function (): ElementInterface {
                $this->fail('Tree levels must not be resolved with a permission check.');
            },
        ]);

        $pathService = $this->makeEmpty(PathServiceInterface::class, [
            'getAllParentPaths' => array_keys(self::IDS_BY_PATH),
        ]);

        $treeQuery = $this->makeEmpty(TreeQueryInterface::class, [
            'get' => $this->makeEmpty(QueryInterface::class, [
                'getSearch' => $this->makeEmpty(SearchInterface::class),
            ]),
        ]);

        return new ElementTreeWidgetRepository(
            $elementService,
            $this->makeEmpty(ElementSearchServiceInterface::class),
            $this->makeEmpty(LocateInTreeServiceInterface::class, ['getPageNumber' => 1]),
            $pathService,
            $treeQuery
        );
    }

    /**
     * @throws Exception
     */
    private function createWidgetElementData(string $rootPath = '/'): WidgetElementData
    {
        $widget = new ElementTreeWidgetConfig(
            'studio_data_object_tree_widget',
            'Objects',
            [],
            new ElementIcon(ElementIconTypes::NAME->value, 'folder'),
            ElementTypes::TYPE_DATA_OBJECT,
            new RelatedElementData(
                self::IDS_BY_PATH[$rootPath],
                ElementTypes::TYPE_OBJECT,
                ElementTypes::TYPE_FOLDER,
                $rootPath,
                null
            )
        );

        return new WidgetElementData(
            $widget,
            $this->makeEmpty(ElementSearchResultItemInterface::class, [
                'getId' => self::ELEMENT_ID,
                'getFullPath' => '/some-folder/child/leaf/element',
            ])
        );
    }

    /**
     * @param TreeLevelData[] $treeLevelData
     *
     * @return array<int, array<int, int>>
     */
    private function flatten(array $treeLevelData): array
    {
        return array_map(
            static fn (TreeLevelData $data) => [$data->getParentId(), $data->getElementId()],
            $treeLevelData
        );
    }

    /**
     * @throws Exception
     */
    private function makeUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 42]);
    }
}
