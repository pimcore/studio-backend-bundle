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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\FolderPreviewSettingEvent;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\FolderPreviewSettingHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Repository\FolderPreviewSettingRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\FolderPreviewSetting;
use Pimcore\Bundle\StudioBackendBundle\Entity\Asset\FolderPreviewSetting as FolderPreviewSettingEntity;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class FolderPreviewSettingService implements FolderPreviewSettingServiceInterface
{
    private const string DEFAULT_IMAGE_SIZE = 'small';

    public function __construct(
        private FolderPreviewSettingRepositoryInterface $repository,
        private FolderPreviewSettingHydratorInterface $hydrator,
        private SecurityServiceInterface $securityService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getImageSize(int $folderId): FolderPreviewSetting
    {
        $userId = $this->securityService->getCurrentUser()->getId();
        $entity = $this->repository->getByUserAndFolder($userId, $folderId);
        $imageSize = $entity?->getImageSize() ?? self::DEFAULT_IMAGE_SIZE;

        $folderPreviewSetting = $this->hydrator->hydrate($imageSize);

        $this->eventDispatcher->dispatch(
            new FolderPreviewSettingEvent($folderPreviewSetting),
            FolderPreviewSettingEvent::EVENT_NAME
        );

        return $folderPreviewSetting;
    }

    public function saveImageSize(int $folderId, string $imageSize): void
    {
        $userId = $this->securityService->getCurrentUser()->getId();
        $entity = $this->repository->getByUserAndFolder($userId, $folderId) ?? new FolderPreviewSettingEntity();
        $entity->setUser($userId);
        $entity->setAssetFolderId($folderId);
        $entity->setImageSize($imageSize);

        $this->repository->save($entity);
    }
}
