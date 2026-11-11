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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ExecutionEngine\AutomationAction;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\ExecuteActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\User\Resolver\UserAccountResolverInterface;
use Pimcore\Model\User;
use Pimcore\Model\User\ServiceUser;

/**
 * @internal
 */
final class AbstractHandlerTest extends Unit
{
    public function testLoadsTheJobRunOnlyForValuesThatReferenceAnEnvironmentVariable(): void
    {
        $jobRun = new JobRun(1);
        $jobRun->setJob(new Job('job', [new JobStep('step', 'message', '', [])], [], ['path' => '/tmp/export']));

        $extractor = $this->createMock(JobRunExtractorInterface::class);
        $extractor->expects($this->once())->method('getJobRun')->willReturn($jobRun);

        $handler = new class() extends AbstractHandler {
            public function replace(GenericExecutionEngineMessageInterface $message, mixed $value): mixed
            {
                return $this->recursivelyReplaceConfigValuesWithEnvVariables($message, $value);
            }
        };
        $handler->setJobRunExtractor($extractor);

        $config = [
            'elements' => array_fill(0, 500, ['type' => 'object', 'id' => 1, 'cacheKey' => 'object_1']),
            'target' => "job_env('path')",
            'nested' => ['flag' => true, 'ratio' => 1.5, 'empty' => null],
        ];
        $expected = $config;
        $expected['target'] = '/tmp/export';

        $this->assertSame(
            $expected,
            $handler->replace($this->createMock(GenericExecutionEngineMessageInterface::class), $config)
        );
    }

    public function testAJobOwnedByAServiceUserPassesTheParameterValidation(): void
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setId(7);

        $result = $this->validatingHandler()->validate(
            $this->messageWithoutElement(),
            new JobRun(7),
            $this->makeEmpty(UserAccountResolverInterface::class, [
                'getAccountById' => static fn (int $id): ?User => $id === 7 ? $serviceUser : null,
            ])
        );

        $this->assertInstanceOf(ExecuteActionData::class, $result);
        $this->assertSame($serviceUser, $result->getUser());
    }

    public function testAJobWithAnUnknownOwnerIsAborted(): void
    {
        $result = $this->validatingHandler()->validate(
            $this->messageWithoutElement(),
            new JobRun(99),
            $this->makeEmpty(UserAccountResolverInterface::class, ['getAccountById' => null])
        );

        $this->assertInstanceOf(AbortActionData::class, $result);
        $this->assertSame(Config::USER_NOT_FOUND_MESSAGE->value, $result->getTranslationKey());
        $this->assertSame(['%userId%' => 99], $result->getTranslationParameters());
    }

    private function validatingHandler(): AbstractHandler
    {
        return new class() extends AbstractHandler {
            public function validate(
                GenericExecutionEngineMessageInterface $message,
                JobRun $jobRun,
                UserAccountResolverInterface $userAccountResolver,
            ): mixed {
                return $this->validateJobParameters($message, $jobRun, $userAccountResolver);
            }
        };
    }

    private function messageWithoutElement(): GenericExecutionEngineMessageInterface
    {
        return $this->makeEmpty(GenericExecutionEngineMessageInterface::class, ['getElement' => null]);
    }
}
