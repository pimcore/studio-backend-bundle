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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\FolderPreviewSettingHydrator;
use Pimcore\Bundle\StudioBackendBundle\Asset\Repository\FolderPreviewSettingRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\FolderPreviewSettingService;
use Pimcore\Bundle\StudioBackendBundle\Entity\Asset\FolderPreviewSetting as FolderPreviewSettingEntity;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FolderPreviewSettingServiceTest extends Unit
{
    private function securityFor(int $userId): SecurityServiceInterface
    {
        $user = $this->makeEmpty(UserInterface::class, ['getId' => $userId]);

        return $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]);
    }

    private function dispatcher(): EventDispatcherInterface
    {
        return $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => static fn ($event) => $event,
        ]);
    }

    public function testGetImageSizeReturnsDefaultWhenMissing(): void
    {
        $repository = $this->makeEmpty(FolderPreviewSettingRepositoryInterface::class, [
            'getByUserAndFolder' => null,
        ]);

        $service = new FolderPreviewSettingService(
            $repository,
            new FolderPreviewSettingHydrator(),
            $this->securityFor(7),
            $this->dispatcher(),
        );

        $this->assertSame('small', $service->getImageSize(42)->getImageSize());
    }

    public function testGetImageSizeReturnsStoredValue(): void
    {
        $entity = new FolderPreviewSettingEntity();
        $entity->setUser(7);
        $entity->setAssetFolderId(42);
        $entity->setImageSize('large');

        $repository = $this->makeEmpty(FolderPreviewSettingRepositoryInterface::class, [
            'getByUserAndFolder' => $entity,
        ]);

        $service = new FolderPreviewSettingService(
            $repository,
            new FolderPreviewSettingHydrator(),
            $this->securityFor(7),
            $this->dispatcher(),
        );

        $this->assertSame('large', $service->getImageSize(42)->getImageSize());
    }

    public function testSaveImageSizeUpsert(): void
    {
        $repository = $this->makeEmpty(FolderPreviewSettingRepositoryInterface::class, [
            'getByUserAndFolder' => null,
            'save' => Expected::once(),
        ]);

        $service = new FolderPreviewSettingService(
            $repository,
            new FolderPreviewSettingHydrator(),
            $this->securityFor(7),
            $this->dispatcher(),
        );

        $service->saveImageSize(42, 'large');
    }
}
