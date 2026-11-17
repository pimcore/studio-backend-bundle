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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Data\Adapter;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Adapter\VideoAdapter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Model\Asset\Video as VideoAsset;
use Pimcore\Model\DataObject\ClassDefinition\Data\Video as VideoDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Video;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class VideoAdapterTest extends Unit
{
    /**
     * Regression test: confirming the video settings dialog without a video id used to produce an
     * "empty" video object, which silently satisfied the mandatory check of a required video field.
     *
     * @throws Exception
     */
    public function testEmptyVideoIdResultsInNullSoMandatoryCheckFails(): void
    {
        $result = $this->getDataForSetter(['type' => 'youtube', 'data' => '']);

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testMissingDataKeyResultsInNull(): void
    {
        $result = $this->getDataForSetter(['type' => 'youtube', 'poster' => null, 'title' => 'My title']);

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testUnresolvableAssetResultsInNull(): void
    {
        $result = $this->getDataForSetter(['type' => 'asset', 'data' => ['id' => 404]]);

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testEmptyAssetDataResultsInNull(): void
    {
        $result = $this->getDataForSetter(['type' => 'asset', 'data' => null]);

        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testFilledVideoIdIsStillMappedToAVideoObject(): void
    {
        $result = $this->getDataForSetter([
            'type' => 'youtube',
            'data' => 'dQw4w9WgXcQ',
            'title' => 'My title',
            'description' => 'My description',
        ]);

        $this->assertNotNull($result);
        $this->assertSame('youtube', $result->getType());
        $this->assertSame('dQw4w9WgXcQ', $result->getData());
        $this->assertSame('My title', $result->getTitle());
        $this->assertSame('My description', $result->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testResolvableAssetIsStillMappedToAVideoObject(): void
    {
        $asset = $this->makeEmpty(VideoAsset::class);

        $result = $this->getDataForSetter(
            ['type' => 'asset', 'data' => ['id' => 5]],
            [5 => $asset]
        );

        $this->assertNotNull($result);
        $this->assertSame('asset', $result->getType());
        $this->assertSame($asset, $result->getData());
    }

    /**
     * @throws Exception
     */
    private function getDataForSetter(array $videoData, array $assets = []): ?Video
    {
        $adapter = new VideoAdapter(
            $this->makeEmpty(AssetResolverInterface::class, [
                'getById' => static fn (int $id) => $assets[$id] ?? null,
            ]),
            $this->makeEmpty(DataServiceInterface::class)
        );

        return $adapter->getDataForSetter(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(VideoDefinition::class, ['getName' => 'video']),
            'video',
            ['video' => $videoData],
            $this->makeEmpty(UserInterface::class)
        );
    }
}
