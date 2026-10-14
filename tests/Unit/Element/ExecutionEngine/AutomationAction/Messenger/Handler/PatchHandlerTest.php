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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\ExecutionEngine\AutomationAction\Messenger\Handler;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler\PatchHandler;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\PatchMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementContextServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class PatchHandlerTest extends Unit
{
    private const int ELEMENT_ID = 42;

    private bool $inStudioContext = false;

    /**
     * @throws Exception
     */
    public function testPatchesElementInStudioElementContext(): void
    {
        $patchData = [
            'id' => self::ELEMENT_ID,
            'editableData' => ['relation' => ['action' => 'add', 'data' => [['id' => 7, 'type' => 'object']]]],
        ];
        $element = $this->makeEmpty(Concrete::class, ['getId' => self::ELEMENT_ID]);
        $user = new User();

        $patchService = $this->createMock(PatchServiceInterface::class);
        $patchService->expects($this->once())
            ->method('patchElement')
            ->with($element, ElementTypes::TYPE_OBJECT, $patchData, $user)
            ->willReturnCallback(function (): void {
                $this->assertTrue(
                    $this->inStudioContext,
                    'The element must be patched in the Studio element context, not the CLI one'
                );
            });

        $elementContextService = $this->createMock(ElementContextServiceInterface::class);
        $elementContextService->expects($this->once())
            ->method('runInStudioContext')
            ->willReturnCallback(function (callable $callback): void {
                $this->inStudioContext = true;
                $callback();
                $this->inStudioContext = false;
            });

        $handler = $this->createHandler($patchService, $elementContextService, $element, $user, $patchData);

        $handler(new PatchMessage(1, 0, new ElementDescriptor(ElementTypes::TYPE_OBJECT, self::ELEMENT_ID)));
    }

    /**
     * @throws Exception
     */
    private function createHandler(
        PatchServiceInterface $patchService,
        ElementContextServiceInterface $elementContextService,
        Concrete $element,
        User $user,
        array $patchData
    ): PatchHandler {
        $jobStep = new JobStep('patch', PatchMessage::class, '', []);
        $jobRun = new JobRun(1);
        $jobRun->setId(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setTotalElements(1);
        $jobRun->setJob(new Job('patch', [$jobStep], [], [self::ELEMENT_ID => $patchData]));

        $jobRunExtractor = $this->makeEmpty(JobRunExtractorInterface::class, [
            'getJobRun' => $jobRun,
            'getJobStep' => $jobStep,
        ]);

        $handler = new PatchHandler(
            $patchService,
            $this->makeEmpty(PublishServiceInterface::class),
            $this->makeEmpty(ElementServiceInterface::class, ['getAllowedElementById' => $element]),
            $this->makeEmpty(UserResolverInterface::class, ['getById' => $user]),
            $this->makeEmpty(UserTopicServiceInterface::class, ['getUserTopic' => 'topic']),
            $elementContextService,
        );
        $handler->setJobRunExtractor($jobRunExtractor);
        $handler->setJobRunRepository($this->makeEmpty(JobRunRepositoryInterface::class));
        $handler->setTranslator($this->makeEmpty(TranslatorInterface::class, [
            'trans' => static fn (string $id, array $parameters = []) => $id . ' ' . json_encode($parameters),
        ]));

        return $handler;
    }
}
