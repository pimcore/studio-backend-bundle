<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\CustomLayout\CustomLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\DownloadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LayoutServiceInterface;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout as CoreLayout;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The editor collection route only requires the 'objects' permission
 * (pimcore/platform-version#597), so the service has to enforce access on its own.
 *
 * @internal
 */
final class CustomLayoutServiceEditorCollectionTest extends Unit
{
    private const OBJECT_ID = 42;

    private const CLASS_ID = 'CAR';

    /**
     * @throws Exception
     */
    public function testUserWithoutViewPermissionOnObjectIsRejected(): void
    {
        $user = $this->createUser(false);

        $dataObjectService = $this->createMock(DataObjectServiceInterface::class);
        $dataObjectService->method('getDataObjectElement')
            ->with($user, self::OBJECT_ID)
            ->willThrowException(new ForbiddenException('You dont have view permission'));

        $repository = $this->createMock(CustomLayoutRepositoryInterface::class);
        $repository->expects($this->never())->method('getCustomLayoutsByClass');

        $service = $this->createService($dataObjectService, $repository);

        $this->expectException(ForbiddenException::class);

        $service->getCustomLayoutEditorCollection(self::OBJECT_ID, $user);
    }

    /**
     * @throws Exception
     */
    public function testNonAdminOnlyGetsAllowedLayouts(): void
    {
        $user = $this->createUser(false);
        $service = $this->createServiceForObject(
            $user,
            ['layout_a', 'layout_b', 'layout_c'],
            allowedLayouts: ['layout_b']
        );

        $result = $service->getCustomLayoutEditorCollection(self::OBJECT_ID, $user);

        // The main layout ('0') is not part of the allowed layouts, so it must not be added either.
        $this->assertSame(['layout_b'], $this->getIds($result));
    }

    /**
     * @throws Exception
     */
    public function testNonAdminWithoutRestrictionGetsMainAndClassLayouts(): void
    {
        $user = $this->createUser(false);
        $service = $this->createServiceForObject($user, ['layout_a', 'layout_b']);

        $result = $service->getCustomLayoutEditorCollection(self::OBJECT_ID, $user);

        // Sorted by name: layout_a, layout_b, main ('0').
        $this->assertSame(['layout_a', 'layout_b', '0'], $this->getIds($result));
    }

    /**
     * @throws Exception
     */
    public function testAdminAdditionallyGetsAdminMainLayout(): void
    {
        $user = $this->createUser(true);
        $service = $this->createServiceForObject($user, ['layout_a']);

        $result = $service->getCustomLayoutEditorCollection(self::OBJECT_ID, $user);

        // Sorted by name: layout_a, main ('0'), main_admin ('-1').
        $this->assertSame(['layout_a', '0', '-1'], $this->getIds($result));
    }

    /**
     * @param string[] $layoutIds
     * @param string[] $allowedLayouts
     *
     * @throws Exception
     */
    private function createServiceForObject(
        UserInterface $user,
        array $layoutIds,
        array $allowedLayouts = []
    ): CustomLayoutService {
        $dataObject = $this->createMock(DataObject::class);
        $dataObject->method('getClassId')->willReturn(self::CLASS_ID);

        $dataObjectService = $this->createMock(DataObjectServiceInterface::class);
        $dataObjectService->method('getDataObjectElement')
            ->with($user, self::OBJECT_ID)
            ->willReturn($dataObject);

        $repository = $this->createMock(CustomLayoutRepositoryInterface::class);
        $repository->method('getCustomLayoutsByClass')
            ->with([self::CLASS_ID])
            ->willReturn(array_map(
                static fn (string $id): CoreLayout => (new CoreLayout())->setId($id)->setName($id),
                $layoutIds
            ));

        $layoutService = $this->createMock(LayoutServiceInterface::class);
        $layoutService->method('getUserAllowedLayoutsByClass')->willReturn($allowedLayouts);

        return $this->createService($dataObjectService, $repository, $layoutService);
    }

    /**
     * @throws Exception
     */
    private function createService(
        DataObjectServiceInterface $dataObjectService,
        CustomLayoutRepositoryInterface $repository,
        ?LayoutServiceInterface $layoutService = null
    ): CustomLayoutService {
        $hydrator = $this->createMock(CustomLayoutHydratorInterface::class);
        $hydrator->method('hydrateCompactLayout')->willReturnCallback(
            static fn (CoreLayout $layout): CustomLayoutCompact => new CustomLayoutCompact(
                $layout->getId(),
                $layout->getName(),
                $layout->getDefault()
            )
        );

        return new CustomLayoutService(
            $repository,
            $hydrator,
            $dataObjectService,
            $this->createMock(DownloadServiceInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $layoutService ?? $this->createMock(LayoutServiceInterface::class)
        );
    }

    /**
     * @throws Exception
     */
    private function createUser(bool $isAdmin): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('isAdmin')->willReturn($isAdmin);

        return $user;
    }

    /**
     * @param CustomLayoutCompact[] $layouts
     *
     * @return string[]
     */
    private function getIds(array $layouts): array
    {
        return array_map(static fn (CustomLayoutCompact $layout): string => $layout->getId(), $layouts);
    }
}
