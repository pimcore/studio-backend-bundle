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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Perspective\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Hydrator\ElementTreeWidgetConfigHydrator;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Widget\TreeContextPermissionsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Regression coverage for PEES-2058: a custom-perspective element-tree widget rooted at a folder
 * the viewing user cannot directly see (but can navigate through to a permitted descendant) must
 * keep resolving to its configured root instead of silently disappearing from the perspective.
 *
 * @internal
 */
final class ElementTreeWidgetConfigHydratorTest extends Unit
{
    public function testResolveCustomRootFolderDoesNotRequireViewPermissionOnTheRootItself(): void
    {
        $rootPath = '/Products/Chucking Equipment';
        $folder = $this->makeEmpty(ElementInterface::class);
        $relatedElementData = new RelatedElementData(101242, ElementTypes::TYPE_OBJECT, 'folder', $rootPath);

        $elementService = $this->makeEmpty(ElementServiceInterface::class, [
            'getElementIdByPath' => 101242,
            'getElementById' => $folder,
            // A direct permission check on the configured root would throw for this user -
            // asserting it is never called is the point of this test.
            'getAllowedElementByPath' => function () {
                throw new ForbiddenException('You dont have VIEW permission');
            },
        ]);

        $hydrator = $this->createHydrator($elementService, [
            'getRelatedElementData' => $relatedElementData,
        ]);

        $config = $hydrator->hydrate($this->widgetData($rootPath));

        $this->assertSame($rootPath, $config->getRootFolder()->getFullPath());
        $this->assertSame(101242, $config->getRootFolder()->getId());
    }

    /**
     * The element data service reports "no view access" for a root the user can only navigate through.
     * Its path still has to reach the widget: the tree is scoped by it (TreeQuery, parent path filter).
     */
    public function testCustomRootFolderKeepsItsPathWithoutViewAccess(): void
    {
        $rootPath = '/Products/Chucking Equipment';
        $elementService = $this->makeEmpty(ElementServiceInterface::class, [
            'getElementIdByPath' => 101242,
            'getElementById' => $this->makeEmpty(ElementInterface::class),
        ]);

        $hydrator = $this->createHydrator($elementService, [
            'getRelatedElementData' => new RelatedElementData(
                101242,
                ElementTypes::TYPE_OBJECT,
                'folder',
                $rootPath,
                null,
                false
            ),
        ]);

        $rootFolder = $hydrator->hydrate($this->widgetData($rootPath))->getRootFolder();

        $this->assertFalse($rootFolder->getHasViewAccess());
        $this->assertSame($rootPath, $rootFolder->getFullPath());
        $this->assertSame('folder', $rootFolder->getSubtype());
    }

    public function testResolveCustomRootFolderFallsBackToDefaultRootWhenPathIsGone(): void
    {
        $throwNotFound = function () {
            throw new NotFoundException(ElementTypes::TYPE_OBJECT, '/deleted-folder', 'path');
        };
        $elementService = $this->makeEmpty(ElementServiceInterface::class, [
            'getElementIdByPath' => $throwNotFound,
            'getAllowedElementByPath' => $throwNotFound,
        ]);

        $hydrator = $this->createHydrator($elementService);

        $config = $hydrator->hydrate($this->widgetData('/deleted-folder'));

        $this->assertSame('/', $config->getRootFolder()->getFullPath());
        $this->assertSame(1, $config->getRootFolder()->getId());
    }

    private function createHydrator(
        ElementServiceInterface $elementService,
        array $elementDataServiceMethods = []
    ): ElementTreeWidgetConfigHydrator {
        return new ElementTreeWidgetConfigHydrator(
            $this->makeEmpty(TreeContextPermissionsServiceInterface::class, ['list' => []]),
            $this->makeEmpty(ElementDataServiceInterface::class, $elementDataServiceMethods),
            $elementService,
            $this->makeEmpty(IconServiceInterface::class, [
                'getIconForValue' => new ElementIcon(ElementIconTypes::NAME->value, 'folder'),
            ]),
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ]),
        );
    }

    private function widgetData(string $rootFolder): array
    {
        return [
            'id' => 'widget-1',
            'name' => 'Products',
            'contextPermissions' => [],
            'icon' => null,
            'elementType' => ElementTypes::TYPE_DATA_OBJECT,
            'rootFolder' => $rootFolder,
            'showRoot' => false,
            'classes' => [],
            'pql' => null,
            'pageSize' => null,
            'isWriteable' => true,
        ];
    }
}
