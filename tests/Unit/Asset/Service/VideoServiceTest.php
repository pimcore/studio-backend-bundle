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
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Video\VideoThumbnailStatus;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\VideoService;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\VideoServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Video;
use Pimcore\Model\Asset\Video\Thumbnail\Config;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class VideoServiceTest extends Unit
{
    private const string THUMBNAIL_NAME = 'content';

    public function testGetThumbnailStatusWithWrongElementType(): void
    {
        $this->expectException(InvalidElementTypeException::class);

        $this->getVideoService()->getThumbnailStatus(new Document(), self::THUMBNAIL_NAME);
    }

    /**
     * @throws Exception
     */
    public function testGetThumbnailStatusReturnsStatusFromCustomSetting(): void
    {
        $video = $this->makeEmpty(Video::class, [
            'getCustomSetting' => [
                self::THUMBNAIL_NAME => [
                    'status' => VideoThumbnailStatus::STATUS_ERROR,
                ],
            ],
        ]);

        $status = $this->getVideoService()->getThumbnailStatus($video, self::THUMBNAIL_NAME);

        $this->assertSame(VideoThumbnailStatus::STATUS_ERROR, $status->getStatus());
    }

    /**
     * @throws Exception
     */
    public function testGetThumbnailStatusReturnsNotStartedWithoutCustomSetting(): void
    {
        $video = $this->makeEmpty(Video::class, [
            'getCustomSetting' => null,
        ]);

        $status = $this->getVideoService()->getThumbnailStatus($video, self::THUMBNAIL_NAME);

        $this->assertSame(VideoThumbnailStatus::STATUS_NOT_STARTED, $status->getStatus());
    }

    /**
     * @throws Exception
     */
    public function testGetThumbnailStatusReturnsNotStartedForUnknownThumbnail(): void
    {
        $video = $this->makeEmpty(Video::class, [
            'getCustomSetting' => [
                'other-thumbnail' => [
                    'status' => VideoThumbnailStatus::STATUS_FINISHED,
                ],
            ],
        ]);

        $status = $this->getVideoService()->getThumbnailStatus($video, self::THUMBNAIL_NAME);

        $this->assertSame(VideoThumbnailStatus::STATUS_NOT_STARTED, $status->getStatus());
    }

    private function getVideoService(): VideoServiceInterface
    {
        $config = new Config();
        $config->setName(self::THUMBNAIL_NAME);

        return new VideoService(
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(ThumbnailServiceInterface::class, [
                'getVideoThumbnailConfig' => $config,
            ]),
        );
    }
}
