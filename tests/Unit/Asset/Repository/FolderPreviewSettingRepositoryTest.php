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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Repository;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Pimcore\Bundle\StudioBackendBundle\Asset\Repository\FolderPreviewSettingRepository;
use Pimcore\Bundle\StudioBackendBundle\Entity\Asset\FolderPreviewSetting;

final class FolderPreviewSettingRepositoryTest extends Unit
{
    public function testGetByUserAndFolderReturnsNullWhenMissing(): void
    {
        $doctrineRepo = $this->makeEmpty(EntityRepository::class, [
            'findOneBy' => null,
        ]);
        $em = $this->makeEmpty(EntityManagerInterface::class, [
            'getRepository' => $doctrineRepo,
        ]);

        $repo = new FolderPreviewSettingRepository($em);

        $this->assertNull($repo->getByUserAndFolder(7, 42));
    }

    public function testGetByUserAndFolderReturnsStoredEntity(): void
    {
        $setting = new FolderPreviewSetting();
        $setting->setUser(7);
        $setting->setAssetFolderId(42);
        $setting->setImageSize('large');

        $doctrineRepo = $this->makeEmpty(EntityRepository::class, [
            'findOneBy' => $setting,
        ]);
        $em = $this->makeEmpty(EntityManagerInterface::class, [
            'getRepository' => $doctrineRepo,
        ]);

        $repo = new FolderPreviewSettingRepository($em);

        $this->assertSame($setting, $repo->getByUserAndFolder(7, 42));
    }

    public function testSavePersistsAndFlushes(): void
    {
        $setting = new FolderPreviewSetting();
        $setting->setUser(7);
        $setting->setAssetFolderId(42);
        $setting->setImageSize('large');

        $em = $this->makeEmpty(EntityManagerInterface::class, [
            'persist' => Expected::once(),
            'flush' => Expected::once(),
        ]);

        $repo = new FolderPreviewSettingRepository($em);

        $this->assertSame($setting, $repo->save($setting));
    }
}
