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
use Pimcore\Bundle\StaticResolverBundle\Lib\PimcoreResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Handler\ElementUsageReplaceHandler;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\ElementUsageReplaceMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementContextServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementUsageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class ElementUsageReplaceHandlerTest extends Unit
{
    private const int SOURCE_ID = 1;

    private const int TARGET_ID = 2;

    private const int ELEMENT_ID = 42;

    private bool $inStudioContext = false;

    /**
     * @throws Exception
     */
    public function testReplacesUsageInStudioElementContext(): void
    {
        $source = $this->makeEmpty(Concrete::class, ['getId' => self::SOURCE_ID]);
        $target = $this->makeEmpty(Concrete::class, ['getId' => self::TARGET_ID]);
        $element = $this->makeEmpty(Concrete::class, ['getId' => self::ELEMENT_ID]);
        $user = new User();

        $elementUsageService = $this->createMock(ElementUsageServiceInterface::class);
        $elementUsageService->method('getElementById')->willReturnMap([
            [ElementTypes::TYPE_OBJECT, self::SOURCE_ID, $source],
            [ElementTypes::TYPE_OBJECT, self::TARGET_ID, $target],
            [ElementTypes::TYPE_OBJECT, self::ELEMENT_ID, $element],
        ]);
        $elementUsageService->expects($this->once())
            ->method('replaceElementUsage')
            ->with($source, $target, $element, $user)
            ->willReturnCallback(function (): void {
                $this->assertTrue(
                    $this->inStudioContext,
                    'The usage must be replaced in the Studio element context, not the CLI one'
                );
            });

        $handler = $this->createHandler($elementUsageService, $this->createElementContextService(), $user);

        $handler(new ElementUsageReplaceMessage(1, 0));
    }

    private function createElementContextService(): ElementContextServiceInterface
    {
        $elementContextService = $this->createMock(ElementContextServiceInterface::class);
        $elementContextService->expects($this->once())
            ->method('runInStudioContext')
            ->willReturnCallback(function (callable $callback): void {
                $this->inStudioContext = true;
                $callback();
                $this->inStudioContext = false;
            });

        return $elementContextService;
    }

    /**
     * @throws Exception
     */
    private function createHandler(
        ElementUsageServiceInterface $elementUsageService,
        ElementContextServiceInterface $elementContextService,
        User $user
    ): ElementUsageReplaceHandler {
        $jobStep = new JobStep('replace', ElementUsageReplaceMessage::class, '', [
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_ID => self::SOURCE_ID,
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_SOURCE_TYPE => ElementTypes::TYPE_OBJECT,
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_ID => self::TARGET_ID,
            ElementUsageServiceInterface::REPLACE_ELEMENT_USAGE_TARGET_TYPE => ElementTypes::TYPE_OBJECT,
        ]);
        $jobRun = new JobRun(1);
        $jobRun->setId(1);
        $jobRun->setState(JobRunStates::RUNNING);
        $jobRun->setCurrentStep(0);
        $jobRun->setJob(new Job(
            'replace',
            [$jobStep],
            [new ElementDescriptor(ElementTypes::TYPE_OBJECT, self::ELEMENT_ID)]
        ));

        $handler = new ElementUsageReplaceHandler(
            $elementUsageService,
            $this->makeEmpty(PimcoreResolverInterface::class),
            $this->makeEmpty(UserResolverInterface::class, ['getById' => $user]),
            $this->makeEmpty(PublishServiceInterface::class),
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
