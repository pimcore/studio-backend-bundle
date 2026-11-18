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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler\ZipDownloadHandler;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipDownloadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\User;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

/**
 * @internal
 */
final class ZipDownloadHandlerTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testLeavesOutAssetsWithMissingBinaryAndLogsEachOfThem(): void
    {
        $jobRun = $this->createJobRun();

        $extractor = $this->createJobRunExtractor($jobRun, [1, 2]);
        $extractor->expects($this->once())
            ->method('logMessageToJobRun')
            ->with(
                $jobRun,
                Config::ZIP_ASSET_BINARY_MISSING_MESSAGE->value,
                ['%id%' => 1, '%path%' => '/folder/asset-1.txt']
            );

        $zipService = $this->createZipService(['/folder/asset-1.txt' => false, '/folder/asset-2.txt' => true]);
        $zipService->expects($this->once())->method('copyZipFileToFlysystem');

        $this->createHandler($extractor, $zipService)(new ZipDownloadMessage(1, 0));
    }

    public function testAbortsWhenNoAssetBinaryCouldBeAdded(): void
    {
        $jobRun = $this->createJobRun();

        $extractor = $this->createJobRunExtractor($jobRun, [1, 2]);
        $extractor->expects($this->exactly(2))
            ->method('logMessageToJobRun')
            ->with($jobRun, Config::ZIP_ASSET_BINARY_MISSING_MESSAGE->value, $this->anything());

        $zipService = $this->createZipService(['/folder/asset-1.txt' => false, '/folder/asset-2.txt' => false]);
        // Without an added file ZipArchive writes no file, so the archive must not be copied
        $zipService->expects($this->never())->method('copyZipFileToFlysystem');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(Config::ZIP_NO_ASSET_BINARIES_FOUND_MESSAGE->value);

        $this->createHandler($extractor, $zipService)(new ZipDownloadMessage(1, 0));
    }

    /**
     * @param array<string, bool> $addResultByPath
     */
    private function createZipService(array $addResultByPath): ZipServiceInterface
    {
        $zipService = $this->createMock(ZipServiceInterface::class);
        $zipService->method('getTempFilePath')->willReturn('/tmp/pme_zip_download_handler_test.zip');
        $zipService->method('createLocalArchive')->willReturn($this->createMock(ZipArchive::class));
        $zipService->method('addFile')->willReturnCallback(
            static fn (ZipArchive $archive, Asset $asset): bool => $addResultByPath[$asset->getRealFullPath()]
        );

        return $zipService;
    }

    private function createHandler(
        JobRunExtractorInterface $extractor,
        ZipServiceInterface $zipService
    ): ZipDownloadHandler {
        $elementService = $this->createMock(ElementServiceInterface::class);
        $elementService->method('getAllowedElementById')->willReturnCallback(
            fn (string $type, int $id): Asset => $this->makeEmpty(Asset::class, [
                'getId' => $id,
                'getType' => 'text',
                'getRealFullPath' => '/folder/asset-' . $id . '.txt',
            ])
        );

        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn(new User());

        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $handler = new ZipDownloadHandler(
            $this->createMock(PublishServiceInterface::class),
            $elementService,
            $userResolver,
            $userTopicService,
            $zipService
        );
        $handler->setJobRunRepository($this->createMock(JobRunRepositoryInterface::class));
        $handler->setJobRunExtractor($extractor);
        $handler->setTranslator($translator);

        return $handler;
    }

    /**
     * @param int[] $assetIds
     */
    private function createJobRunExtractor(JobRun $jobRun, array $assetIds): JobRunExtractorInterface&MockObject
    {
        $jobStep = $this->createMock(JobStepInterface::class);
        $jobStep->method('getName')->willReturn('step');
        $jobStep->method('getConfig')->willReturn([
            ZipServiceInterface::ASSETS_TO_ZIP => $assetIds,
            ZipServiceInterface::ZIP_BASE_PATH => '/',
        ]);

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->method('getJobRun')->willReturn($jobRun);
        $extractor->method('getJobStep')->willReturn($jobStep);

        return $extractor;
    }

    private function createJobRun(): JobRun
    {
        $jobRun = new JobRun(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        return $jobRun;
    }
}
