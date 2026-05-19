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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service\ExecutionEngine;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipService;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Asset;
use ReflectionMethod;
use ZipArchive;

/**
 * @internal
 */
final class ZipServiceTest extends Unit
{
    private string $zipPath = '';

    /** @var string[] */
    private array $tempFiles = [];

    protected function _after(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        if ($this->zipPath !== '' && file_exists($this->zipPath)) {
            unlink($this->zipPath);
        }
    }

    public function testSameFilenameInDifferentFoldersWithCommonBasePath(): void
    {
        $service = $this->createService();
        $archive = $this->createZipArchive();

        $asset1 = $this->createAssetMock('/parent/folder1/image.jpg');
        $asset2 = $this->createAssetMock('/parent/folder2/image.jpg');

        $service->addFile($archive, $asset1, '/parent');
        $service->addFile($archive, $asset2, '/parent');

        $entries = $this->getZipEntries($archive);

        $this->assertCount(2, $entries);
        $this->assertContains('folder1/image.jpg', $entries);
        $this->assertContains('folder2/image.jpg', $entries);
    }

    public function testRootBasePath(): void
    {
        $service = $this->createService();
        $archive = $this->createZipArchive();

        $asset = $this->createAssetMock('/some/deep/path/file.txt');

        $service->addFile($archive, $asset, '/');

        $entries = $this->getZipEntries($archive);

        $this->assertCount(1, $entries);
        $this->assertContains('some/deep/path/file.txt', $entries);
    }

    public function testDirectParentBasePath(): void
    {
        $service = $this->createService();
        $archive = $this->createZipArchive();

        $asset = $this->createAssetMock('/photos/vacation/beach.jpg');

        $service->addFile($archive, $asset, '/photos/vacation');

        $entries = $this->getZipEntries($archive);

        $this->assertCount(1, $entries);
        $this->assertContains('beach.jpg', $entries);
    }

    public function testDefaultBasePathBehavesAsRoot(): void
    {
        $service = $this->createService();
        $archive = $this->createZipArchive();

        $asset = $this->createAssetMock('/folder/file.txt');

        $service->addFile($archive, $asset);

        $entries = $this->getZipEntries($archive);

        $this->assertCount(1, $entries);
        $this->assertContains('folder/file.txt', $entries);
    }

    public function testResolveCommonBasePathSingleFolder(): void
    {
        $result = $this->invokeResolveCommonBasePath(['/photos/vacation']);
        $this->assertSame('/photos', $result);
    }

    public function testResolveCommonBasePathSingleRootChild(): void
    {
        $result = $this->invokeResolveCommonBasePath(['/assets']);
        $this->assertSame('/', $result);
    }

    public function testResolveCommonBasePathTwoSiblingFolders(): void
    {
        $result = $this->invokeResolveCommonBasePath(['/parent/folder1', '/parent/folder2']);
        $this->assertSame('/parent', $result);
    }

    public function testResolveCommonBasePathNestedFolders(): void
    {
        $result = $this->invokeResolveCommonBasePath(['/a/b/c', '/a/b/d']);
        $this->assertSame('/a/b', $result);
    }

    public function testResolveCommonBasePathNoCommonPrefix(): void
    {
        $result = $this->invokeResolveCommonBasePath(['/photos/vacation', '/documents/work']);
        $this->assertSame('/', $result);
    }

    public function testResolveCommonBasePathDeeplyNested(): void
    {
        $result = $this->invokeResolveCommonBasePath(['/a/b/c/d/e', '/a/b/c/d/f', '/a/b/c/x']);
        $this->assertSame('/a/b/c', $result);
    }

    /**
     * @param string[] $folderPaths
     */
    private function invokeResolveCommonBasePath(array $folderPaths): string
    {
        $service = $this->createService();
        $method = new ReflectionMethod(ZipService::class, 'resolveCommonBasePath');
        $method->setAccessible(true);

        return $method->invoke($service, $folderPaths);
    }

    private function createService(): ZipService
    {
        return new ZipService(
            $this->makeEmpty(AssetSearchServiceInterface::class),
            $this->makeEmpty(JobExecutionAgentInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(StorageServiceInterface::class),
            $this->makeEmpty(UploadServiceInterface::class),
            $this->makeEmpty(GridSearchInterface::class),
            [],
        );
    }

    private function createZipArchive(): ZipArchive
    {
        $this->zipPath = tempnam(sys_get_temp_dir(), 'zip_test_') . '.zip';
        $archive = new ZipArchive();
        $archive->open($this->zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        return $archive;
    }

    private function createAssetMock(string $realFullPath): Asset
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'asset_test_');
        file_put_contents($tempFile, 'test content');
        $this->tempFiles[] = $tempFile;

        return $this->makeEmpty(Asset::class, [
            'getLocalFile' => $tempFile,
            'getRealFullPath' => $realFullPath,
        ]);
    }

    /**
     * @return string[]
     */
    private function getZipEntries(ZipArchive $archive): array
    {
        $archive->close();

        $readArchive = new ZipArchive();
        $readArchive->open($this->zipPath, ZipArchive::RDONLY);

        $entries = [];
        for ($i = 0; $i < $readArchive->numFiles; $i++) {
            $entries[] = $readArchive->getNameIndex($i);
        }

        $readArchive->close();

        return $entries;
    }
}
