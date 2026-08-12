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

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadInfoService;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadInfoServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UploadInfoServiceTest extends Unit
{
    private const string PARENT_PATH = '/parent-folder';

    private const int PARENT_ID = 5;

    private const int EXISTING_ASSET_ID = 83;

    /**
     * @throws Exception
     */
    public function testFilesExistReturnsOneEntryPerNameInRequestOrder(): void
    {
        $result = $this->getUploadInfoService(['taken.jpg'])->filesExist(
            self::PARENT_ID,
            ['free-a.jpg', 'taken.jpg', 'free-b.jpg'],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertCount(3, $result);
        $this->assertSame(
            ['free-a.jpg', 'taken.jpg', 'free-b.jpg'],
            array_map(static fn ($info) => $info->getFileName(), $result)
        );
        $this->assertSame([false, true, false], array_map(static fn ($info) => $info->isExists(), $result));
    }

    /**
     * @throws Exception
     */
    public function testFilesExistReturnsAssetIdForAnExistingName(): void
    {
        $result = $this->getUploadInfoService(['taken.jpg'])->filesExist(
            self::PARENT_ID,
            ['taken.jpg'],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertTrue($result[0]->isExists());
        $this->assertSame(self::EXISTING_ASSET_ID, $result[0]->getAssetId());
        $this->assertFalse($result[0]->isAccessDenied());
    }

    /**
     * @throws Exception
     */
    public function testFilesExistReportsNoAssetIdForAFreeName(): void
    {
        $result = $this->getUploadInfoService([])->filesExist(
            self::PARENT_ID,
            ['free.jpg'],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertFalse($result[0]->isExists());
        $this->assertNull($result[0]->getAssetId());
        $this->assertFalse($result[0]->isAccessDenied());
    }

    /**
     * A name taken by an asset the user may not view must not abort the request,
     * otherwise one denied file would take the rest of the batch down with it.
     *
     * @throws Exception
     */
    public function testFilesExistReportsDeniedNameWithoutFailingTheBatch(): void
    {
        $result = $this->getUploadInfoService(['taken.jpg', 'denied.jpg'], ['denied.jpg'])->filesExist(
            self::PARENT_ID,
            ['denied.jpg', 'taken.jpg'],
            $this->makeEmpty(UserInterface::class)
        );

        $this->assertCount(2, $result);

        $this->assertTrue($result[0]->isAccessDenied());
        $this->assertFalse($result[0]->isExists());
        $this->assertNull($result[0]->getAssetId());

        $this->assertFalse($result[1]->isAccessDenied());
        $this->assertTrue($result[1]->isExists());
        $this->assertSame(self::EXISTING_ASSET_ID, $result[1]->getAssetId());
    }

    /**
     * @param array<string> $existingNames names that resolve as already taken
     * @param array<string> $deniedNames   names the user may not view
     *
     * @throws Exception
     */
    private function getUploadInfoService(array $existingNames, array $deniedNames = []): UploadInfoServiceInterface
    {
        $existingPaths = array_map(static fn (string $n) => self::PARENT_PATH . '/' . $n, $existingNames);
        $deniedPaths = array_map(static fn (string $n) => self::PARENT_PATH . '/' . $n, $deniedNames);

        $parent = $this->makeEmpty(Folder::class, [
            'getRealFullPath' => self::PARENT_PATH,
        ]);

        $existingAsset = $this->makeEmpty(Image::class, [
            'getId' => self::EXISTING_ASSET_ID,
        ]);

        return new UploadInfoService(
            $this->makeEmpty(AssetServiceInterface::class, [
                'getAssetElement' => $parent,
                'getAssetElementByPath' => static function (
                    UserInterface $user,
                    string $path
                ) use ($deniedPaths, $existingAsset) {
                    if (in_array($path, $deniedPaths, true)) {
                        throw new ForbiddenException();
                    }

                    return $existingAsset;
                },
            ]),
            $this->makeEmpty(AssetServiceResolverInterface::class, [
                'pathExists' => static fn (string $path) => in_array($path, $existingPaths, true),
            ]),
        );
    }
}
