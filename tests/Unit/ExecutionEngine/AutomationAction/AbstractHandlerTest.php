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
}
