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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Document\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStepInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\ExecutionEngine\AutomationAction\Messenger\Handler\CloneHandler;
use Pimcore\Bundle\StudioBackendBundle\Document\ExecutionEngine\AutomationAction\Messenger\Messages\CloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine\ElementReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Model\Document;
use Pimcore\Model\User;
use ReflectionProperty;

/**
 * @internal
 */
final class CloneHandlerTest extends Unit
{
    private const int ORIGINAL_ROOT_ID = 10;

    private const int NEW_ROOT_ID = 20;

    private const int SOURCE_ID = 11;

    private const int NEW_ID = 21;

    /**
     * @throws Exception
     */
    public function testRewritesReferencesOfTheCopiedRootAndChildrenAfterTheLastChild(): void
    {
        $rewrites = [];
        $referenceService = $this->createMock(ElementReferenceServiceInterface::class);
        $referenceService->method('rewriteReferencesWithExecutionEngine')->willReturnCallback(
            static function ($user, array $rewriteConfiguration, array $ids, string $type) use (&$rewrites): int {
                $rewrites[] = [$rewriteConfiguration, $ids, $type];

                return 2;
            }
        );

        // children copied in earlier steps are already in the context
        $handler = $this->createHandler($referenceService, [12 => 22]);
        $handler(new CloneMessage(1, 1));

        $this->assertSame(
            [[
                [self::ORIGINAL_ROOT_ID => self::NEW_ROOT_ID, 12 => 22, self::SOURCE_ID => self::NEW_ID],
                [self::NEW_ROOT_ID, 22, self::NEW_ID],
                'document',
            ]],
            $rewrites
        );
    }

    /**
     * @param array<int, int> $alreadyCopied
     *
     * @throws Exception
     */
    private function createHandler(ElementReferenceServiceInterface $referenceService, array $alreadyCopied): CloneHandler
    {
        $cloneService = $this->createMock(CloneServiceInterface::class);
        $cloneService->method('getNewCloneTarget')->willReturn($this->makeDocument(self::NEW_ROOT_ID));
        $cloneService->method('cloneDocument')->willReturn($this->makeDocument(self::NEW_ID));

        $elementService = $this->createMock(ElementServiceInterface::class);
        $elementService->method('getAllowedElementById')->willReturnCallback(
            fn (string $type, int $id): Document => $this->makeDocument($id)
        );

        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn(new User());

        $userTopicService = $this->createMock(UserTopicServiceInterface::class);
        $userTopicService->method('getUserTopic')->willReturn('topic');

        $handler = new CloneHandler(
            $cloneService,
            $referenceService,
            $elementService,
            $this->createMock(PublishServiceInterface::class),
            $userResolver,
            $userTopicService
        );
        $handler->setJobRunRepository($this->createMock(JobRunRepositoryInterface::class));
        $handler->setJobRunExtractor($this->createJobRunExtractor($alreadyCopied));

        return $handler;
    }

    /**
     * @param array<int, int> $alreadyCopied
     */
    private function createJobRunExtractor(array $alreadyCopied): JobRunExtractorInterface
    {
        $jobRun = new JobRun(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(1);
        $jobRun->setContext([EnvironmentVariables::REWRITE_CONFIGURATION->value => $alreadyCopied]);
        $jobRun->setJob(new Job(
            name: 'job',
            steps: [
                new JobStep('step', CloneMessage::class, '', []),
                new JobStep('step', CloneMessage::class, '', []),
            ],
            environmentData: [
                EnvironmentVariables::ORIGINAL_PARENT_ID->value => self::ORIGINAL_ROOT_ID,
                EnvironmentVariables::PARENT_ID->value => self::NEW_ROOT_ID,
                EnvironmentVariables::CLONE_DATA->value => [
                    'language' => 'fr',
                    'enableInheritance' => false,
                    'updateReferences' => true,
                ],
                EnvironmentVariables::REWRITE_CONFIGURATION->value => [self::ORIGINAL_ROOT_ID => self::NEW_ROOT_ID],
            ]
        ));
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        $jobStep = $this->createMock(JobStepInterface::class);
        $jobStep->method('getName')->willReturn('step');
        $jobStep->method('getConfig')->willReturn([CloneServiceInterface::DOCUMENT_TO_CLONE => self::SOURCE_ID]);

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->method('getJobRun')->willReturn($jobRun);
        $extractor->method('getJobStep')->willReturn($jobStep);

        return $extractor;
    }

    /**
     * @throws Exception
     */
    private function makeDocument(int $id): Document
    {
        return $this->makeEmpty(Document::class, ['getId' => $id]);
    }
}
