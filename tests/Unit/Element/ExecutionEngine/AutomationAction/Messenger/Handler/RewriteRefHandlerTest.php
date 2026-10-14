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
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler\RewriteRefHandler;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\RewriteRefMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementContextServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine\ElementReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class RewriteRefHandlerTest extends Unit
{
    private const int ELEMENT_ID = 42;

    private const array REWRITE_CONFIGURATION = ['object' => [1 => 2]];

    private bool $inStudioContext = false;

    /**
     * @throws Exception
     */
    public function testRewritesReferencesInStudioElementContext(): void
    {
        $element = $this->makeEmpty(Concrete::class, ['getId' => self::ELEMENT_ID]);
        $user = new User();

        $elementReferenceService = $this->createMock(ElementReferenceServiceInterface::class);
        $elementReferenceService->expects($this->once())
            ->method('rewriteElementReferences')
            ->with($user, $element, self::REWRITE_CONFIGURATION, [])
            ->willReturnCallback(function (): void {
                $this->assertTrue(
                    $this->inStudioContext,
                    'References must be rewritten in the Studio element context, not the CLI one'
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

        $handler = $this->createHandler($elementReferenceService, $elementContextService, $element, $user);

        $handler(new RewriteRefMessage(1, 0));
    }

    /**
     * @throws Exception
     */
    private function createHandler(
        ElementReferenceServiceInterface $elementReferenceService,
        ElementContextServiceInterface $elementContextService,
        Concrete $element,
        User $user
    ): RewriteRefHandler {
        $jobStep = new JobStep('rewrite', RewriteRefMessage::class, '', [
            StepConfig::ELEMENTS_TO_REWRITE_REFERENCES->value => [self::ELEMENT_ID],
            StepConfig::ELEMENT_TYPE_TO_REWRITE_REFERENCES->value => ElementTypes::TYPE_OBJECT,
        ]);
        $jobRun = new JobRun(1);
        $jobRun->setId(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setJob(new Job('rewrite', [$jobStep], [], [
            EnvironmentVariables::REWRITE_CONFIGURATION->value => self::REWRITE_CONFIGURATION,
            EnvironmentVariables::REWRITE_PARAMETERS->value => [],
        ]));

        $handler = new RewriteRefHandler(
            $elementReferenceService,
            $this->makeEmpty(ElementServiceInterface::class, ['getAllowedElementById' => $element]),
            $this->makeEmpty(PublishServiceInterface::class),
            $this->makeEmpty(UserResolverInterface::class, ['getById' => $user]),
            $this->makeEmpty(UserTopicServiceInterface::class, ['getUserTopic' => 'topic']),
            $elementContextService,
        );
        $handler->setJobRunExtractor($this->makeEmpty(JobRunExtractorInterface::class, [
            'getJobRun' => $jobRun,
            'getJobStep' => $jobStep,
        ]));
        $handler->setJobRunRepository($this->makeEmpty(JobRunRepositoryInterface::class));
        $handler->setTranslator($this->makeEmpty(TranslatorInterface::class, [
            'trans' => static fn (string $id, array $parameters = []) => $id . ' ' . json_encode($parameters),
        ]));

        return $handler;
    }
}
